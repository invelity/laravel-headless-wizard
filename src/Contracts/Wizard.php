<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Contracts;

use Illuminate\Http\Request;
use Invelity\WizardPackage\Navigation;
use Invelity\WizardPackage\Progress;
use Invelity\WizardPackage\State;
use Invelity\WizardPackage\StepCollection;

interface Wizard
{
    /**
     * Get the name of the wizard, unique within the application.
     */
    public function name(): string;

    /**
     * Get the steps of the wizard, in order.
     */
    public function steps(): StepCollection;

    /**
     * Get the scope that identifies the visitor the wizard belongs to.
     */
    public function scope(): string;

    /**
     * Get the current state of the wizard.
     */
    public function state(): State;

    /**
     * Determine if the wizard has stored state.
     */
    public function exists(): bool;

    /**
     * Determine if the wizard has been completed.
     */
    public function isCompleted(): bool;

    /**
     * Start the wizard with the given metadata unless it already exists.
     *
     * @param  array<array-key, mixed>  $metadata
     */
    public function start(array $metadata = []): static;

    /**
     * Validate and store the input of a step, then move to the next step.
     *
     * The input is validated by the step's form request, with its full lifecycle.
     *
     * @param  Request|array<string, mixed>  $input
     * @return array<string, mixed> The data stored for the step.
     */
    public function process(string $step, Request|array $input = []): array;

    /**
     * Skip an optional step and move to the next step.
     */
    public function skip(string $step): void;

    /**
     * Reopen a step and the steps that depend on it, keeping their data for prefilling.
     */
    public function reopen(string $step): void;

    /**
     * Move the visitor to an accessible step.
     */
    public function goTo(string $step): void;

    /**
     * Complete the wizard once every required step is finished.
     *
     * @return array<string, array<string, mixed>> The data of every step.
     */
    public function complete(): array;

    /**
     * Remove the stored state of the wizard.
     */
    public function reset(): void;

    /**
     * Get a step by its identifier.
     */
    public function step(string $id): Step;

    /**
     * Get the step the visitor should be on.
     */
    public function current(): ?Step;

    /**
     * Get the step after the given step, or after the current step.
     */
    public function next(?string $step = null): ?Step;

    /**
     * Get the step before the given step, or before the current step.
     */
    public function previous(?string $step = null): ?Step;

    /**
     * Get the first step that takes input and is neither processed nor skipped.
     */
    public function firstUnfinished(): ?Step;

    /**
     * Determine if the visitor may open the given step.
     */
    public function canAccess(string $step): bool;

    /**
     * Get the navigation as seen from the given step, or from the current step.
     */
    public function navigation(?string $step = null): Navigation;

    /**
     * Get the progress through the steps that take input.
     */
    public function progress(): Progress;

    /**
     * Get the URL of a step, if the application provides one.
     */
    public function url(string $step): ?string;

    /**
     * Get the data of every step, or of the given step.
     *
     * @return ($step is null ? array<string, array<string, mixed>> : array<string, mixed>)
     */
    public function data(?string $step = null): array;

    /**
     * Get the metadata, or one value of it using "dot" notation.
     */
    public function metadata(?string $key = null, mixed $default = null): mixed;

    /**
     * Store metadata, using "dot" notation for nested keys.
     *
     * @param  string|array<array-key, mixed>  $key
     */
    public function putMetadata(string|array $key, mixed $value = null): void;

    /**
     * Remove metadata, using "dot" notation for nested keys.
     *
     * @param  string|list<string>  $keys
     */
    public function forgetMetadata(string|array $keys): void;
}
