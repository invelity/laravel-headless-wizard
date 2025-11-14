<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Services;

use Illuminate\Support\Facades\Event;
use Invelity\WizardPackage\Contracts\WizardEventManagerInterface;
use Invelity\WizardPackage\Core\WizardConfiguration;
use Invelity\WizardPackage\Events\StepCompleted;
use Invelity\WizardPackage\Events\StepSkipped;
use Invelity\WizardPackage\Events\WizardCompleted;
use Invelity\WizardPackage\Events\WizardStarted;

class WizardEventManager implements WizardEventManagerInterface
{
    public function __construct(
        private readonly WizardConfiguration $configuration,
    ) {}

    public function fireWizardStarted(string $wizardId, ?int $userId, string $sessionId, array $initialData): void
    {
        if (!$this->configuration->fireEvents) {
            return;
        }

        Event::dispatch(new WizardStarted(
            wizardId: $wizardId,
            userId: $userId,
            sessionId: $sessionId,
            initialData: $initialData
        ));
    }

    public function fireStepCompleted(string $wizardId, string $stepId, array $stepData, int $percentComplete): void
    {
        if (!$this->configuration->fireEvents) {
            return;
        }

        Event::dispatch(new StepCompleted($wizardId, $stepId, $stepData, $percentComplete));
    }

    public function fireStepSkipped(string $wizardId, string $stepId, string $sessionId): void
    {
        if (!$this->configuration->fireEvents) {
            return;
        }

        Event::dispatch(new StepSkipped($wizardId, $stepId, $sessionId));
    }

    public function fireWizardCompleted(string $wizardId, array $allData): void
    {
        if (!$this->configuration->fireEvents) {
            return;
        }

        Event::dispatch(new WizardCompleted($wizardId, $allData, now()));
    }
}
