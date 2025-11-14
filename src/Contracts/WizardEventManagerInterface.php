<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Contracts;

interface WizardEventManagerInterface
{
    public function fireWizardStarted(string $wizardId, ?int $userId, string $sessionId, array $initialData): void;

    public function fireStepCompleted(string $wizardId, string $stepId, array $stepData, int $percentComplete): void;

    public function fireStepSkipped(string $wizardId, string $stepId, string $sessionId): void;

    public function fireWizardCompleted(string $wizardId, array $allData): void;
}
