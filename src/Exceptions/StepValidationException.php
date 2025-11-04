<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Exceptions;

use Exception;

class StepValidationException extends Exception
{
    public function __construct(
        public readonly array $errors
    ) {
        parent::__construct('Step validation failed');
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
