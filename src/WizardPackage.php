<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage;

use WebSystem\WizardPackage\Contracts\WizardManagerInterface;
use WebSystem\WizardPackage\Contracts\WizardNavigationInterface;
use WebSystem\WizardPackage\Contracts\WizardStepInterface;
use WebSystem\WizardPackage\ValueObjects\StepResult;
use WebSystem\WizardPackage\ValueObjects\WizardProgressValue;

class WizardPackage
{
    public function __construct(
        protected WizardManagerInterface $manager
    ) {}

    public function initialize(string $wizardId, array $config = []): void
    {
        $this->manager->initialize($wizardId, $config);
    }

    public function getCurrentStep(): ?WizardStepInterface
    {
        return $this->manager->getCurrentStep();
    }

    public function getStep(string $stepId): WizardStepInterface
    {
        return $this->manager->getStep($stepId);
    }

    public function processStep(string $stepId, array $data): StepResult
    {
        return $this->manager->processStep($stepId, $data);
    }

    public function navigateToStep(string $stepId): void
    {
        $this->manager->navigateToStep($stepId);
    }

    public function getNextStep(): ?WizardStepInterface
    {
        return $this->manager->getNextStep();
    }

    public function getPreviousStep(): ?WizardStepInterface
    {
        return $this->manager->getPreviousStep();
    }

    public function canAccessStep(string $stepId): bool
    {
        return $this->manager->canAccessStep($stepId);
    }

    public function getProgress(): WizardProgressValue
    {
        return $this->manager->getProgress();
    }

    public function getAllData(): array
    {
        return $this->manager->getAllData();
    }

    public function complete(): StepResult
    {
        return $this->manager->complete();
    }

    public function reset(): void
    {
        $this->manager->reset();
    }

    public function loadFromStorage(string $wizardId, int $instanceId): void
    {
        $this->manager->loadFromStorage($wizardId, $instanceId);
    }

    public function deleteWizard(string $wizardId, int $instanceId): void
    {
        $this->manager->deleteWizard($wizardId, $instanceId);
    }

    public function getNavigation(): WizardNavigationInterface
    {
        return $this->manager->getNavigation();
    }

    public function skipStep(string $stepId): void
    {
        $this->manager->skipStep($stepId);
    }
}
