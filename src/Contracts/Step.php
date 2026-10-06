<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Contracts;

use Illuminate\Foundation\Http\FormRequest;
use Invelity\WizardPackage\State;

interface Step
{
    /**
     * Get the identifier of the step, unique within its wizard.
     */
    public function id(): string;

    /**
     * Get the human readable title of the step.
     */
    public function title(): string;

    /**
     * Determine if the visitor may skip the step.
     */
    public function isOptional(): bool;

    /**
     * Determine if the step only displays information and never takes input.
     */
    public function isDisplayOnly(): bool;

    /**
     * Get the steps that must be finished before this one.
     *
     * Processing one of them again with different data reopens this step.
     *
     * @return list<class-string<Step>>
     */
    public function dependencies(): array;

    /**
     * Get the form request that validates the step's input.
     *
     * @return class-string<FormRequest>|null
     */
    public function formRequest(): ?string;

    /**
     * Determine if the step should be left out of the flow for the given state.
     */
    public function shouldSkip(State $state): bool;
}
