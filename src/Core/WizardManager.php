<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Core;

use Invelity\WizardPackage\Contracts\WizardDataInterface;
use Invelity\WizardPackage\Contracts\WizardEventManagerInterface;
use Invelity\WizardPackage\Contracts\WizardInitializationInterface;
use Invelity\WizardPackage\Contracts\WizardLifecycleManagerInterface;
use Invelity\WizardPackage\Contracts\WizardManagerInterface;
use Invelity\WizardPackage\Contracts\WizardNavigationInterface;
use Invelity\WizardPackage\Contracts\WizardNavigationManagerInterface;
use Invelity\WizardPackage\Contracts\WizardProgressTrackerInterface;
use Invelity\WizardPackage\Contracts\WizardStepAccessInterface;
use Invelity\WizardPackage\Contracts\WizardStepInterface;
use Invelity\WizardPackage\Contracts\WizardStepProcessorInterface;
use Invelity\WizardPackage\Contracts\WizardStorageInterface;
use Invelity\WizardPackage\Exceptions\InvalidStepException;
use Invelity\WizardPackage\Factories\WizardNavigationFactory;
use Invelity\WizardPackage\Services\StepFinderService;
use Invelity\WizardPackage\Steps\StepFactory;
use Invelity\WizardPackage\ValueObjects\StepResult;
use Invelity\WizardPackage\ValueObjects\WizardProgressValue;
use RuntimeException;

class WizardManager implements 
    WizardManagerInterface,
    WizardInitializationInterface,
    WizardStepAccessInterface,
    WizardNavigationManagerInterface,
    WizardDataInterface
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
        private readonly WizardEventManagerInterface $eventManager,
        private readonly WizardStepProcessorInterface $stepProcessor,
        private readonly WizardProgressTrackerInterface $progressTracker,
        private readonly WizardLifecycleManagerInterface $lifecycleManager,
        private readonly StepFinderService $stepFinder,
        private readonly WizardNavigationFactory $navigationFactory,
    ) {}

    public function initialize(string $wizardId, array $config = []): void
    {
        $stepClasses = $config['steps'] ?? config("wizard.wizards.{$wizardId}.steps", []);
        $this->setupWizardContext($wizardId, $stepClasses);
        $this->lifecycleManager->initializeWizard($wizardId, $this->steps, $config);
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

    /**
     * @throws InvalidStepException
     */
    public function processStep(string $stepId, array $data): StepResult
    {
        $this->ensureInitialized();

        $step = $this->getStep($stepId);
        $result = $this->stepProcessor->processStep($this->currentWizardId, $stepId, $data, $step);

        if ($result->success) {
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

        return $this->progressTracker->getProgress($this->currentWizardId, $this->steps);
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

        return $this->lifecycleManager->completeWizard($this->currentWizardId);
    }

    public function reset(): void
    {
        $this->ensureInitialized();

        $this->lifecycleManager->resetWizard($this->currentWizardId);

        $this->initialize($this->currentWizardId);
    }

    /**
     * @throws InvalidStepException
     */
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

        $this->eventManager->fireStepSkipped(
            wizardId: $this->currentWizardId,
            stepId: $stepId,
            sessionId: (string) session()->getId()
        );

        $nextStep = $this->navigation->getNextStep($stepId);

        if ($nextStep !== null) {
            $this->storage->update($this->currentWizardId, 'current_step', $nextStep->getId());
        }
    }

    private function findStep(string $stepId): ?WizardStepInterface
    {
        return $this->stepFinder->findStep($this->steps, $stepId);
    }

    public function loadFromStorage(string $wizardId, int $instanceId): void
    {
        $stepClasses = config("wizard.wizards.{$wizardId}.steps", []);
        $this->setupWizardContext($wizardId, $stepClasses);
        $this->lifecycleManager->loadFromStorage($wizardId, $instanceId, $this->steps);
    }

    public function deleteWizard(string $wizardId, int $instanceId): void
    {
        $this->lifecycleManager->deleteWizard($wizardId, $instanceId);
    }

    public function getNavigation(): WizardNavigationInterface
    {
        $this->ensureInitialized();

        if ($this->navigation === null) {
            throw new RuntimeException(__('Navigation not initialized.'));
        }

        return $this->navigation;
    }

    private function setupWizardContext(string $wizardId, array $stepClasses): void
    {
        $this->currentWizardId = $wizardId;
        $this->steps = $this->stepFactory->makeMany($stepClasses);

        usort($this->steps, fn ($a, $b) => $a->getOrder() <=> $b->getOrder());

        $this->navigation = $this->navigationFactory->create($this->steps, $wizardId);
    }

    private function ensureInitialized(): void
    {
        if ($this->currentWizardId === null) {
            throw new RuntimeException(__('Wizard not initialized. Call initialize() first.'));
        }
    }
}
