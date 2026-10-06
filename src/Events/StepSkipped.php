<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Events;

use Invelity\WizardPackage\Wizard;

/**
 * Dispatched when an optional step has been skipped.
 */
final readonly class StepSkipped
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
