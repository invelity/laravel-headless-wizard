<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Events;

use Invelity\WizardPackage\Wizard;

/**
 * Dispatched when a wizard has been completed.
 */
final readonly class WizardCompleted
{
    /**
     * Create a new event instance.
     *
     * @param  class-string<Wizard>  $wizard  The class of the wizard.
     * @param  array<string, array<string, mixed>>  $data  The data of every step.
     */
    public function __construct(
        public string $wizard,
        public string $scope,
        public array $data,
    ) {}
}
