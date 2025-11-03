<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\Exceptions;

use Exception;

class InvalidStepException extends Exception
{
    public function __construct(string $stepId)
    {
        parent::__construct("Invalid wizard step: {$stepId}");
    }
}
