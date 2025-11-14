<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Services;

use Invelity\WizardPackage\Contracts\WizardEventManagerInterface;
use Invelity\WizardPackage\Contracts\WizardLifecycleManagerInterface;
use Invelity\WizardPackage\Contracts\WizardProgressTrackerInterface;
use Invelity\WizardPackage\Contracts\WizardStorageInterface;
use Invelity\WizardPackage\Core\WizardConfiguration;
use Invelity\WizardPackage\Models\WizardProgress;
use Invelity\WizardPackage\ValueObjects\StepResult;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WizardLifecycleManager implements WizardLifecycleManagerInterface
{
    public function __construct(
        private readonly WizardStorageInterface $storage,
        private readonly WizardEventManagerInterface $eventManager,
        private readonly WizardProgressTrackerInterface $progressTracker,
        private readonly WizardConfiguration $configuration,
    ) {}

    public function initializeWizard(string $wizardId, array $steps, array $config = []): void
    {
        $wizardData = $this->storage->get($wizardId);

        if ($wizardData === null) {
            $firstStepId = ! empty($steps) ? $steps[0]->getId() : null;

            $this->storage->put($wizardId, [
                'wizard_id' => $wizardId,
                'current_step' => $firstStepId,
                'completed_steps' => [],
                'steps' => [],
                'metadata' => $config['metadata'] ?? [],
                'started_at' => now()->toIso8601String(),
            ]);

            $this->eventManager->fireWizardStarted(
                wizardId: $wizardId,
                userId: $config['user_id'] ?? null,
                sessionId: (string) session()->getId(),
                initialData: $config['metadata'] ?? []
            );
        }
    }

    public function loadFromStorage(string $wizardId, int $instanceId, array $steps): void
    {
        if ($this->configuration->storage === 'database') {
            $wizardData = WizardProgress::find($instanceId);

            if ($wizardData === null) {
                throw new NotFoundHttpException("Wizard instance {$instanceId} not found.");
            }

            $this->storage->put($wizardId, [
                'wizard_id' => $wizardData->wizard_id,
                'current_step' => $wizardData->current_step,
                'completed_steps' => $wizardData->completed_steps,
                'steps' => $wizardData->step_data,
                'metadata' => $wizardData->metadata,
                'started_at' => $wizardData->started_at?->toIso8601String(),
            ]);
        } else {
            $existingData = $this->storage->get($wizardId);
            if ($existingData === null) {
                $this->initializeWizard($wizardId, $steps);
            }
        }
    }

    public function completeWizard(string $wizardId): StepResult
    {
        $wizardData = $this->storage->get($wizardId);
        $steps = $wizardData['steps'] ?? [];
        
        $this->storage->update($wizardId, 'completed_at', now()->toIso8601String());
        $this->storage->update($wizardId, 'status', 'completed');

        $this->eventManager->fireWizardCompleted(
            wizardId: $wizardId,
            allData: $steps
        );

        return StepResult::success(
            data: $steps,
            message: 'Wizard completed successfully.'
        );
    }

    public function resetWizard(string $wizardId): void
    {
        $this->storage->forget($wizardId);
    }

    public function deleteWizard(string $wizardId, int $instanceId): void
    {
        if ($this->configuration->storage === 'database') {
            $wizardData = WizardProgress::find($instanceId);

            if ($wizardData === null) {
                throw new NotFoundHttpException("Wizard instance {$instanceId} not found.");
            }

            $wizardData->delete();
        }

        $this->storage->forget($wizardId);
    }
}
