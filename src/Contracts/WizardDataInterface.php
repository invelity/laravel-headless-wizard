<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Contracts;

use Invelity\WizardPackage\ValueObjects\StepResult;
use Invelity\WizardPackage\ValueObjects\WizardProgressValue;

interface WizardDataInterface
{
    public function processStep(string $stepId, array $data): StepResult;

    public function getAllData(): array;

    public function getProgress(): WizardProgressValue;

    public function complete(): StepResult;

    public function skipStep(string $stepId): void;

    public function deleteWizard(string $wizardId, int $instanceId): void;
}
