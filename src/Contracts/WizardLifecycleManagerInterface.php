<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Contracts;

use Invelity\WizardPackage\ValueObjects\StepResult;

interface WizardLifecycleManagerInterface
{
    public function initializeWizard(string $wizardId, array $steps, array $config = []): void;

    public function loadFromStorage(string $wizardId, int $instanceId, array $steps): void;

    public function completeWizard(string $wizardId): StepResult;

    public function resetWizard(string $wizardId): void;

    public function deleteWizard(string $wizardId, int $instanceId): void;
}
