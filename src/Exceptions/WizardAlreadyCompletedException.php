<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Exceptions;

use RuntimeException;

final class WizardAlreadyCompletedException extends RuntimeException
{
    /**
     * Create a new exception instance.
     */
    public function __construct(
        public readonly string $wizard,
    ) {
        parent::__construct("Wizard [{$wizard}] has already been completed.");
    }
}
