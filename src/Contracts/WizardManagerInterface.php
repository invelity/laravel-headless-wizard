<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Contracts;

use Invelity\WizardPackage\ValueObjects\StepResult;
use Invelity\WizardPackage\ValueObjects\WizardProgressValue;

interface WizardManagerInterface
{
    public function initialize(string $wizardId, array $config = []): void;

    public function getCurrentStep(): ?WizardStepInterface;

    public function getStep(string $stepId): WizardStepInterface;

    public function processStep(string $stepId, array $data): StepResult;

    public function navigateToStep(string $stepId): void;

    public function getNextStep(): ?WizardStepInterface;

    public function getPreviousStep(): ?WizardStepInterface;

    public function canAccessStep(string $stepId): bool;

    public function getProgress(): WizardProgressValue;

    public function getAllData(): array;

    public function complete(): StepResult;

    public function reset(): void;

    public function loadFromStorage(string $wizardId, int $instanceId): void;

    public function deleteWizard(string $wizardId, int $instanceId): void;

    public function getNavigation(): WizardNavigationInterface;

    public function skipStep(string $stepId): void;
}
