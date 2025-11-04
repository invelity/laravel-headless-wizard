<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Contracts;

use Invelity\WizardPackage\ValueObjects\StepResult;

interface WizardLifecycleManagerInterface
{
    public function initialize(string $wizardId, array $config = []): void;

    public function complete(): StepResult;

    public function reset(): void;
}
