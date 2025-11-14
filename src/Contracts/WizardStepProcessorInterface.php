<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Contracts;

use Invelity\WizardPackage\ValueObjects\StepResult;

interface WizardStepProcessorInterface
{
    public function processStep(
        string $wizardId,
        string $stepId,
        array $data,
        WizardStepInterface $step
    ): StepResult;
}
