<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Events;

use Invelity\WizardPackage\Wizard;

/**
 * Dispatched when a finished step has been reopened, either explicitly or because a step it depends on changed.
 */
final readonly class StepReopened
{
    /**
     * Create a new event instance.
     *
     * @param  class-string<Wizard>  $wizard  The class of the wizard.
     */
    public function __construct(
        public string $wizard,
        public string $scope,
        public string $step,
    ) {}
}
