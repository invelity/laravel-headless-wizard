<?php

declare(strict_types=1);

namespace Invelity\WizardPackage;

use Closure;
use Invelity\WizardPackage\Contracts\Step;

/**
 * Answers every question about moving through a wizard from its steps and state alone.
 *
 * The navigator performs no IO, so the rules of the flow live in one place and can be
 * evaluated for any state.
 */
final readonly class Navigator
{
    /**
     * The steps that take part in the flow for the current state.
     */
    private StepCollection $applicable;

    /**
     * Create a new navigator instance.
     *
     * @param  bool  $allowJumping  Whether every step in the flow is accessible regardless of order.
     */
    public function __construct(
        private StepCollection $steps,
        private State $state,
        private bool $allowJumping = false,
    ) {
        $this->applicable = $steps->reject(fn (Step $step): bool => $step->shouldSkip($state))->values();
    }

    /**
     * Get the steps that take part in the flow, leaving out conditionally skipped ones.
     */
    public function applicable(): StepCollection
    {
        return $this->applicable;
    }

    /**
     * Determine if the step takes part in the flow.
     */
    public function isApplicable(Step $step): bool
    {
        return $this->applicable->find($step->id()) !== null;
    }

    /**
     * Determine if the visitor may open the step.
     *
     * A step is accessible when it takes part in the flow and its dependencies are finished.
     * Unless jumping between steps is allowed, every earlier required step must be finished too.
     */
    public function canAccess(Step $step): bool
    {
        if (! $this->isApplicable($step) || ! $this->dependenciesAreFinished($step)) {
            return false;
        }

        return $this->allowJumping || $this->predecessorsAreSettled($step);
    }

    /**
     * Get the step the visitor should be on.
     *
     * That is the stored current step while it stays accessible, otherwise the first
     * unfinished step, otherwise the last step of the flow.
     */
    public function current(): ?Step
    {
        $stored = $this->state->current === null ? null : $this->applicable->find($this->state->current);

        if ($stored !== null && $this->canAccess($stored)) {
            return $stored;
        }

        return $this->firstUnfinished() ?? $this->applicable->last();
    }

    /**
     * Get the first step of the flow that takes input and is neither processed nor skipped.
     */
    public function firstUnfinished(): ?Step
    {
        return $this->applicable->first(
            fn (Step $step): bool => ! $step->isDisplayOnly() && ! $this->state->hasFinished($step->id())
        );
    }

    /**
     * Get the step of the flow that follows the given one.
     */
    public function next(Step $step): ?Step
    {
        return $this->steps
            ->skipUntil(fn (Step $candidate): bool => $candidate->id() === $step->id())
            ->skip(1)
            ->first(fn (Step $candidate): bool => $this->isApplicable($candidate));
    }

    /**
     * Get the step of the flow that precedes the given one.
     */
    public function previous(Step $step): ?Step
    {
        return $this->steps
            ->takeUntil(fn (Step $candidate): bool => $candidate->id() === $step->id())
            ->last(fn (Step $candidate): bool => $this->isApplicable($candidate));
    }

    /**
     * Get the required steps of the flow that are not finished yet.
     */
    public function missing(): StepCollection
    {
        return $this->applicable->reject(fn (Step $step): bool => $this->isSettled($step))->values();
    }

    /**
     * Determine if every required step of the flow is finished.
     */
    public function isComplete(): bool
    {
        return $this->missing()->isEmpty();
    }

    /**
     * Get the progress through the steps of the flow that take input.
     */
    public function progress(): Progress
    {
        $counted = $this->applicable->reject(fn (Step $step): bool => $step->isDisplayOnly());

        return new Progress(
            completed: $counted->filter(fn (Step $step): bool => $this->state->hasFinished($step->id()))->count(),
            total: $counted->count(),
        );
    }

    /**
     * Get the navigation as seen from the given step, or from the current step.
     *
     * @param  (Closure(Step): ?string)|null  $url  Resolves the URL of a step.
     */
    public function navigation(?Step $from = null, ?Closure $url = null): Navigation
    {
        $from ??= $this->current();

        $items = $this->applicable->toBase()->values()->map(fn (Step $step, int $index): NavigationItem => new NavigationItem(
            id: $step->id(),
            title: $step->title(),
            position: $index + 1,
            status: $this->status($step),
            current: $from !== null && $step->id() === $from->id(),
            accessible: $this->canAccess($step),
            optional: $step->isOptional(),
            displayOnly: $step->isDisplayOnly(),
            url: $url === null ? null : $url($step),
        ));

        return new Navigation(
            items: array_values($items->all()),
            current: $from?->id(),
            previous: $from === null ? null : $this->previous($from)?->id(),
            next: $from === null ? null : $this->next($from)?->id(),
        );
    }

    /**
     * Get the status of the step in the state.
     */
    private function status(Step $step): StepStatus
    {
        return match (true) {
            $this->state->hasCompleted($step->id()) => StepStatus::Completed,
            $this->state->hasSkipped($step->id()) => StepStatus::Skipped,
            default => StepStatus::Pending,
        };
    }

    /**
     * Determine if the step no longer holds back the steps after it.
     */
    private function isSettled(Step $step): bool
    {
        return $step->isOptional() || $step->isDisplayOnly() || $this->state->hasFinished($step->id());
    }

    /**
     * Determine if every dependency of the step that takes part in the flow is finished.
     */
    private function dependenciesAreFinished(Step $step): bool
    {
        foreach ($step->dependencies() as $class) {
            $dependency = $this->steps->findByClass($class);

            if ($dependency !== null && $this->isApplicable($dependency) && ! $this->state->hasFinished($dependency->id())) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if every step of the flow before the given one is settled.
     */
    private function predecessorsAreSettled(Step $step): bool
    {
        return $this->applicable
            ->takeUntil(fn (Step $candidate): bool => $candidate->id() === $step->id())
            ->every(fn (Step $previous): bool => $this->isSettled($previous));
    }
}
