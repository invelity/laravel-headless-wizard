<?php

declare(strict_types=1);

namespace Invelity\WizardPackage;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Invelity\WizardPackage\Contracts\Step as StepContract;

abstract class Step implements StepContract
{
    /**
     * The identifier of the step.
     *
     * Defaults to the kebab-cased class name without the "Step" suffix.
     */
    protected string $id;

    /**
     * The human readable title of the step.
     *
     * Defaults to the headline of the class name without the "Step" suffix.
     */
    protected string $title;

    /**
     * Indicates if the visitor may skip the step.
     */
    protected bool $optional = false;

    /**
     * Indicates if the step only displays information and never takes input.
     */
    protected bool $displayOnly = false;

    /**
     * The steps that must be finished before this one.
     *
     * @var list<class-string<StepContract>>
     */
    protected array $dependencies = [];

    /**
     * The form request that validates the step's input.
     *
     * @var class-string<FormRequest>|null
     */
    protected ?string $formRequest = null;

    public function id(): string
    {
        return $this->id ??= Str::kebab($this->baseName());
    }

    public function title(): string
    {
        return $this->title ??= Str::headline($this->baseName());
    }

    public function isOptional(): bool
    {
        return $this->optional;
    }

    public function isDisplayOnly(): bool
    {
        return $this->displayOnly;
    }

    public function dependencies(): array
    {
        return $this->dependencies;
    }

    public function formRequest(): ?string
    {
        return $this->displayOnly ? null : $this->formRequest;
    }

    public function shouldSkip(State $state): bool
    {
        return false;
    }

    /**
     * Get the class name of the step without the "Step" suffix.
     */
    private function baseName(): string
    {
        $name = class_basename($this);

        return $name !== 'Step' && Str::endsWith($name, 'Step') ? Str::beforeLast($name, 'Step') : $name;
    }
}
