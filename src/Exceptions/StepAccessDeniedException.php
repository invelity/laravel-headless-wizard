<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\Exceptions;

use Exception;

class StepAccessDeniedException extends Exception
{
    public function __construct(string $stepId)
    {
        parent::__construct("Access denied to step: {$stepId}");
    }
}
