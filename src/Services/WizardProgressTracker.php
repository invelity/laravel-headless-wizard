<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Services;

use Invelity\WizardPackage\Contracts\WizardProgressTrackerInterface;
use Invelity\WizardPackage\Contracts\WizardStorageInterface;
use Invelity\WizardPackage\ValueObjects\WizardProgressValue;

class WizardProgressTracker implements WizardProgressTrackerInterface
{
    public function __construct(
        private readonly WizardStorageInterface $storage,
    ) {}

    public function getProgress(string $wizardId, array $steps): WizardProgressValue
    {
        $wizardData = $this->storage->get($wizardId);
        $completedSteps = $wizardData['completed_steps'] ?? [];
        $currentStepId = $wizardData['current_step'] ?? null;

        $currentStepPosition = 0;
        $remainingStepIds = [];

        foreach ($steps as $index => $step) {
            if ($step->getId() === $currentStepId) {
                $currentStepPosition = $index + 1;
            }

            if (! in_array($step->getId(), $completedSteps)) {
                $remainingStepIds[] = $step->getId();
            }
        }

        return WizardProgressValue::calculate(
            totalSteps: count($steps),
            completedSteps: count($completedSteps),
            currentStepPosition: $currentStepPosition,
            remainingStepIds: $remainingStepIds
        );
    }
}
