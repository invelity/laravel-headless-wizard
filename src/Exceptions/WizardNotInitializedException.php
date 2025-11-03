<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\Exceptions;

use Exception;

class WizardNotInitializedException extends Exception
{
    public function __construct(string $wizardId)
    {
        parent::__construct("Wizard not initialized: {$wizardId}");
    }
}
