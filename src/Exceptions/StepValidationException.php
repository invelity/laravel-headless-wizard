<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Exceptions;

use Illuminate\Validation\ValidationException;

class StepValidationException extends ValidationException
{
    public function getErrors(): array
    {
        return $this->validator->errors()->toArray();
    }
}
