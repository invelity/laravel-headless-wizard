<?php

declare(strict_types=1);

namespace Invelity\WizardPackage;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Invelity\WizardPackage\Contracts\Step;
use Invelity\WizardPackage\Contracts\Wizard as WizardContract;
use Invelity\WizardPackage\Events\StepCompleted;
use Invelity\WizardPackage\Events\StepReopened;
use Invelity\WizardPackage\Events\StepSkipped;
use Invelity\WizardPackage\Events\WizardCompleted;
use Invelity\WizardPackage\Events\WizardReset;
use Invelity\WizardPackage\Events\WizardStarted;
use Invelity\WizardPackage\Exceptions\InvalidWizardException;
use Invelity\WizardPackage\Exceptions\StepNotAccessibleException;
use Invelity\WizardPackage\Exceptions\WizardAlreadyCompletedException;

/**
 * The base class of every wizard.
 *
 * A subclass declares the wizard; an instance resolved from the container, or through
 * Wizard::for(), is bound to one visitor and runs the wizard for them.
 */
abstract class Wizard implements WizardContract
{
    /**
     * The name of the wizard.
     *
     * Defaults to the kebab-cased class name without the "Wizard" suffix.
     */
    protected string $name;

    /**
     * The steps of the wizard, in order.
     *
     * @var list<class-string<Step>>
     */
    protected array $steps = [];

    /**
     * The store that keeps the state; null uses the default store.
     */
    protected ?string $store = null;

    /**
     * Indicates if the visitor may open any step regardless of the order of the steps.
     */
    protected bool $allowJumping = false;

    /**
     * The services the wizard runs on.
     */
    private ?Runtime $runtime = null;

    /**
     * The resolved steps.
     */
    private ?StepCollection $resolvedSteps = null;

    /**
     * The loaded state.
     */
    private ?State $state = null;

    /**
     * Indicates if the state is stored.
     */
    private bool $exists = false;

    public function name(): string
    {
        return $this->name ??= Str::kebab($this->baseName());
    }

    public function steps(): StepCollection
    {
        return $this->resolvedSteps ??= $this->resolveSteps();
    }

    /**
     * Get the name of the store that keeps the state, or null for the default store.
     */
    public function storeName(): ?string
    {
        return $this->store;
    }

    /**
     * Determine if the visitor may open any step regardless of the order of the steps.
     */
    public function allowsJumping(): bool
    {
        return $this->allowJumping;
    }

    public function scope(): string
    {
        return $this->runtime()->scope();
    }

    public function state(): State
    {
        return $this->state ??= $this->load();
    }

    public function exists(): bool
    {
        $this->state();

        return $this->exists;
    }

    public function isCompleted(): bool
    {
        return $this->state()->isCompleted();
    }

    public function start(array $metadata = []): static
    {
        if (! $this->exists()) {
            $this->save($this->state()->withMetadata($metadata));
        }

        return $this;
    }

    public function process(string $step, Request|array $input = []): array
    {
        $this->ensureNotCompleted();

        $step = $this->accessibleStep($step);

        if ($step->isDisplayOnly()) {
            throw ValidationException::withMessages([
                'step' => $this->runtime()->trans('display_only', ['step' => $step->title()]),
            ]);
        }

        $data = $this->handle($step, $this->runtime()->validator->validate($step, $input));

        $state = $this->state();
        $changed = ! $state->hasCompleted($step->id()) || $state->data($step->id()) !== $data;
        $reopened = $changed ? $this->finishedDependents($step, $state) : [];
        $state = $state->withCompleted($step->id(), $data)->withReopened(...$reopened);

        $navigator = $this->navigator($state);

        $this->save($state->withCurrent($navigator->next($step)?->id() ?? $step->id()));

        $this->dispatch(new StepCompleted(static::class, $this->scope(), $step->id(), $data, $navigator->progress()->percentage()));

        foreach ($reopened as $dependent) {
            $this->dispatch(new StepReopened(static::class, $this->scope(), $dependent));
        }

        return $data;
    }

    public function skip(string $step): void
    {
        $this->ensureNotCompleted();

        $step = $this->accessibleStep($step);

        if (! $step->isOptional()) {
            throw ValidationException::withMessages([
                'step' => $this->runtime()->trans('step_not_optional', ['step' => $step->title()]),
            ]);
        }

        $state = $this->state()->withSkipped($step->id());

        $this->save($state->withCurrent($this->navigator($state)->next($step)?->id() ?? $step->id()));

        $this->dispatch(new StepSkipped(static::class, $this->scope(), $step->id()));
    }

    public function reopen(string $step): void
    {
        $step = $this->step($step);
        $state = $this->state();

        $reopened = array_values(array_filter(
            [$step->id(), ...$this->finishedDependents($step, $state)],
            fn (string $id): bool => $state->hasFinished($id),
        ));

        $this->save($state->withReopened(...$reopened)->withCurrent($step->id()));

        foreach ($reopened as $id) {
            $this->dispatch(new StepReopened(static::class, $this->scope(), $id));
        }
    }

    public function goTo(string $step): void
    {
        $this->save($this->state()->withCurrent($this->accessibleStep($step)->id()));
    }

    public function complete(): array
    {
        $this->ensureNotCompleted();

        $navigator = $this->navigator();

        if (! $navigator->isComplete()) {
            throw ValidationException::withMessages([
                'steps' => $this->runtime()->trans('incomplete', [
                    'steps' => $navigator->missing()->toBase()->map(fn (Step $step): string => $step->title())->implode(', '),
                ]),
            ]);
        }

        $landing = $navigator->applicable()->last(fn (Step $step): bool => $step->isDisplayOnly());

        $state = $this->state()->withCompletedAt(CarbonImmutable::now());

        $this->save($landing === null ? $state : $state->withCurrent($landing->id()));

        $this->dispatch(new WizardCompleted(static::class, $this->scope(), $state->data));

        return $state->data;
    }

    public function reset(): void
    {
        $existed = $this->exists();

        $this->runtime()->store->forget($this->name(), $this->scope());

        $this->state = new State;
        $this->exists = false;

        if ($existed) {
            $this->dispatch(new WizardReset(static::class, $this->scope()));
        }
    }

    public function step(string $id): Step
    {
        return $this->steps()->findOrFail($id);
    }

    public function current(): ?Step
    {
        return $this->navigator()->current();
    }

    public function next(?string $step = null): ?Step
    {
        $from = $step === null ? $this->current() : $this->step($step);

        return $from === null ? null : $this->navigator()->next($from);
    }

    public function previous(?string $step = null): ?Step
    {
        $from = $step === null ? $this->current() : $this->step($step);

        return $from === null ? null : $this->navigator()->previous($from);
    }

    public function firstUnfinished(): ?Step
    {
        return $this->navigator()->firstUnfinished();
    }

    public function canAccess(string $step): bool
    {
        return $this->navigator()->canAccess($this->step($step));
    }

    public function navigation(?string $step = null): Navigation
    {
        return $this->navigator()->navigation(
            $step === null ? null : $this->step($step),
            fn (Step $step): ?string => $this->url($step->id()),
        );
    }

    public function progress(): Progress
    {
        return $this->navigator()->progress();
    }

    public function url(string $step): ?string
    {
        $step = $this->step($step);

        return $this->stepUrl($step) ?? $this->runtime()->url($this, $step);
    }

    public function data(?string $step = null): array
    {
        return $step === null ? $this->state()->data : $this->state()->data($this->step($step)->id());
    }

    public function metadata(?string $key = null, mixed $default = null): mixed
    {
        return $key === null ? $this->state()->metadata : Arr::get($this->state()->metadata, $key, $default);
    }

    public function putMetadata(string|array $key, mixed $value = null): void
    {
        $metadata = $this->state()->metadata;

        foreach (is_array($key) ? $key : [$key => $value] as $path => $item) {
            Arr::set($metadata, $path, $item);
        }

        $this->save($this->state()->withMetadata($metadata));
    }

    public function forgetMetadata(string|array $keys): void
    {
        $metadata = $this->state()->metadata;

        Arr::forget($metadata, $keys);

        $this->save($this->state()->withMetadata($metadata));
    }

    /**
     * Bind the wizard to the services it runs on.
     *
     * @internal Called by the wizard manager.
     */
    public function setRuntime(Runtime $runtime): void
    {
        $this->runtime = $runtime;
        $this->resolvedSteps = null;
        $this->state = null;
        $this->exists = false;
    }

    /**
     * Determine if the wizard is bound to the services it runs on.
     *
     * @internal Called by the wizard manager.
     */
    public function hasRuntime(): bool
    {
        return $this->runtime !== null;
    }

    /**
     * Get the URL of a step.
     *
     * Override this method to link the steps to the application's own routes. Returning
     * null falls back to the resolver registered with Wizard::resolveUrlsUsing(), and then
     * to the routes registered with Route::wizard().
     */
    protected function stepUrl(Step $step): ?string
    {
        return null;
    }

    /**
     * Get the services the wizard runs on.
     *
     * @throws InvalidWizardException
     */
    private function runtime(): Runtime
    {
        return $this->runtime ?? throw InvalidWizardException::notResolved(static::class);
    }

    /**
     * Get a navigator for the given state, or for the current state.
     */
    private function navigator(?State $state = null): Navigator
    {
        return new Navigator($this->steps(), $state ?? $this->state(), $this->allowsJumping());
    }

    /**
     * Get a step the visitor may open.
     *
     * @throws StepNotAccessibleException
     */
    private function accessibleStep(string $id): Step
    {
        $step = $this->step($id);

        if (! $this->navigator()->canAccess($step)) {
            throw new StepNotAccessibleException($this->name(), $step->id(), $this->current()?->id());
        }

        return $step;
    }

    /**
     * Ensure the wizard has not been completed yet.
     *
     * @throws WizardAlreadyCompletedException
     */
    private function ensureNotCompleted(): void
    {
        if ($this->isCompleted()) {
            throw new WizardAlreadyCompletedException($this->name());
        }
    }

    /**
     * Run the step's handle() method, when it has one, on the validated data.
     *
     * The method is called through the container, so it may type-hint any service; it
     * receives the data as $data and the wizard as $wizard. The array it returns is what
     * gets stored; when it returns nothing, the validated data is stored.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     *
     * @throws InvalidWizardException
     */
    private function handle(Step $step, array $validated): array
    {
        if (! method_exists($step, 'handle')) {
            return $validated;
        }

        $result = $this->runtime()->container->call([$step, 'handle'], ['data' => $validated, 'wizard' => $this]);

        if ($result !== null && ! is_array($result)) {
            throw InvalidWizardException::invalidHandleResult(static::class, $step->id());
        }

        /** @var array<string, mixed> */
        return $result ?? $validated;
    }

    /**
     * Get the finished steps that depend on the given step.
     *
     * @return list<string>
     */
    private function finishedDependents(Step $step, State $state): array
    {
        return $this->steps()
            ->filter(fn (Step $candidate): bool => in_array($step::class, $candidate->dependencies(), true)
                && $state->hasFinished($candidate->id()))
            ->ids();
    }

    /**
     * Load the state from the store.
     */
    private function load(): State
    {
        $stored = $this->runtime()->store->get($this->name(), $this->scope());

        $this->exists = $stored !== null;

        return $stored === null ? new State : State::fromArray($stored);
    }

    /**
     * Store the state, starting the wizard when it is stored for the first time.
     */
    private function save(State $state): void
    {
        $started = ! $this->exists();

        if (! $state->isStarted()) {
            $state = $state->withStartedAt(CarbonImmutable::now());
        }

        if ($state->current === null) {
            $state = $state->withCurrent($this->navigator($state)->current()?->id());
        }

        $this->runtime()->store->put($this->name(), $this->scope(), $state->toArray());

        $this->state = $state;
        $this->exists = true;

        if ($started) {
            $this->dispatch(new WizardStarted(static::class, $this->scope(), $state->metadata));
        }
    }

    /**
     * Dispatch an event of the wizard.
     */
    private function dispatch(object $event): void
    {
        $this->runtime()->events->dispatch($event);
    }

    /**
     * Resolve the step classes and check that they form a valid wizard.
     *
     * @throws InvalidWizardException
     */
    private function resolveSteps(): StepCollection
    {
        $steps = new StepCollection;

        foreach ($this->steps as $class) {
            $step = $this->runtime()->container->make($class);

            if (! $step instanceof Step) {
                throw InvalidWizardException::notAStep(static::class, $class);
            }

            if ($steps->find($step->id()) !== null) {
                throw InvalidWizardException::duplicateStep(static::class, $step->id());
            }

            $steps->push($step);
        }

        foreach ($steps as $step) {
            foreach ($step->dependencies() as $dependency) {
                if ($steps->findByClass($dependency) === null) {
                    throw InvalidWizardException::unknownDependency(static::class, $step->id(), $dependency);
                }
            }
        }

        return $steps;
    }

    /**
     * Get the class name of the wizard without the "Wizard" suffix.
     */
    private function baseName(): string
    {
        $name = class_basename($this);

        return $name !== 'Wizard' && Str::endsWith($name, 'Wizard') ? Str::beforeLast($name, 'Wizard') : $name;
    }
}
