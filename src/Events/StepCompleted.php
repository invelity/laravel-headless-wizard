<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Events;

use Invelity\WizardPackage\Wizard;

/**
 * Dispatched when the input of a step has been validated and stored.
 */
final readonly class StepCompleted
{
    /**
     * Create a new event instance.
     *
     * @param  class-string<Wizard>  $wizard  The class of the wizard.
     * @param  array<string, mixed>  $data  The data stored for the step.
     * @param  int  $percentage  The progress of the wizard after the step.
     */
    public function __construct(
        public string $wizard,
        public string $scope,
        public string $step,
        public array $data,
        public int $percentage,
    ) {}
}
