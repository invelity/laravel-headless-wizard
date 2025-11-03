<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\Core;

use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use WebSystem\WizardPackage\Contracts\WizardManagerInterface;
use WebSystem\WizardPackage\Contracts\WizardStepInterface;
use WebSystem\WizardPackage\Contracts\WizardStorageInterface;
use WebSystem\WizardPackage\Events\StepCompleted;
use WebSystem\WizardPackage\Events\StepSkipped;
use WebSystem\WizardPackage\Events\WizardCompleted;
use WebSystem\WizardPackage\Events\WizardStarted;
use WebSystem\WizardPackage\Exceptions\InvalidStepException;
use WebSystem\WizardPackage\Models\WizardProgress;
use WebSystem\WizardPackage\Steps\StepFactory;
use WebSystem\WizardPackage\ValueObjects\StepData;
use WebSystem\WizardPackage\ValueObjects\StepResult;
use WebSystem\WizardPackage\ValueObjects\WizardProgressValue;

class WizardManager implements WizardManagerInterface
{
    private ?string $currentWizardId = null;

    /**
     * @var array<WizardStepInterface>
     */
    private array $steps = [];

    private ?WizardNavigation $navigation = null;

    public function __construct(
        private readonly WizardConfiguration $configuration,
        private readonly WizardStorageInterface $storage,
        private readonly StepFactory $stepFactory,
    ) {}

    public function initialize(string $wizardId, array $config = []): void
    {
        $this->currentWizardId = $wizardId;

        $stepClasses = $config['steps'] ?? config("wizard.wizards.{$wizardId}.steps", []);
        $this->steps = $this->stepFactory->makeMany($stepClasses);

        usort($this->steps, fn ($a, $b) => $a->getOrder() <=> $b->getOrder());

        $this->navigation = new WizardNavigation(
            steps: $this->steps,
            storage: $this->storage,
            configuration: $this->configuration,
            wizardId: $wizardId,
        );

        $wizardData = $this->storage->get($wizardId);

        if ($wizardData === null) {
            $firstStepId = ! empty($this->steps) ? $this->steps[0]->getId() : null;

            $this->storage->put($wizardId, [
                'wizard_id' => $wizardId,
                'current_step' => $firstStepId,
                'completed_steps' => [],
                'steps' => [],
                'metadata' => $config['metadata'] ?? [],
                'started_at' => now()->toIso8601String(),
            ]);

            if ($this->configuration->fireEvents) {
                $sessionId = session()->getId();
                Event::dispatch(new WizardStarted(
                    wizardId: $wizardId,
                    userId: $config['user_id'] ?? null,
                    sessionId: $sessionId !== null ? $sessionId : '',
                    initialData: $config['metadata'] ?? []
                ));
            }
        }
    }

    public function getCurrentStep(): ?WizardStepInterface
    {
        $this->ensureInitialized();

        $wizardData = $this->storage->get($this->currentWizardId);
        $currentStepId = $wizardData['current_step'] ?? null;

        if ($currentStepId === null) {
            return null;
        }

        return $this->findStep($currentStepId);
    }

    public function getStep(string $stepId): WizardStepInterface
    {
        $this->ensureInitialized();

        $step = $this->findStep($stepId);

        if ($step === null) {
            throw new InvalidStepException($stepId);
        }

        return $step;
    }

    public function processStep(string $stepId, array $data): StepResult
    {
        $this->ensureInitialized();

        $step = $this->getStep($stepId);

        $validated = $step->validate($data);

        $stepData = new StepData(
            stepId: $stepId,
            data: $validated,
            isValid: true,
            errors: [],
            timestamp: now(),
        );

        $step->beforeProcess($stepData);

        $result = $step->process($stepData);

        $step->afterProcess($result);

        if ($result->success) {
            $this->storage->update($this->currentWizardId, "steps.{$stepId}", $validated);

            $wizardData = $this->storage->get($this->currentWizardId);
            $completedSteps = $wizardData['completed_steps'] ?? [];

            if (! in_array($stepId, $completedSteps)) {
                $completedSteps[] = $stepId;
                $this->storage->update($this->currentWizardId, 'completed_steps', $completedSteps);

                if ($this->configuration->fireEvents) {
                    Event::dispatch(new StepCompleted($this->currentWizardId, $stepId, $validated, $this->getProgress()->percentComplete));
                }
            }

            $nextStep = $this->navigation->getNextStep($stepId);

            if ($nextStep !== null) {
                $this->storage->update($this->currentWizardId, 'current_step', $nextStep->getId());
            }
        }

        return $result;
    }

    /**
     * @throws InvalidStepException
     */
    public function navigateToStep(string $stepId): void
    {
        $this->ensureInitialized();

        if (! $this->canAccessStep($stepId)) {
            throw new InvalidStepException($stepId);
        }

        $this->storage->update($this->currentWizardId, 'current_step', $stepId);
    }

    public function getNextStep(): ?WizardStepInterface
    {
        $this->ensureInitialized();

        return $this->navigation->getNextStep();
    }

    public function getPreviousStep(): ?WizardStepInterface
    {
        $this->ensureInitialized();

        return $this->navigation->getPreviousStep();
    }

    public function canAccessStep(string $stepId): bool
    {
        $this->ensureInitialized();

        return $this->navigation->canNavigateTo($stepId);
    }

    public function getProgress(): WizardProgressValue
    {
        $this->ensureInitialized();

        $wizardData = $this->storage->get($this->currentWizardId);
        $completedSteps = $wizardData['completed_steps'] ?? [];
        $currentStepId = $wizardData['current_step'] ?? null;

        $currentStepPosition = 0;
        $remainingStepIds = [];

        foreach ($this->steps as $index => $step) {
            if ($step->getId() === $currentStepId) {
                $currentStepPosition = $index + 1;
            }

            if (! in_array($step->getId(), $completedSteps)) {
                $remainingStepIds[] = $step->getId();
            }
        }

        return WizardProgressValue::calculate(
            totalSteps: count($this->steps),
            completedSteps: count($completedSteps),
            currentStepPosition: $currentStepPosition,
            remainingStepIds: $remainingStepIds
        );
    }

    public function getAllData(): array
    {
        $this->ensureInitialized();

        $wizardData = $this->storage->get($this->currentWizardId);

        return $wizardData['steps'] ?? [];
    }

    public function complete(): StepResult
    {
        $this->ensureInitialized();

        $progress = $this->getProgress();

        if (! $progress->isComplete) {
            return StepResult::failure(
                ['wizard' => 'Wizard is not complete. Please complete all required steps.']
            );
        }

        $this->storage->update($this->currentWizardId, 'completed_at', now()->toIso8601String());
        $this->storage->update($this->currentWizardId, 'status', 'completed');

        if ($this->configuration->fireEvents) {
            Event::dispatch(new WizardCompleted($this->currentWizardId, $this->getAllData(), now()));
        }

        return StepResult::success(
            data: $this->getAllData(),
            message: 'Wizard completed successfully.'
        );
    }

    public function reset(): void
    {
        $this->ensureInitialized();

        $this->storage->forget($this->currentWizardId);

        $this->initialize($this->currentWizardId);
    }

    public function skipStep(string $stepId): void
    {
        $this->ensureInitialized();

        $step = $this->getStep($stepId);

        if (! $step->canSkip()) {
            throw new InvalidStepException("Step {$stepId} cannot be skipped.");
        }

        $wizardData = $this->storage->get($this->currentWizardId);
        $completedSteps = $wizardData['completed_steps'] ?? [];

        if (! in_array($stepId, $completedSteps)) {
            $completedSteps[] = $stepId;
            $this->storage->update($this->currentWizardId, 'completed_steps', $completedSteps);
        }

        if ($this->configuration->fireEvents) {
            $sessionId = session()->getId();
            Event::dispatch(new StepSkipped($this->currentWizardId, $stepId, $sessionId !== null ? $sessionId : ''));
        }

        $nextStep = $this->navigation->getNextStep($stepId);

        if ($nextStep !== null) {
            $this->storage->update($this->currentWizardId, 'current_step', $nextStep->getId());
        }
    }

    private function findStep(string $stepId): ?WizardStepInterface
    {
        return array_find(
            $this->steps,
            fn (WizardStepInterface $step) => $step->getId() === $stepId
        );
    }

    public function loadFromStorage(string $wizardId, int $instanceId): void
    {
        $this->currentWizardId = $wizardId;

        $stepClasses = config("wizard.wizards.{$wizardId}.steps", []);
        $this->steps = $this->stepFactory->makeMany($stepClasses);

        usort($this->steps, fn ($a, $b) => $a->getOrder() <=> $b->getOrder());

        $this->navigation = new WizardNavigation(
            steps: $this->steps,
            storage: $this->storage,
            configuration: $this->configuration,
            wizardId: $wizardId,
        );

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
                $this->initialize($wizardId);
            }
        }
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

    public function getNavigation(): \WebSystem\WizardPackage\Contracts\WizardNavigationInterface
    {
        $this->ensureInitialized();

        if ($this->navigation === null) {
            throw new \RuntimeException('Navigation not initialized.');
        }

        return $this->navigation;
    }

    private function ensureInitialized(): void
    {
        if ($this->currentWizardId === null) {
            throw new \RuntimeException('Wizard not initialized. Call initialize() first.');
        }
    }
}
