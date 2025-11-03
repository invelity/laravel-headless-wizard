<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\Contracts;

interface WizardPersistenceInterface
{
    public function loadFromStorage(string $wizardId, int $instanceId): void;

    public function deleteWizard(string $wizardId, int $instanceId): void;

    public function getStep(string $stepId): WizardStepInterface;
}
