<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Exceptions;

use RuntimeException;

final class StepNotAccessibleException extends RuntimeException
{
    /**
     * Create a new exception instance.
     *
     * @param  string|null  $current  The step the visitor should be on instead.
     */
    public function __construct(
        public readonly string $wizard,
        public readonly string $step,
        public readonly ?string $current,
    ) {
        parent::__construct("Wizard step [{$step}] of [{$wizard}] is not accessible yet.");
    }
}
