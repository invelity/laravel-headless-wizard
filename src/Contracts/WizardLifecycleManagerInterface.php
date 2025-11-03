<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\Contracts;

use WebSystem\WizardPackage\ValueObjects\StepResult;

interface WizardLifecycleManagerInterface
{
    public function initialize(string $wizardId, array $config = []): void;

    public function complete(): StepResult;

    public function reset(): void;
}
