<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Exceptions;

use InvalidArgumentException;

final class StepNotFoundException extends InvalidArgumentException
{
    /**
     * Create a new exception instance.
     */
    public function __construct(
        public readonly string $step,
    ) {
        parent::__construct("Wizard step [{$step}] does not exist.");
    }
}
