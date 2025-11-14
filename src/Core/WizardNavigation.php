<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Core;

use Invelity\WizardPackage\Contracts\StepFinderInterface;
use Invelity\WizardPackage\Contracts\WizardNavigationInterface;
use Invelity\WizardPackage\Contracts\WizardStepInterface;
use Invelity\WizardPackage\Contracts\WizardStorageInterface;
use Invelity\WizardPackage\ValueObjects\NavigationItem;

class WizardNavigation implements WizardNavigationInterface
{
    /**
     * @param  array<WizardStepInterface>  $steps
     */
    public function __construct(
        private readonly array $steps,
        private readonly WizardStorageInterface $storage,
        private readonly WizardConfiguration $configuration,
        private readonly string $wizardId,
        private readonly StepFinderInterface $stepFinder,
    ) {}

    /**
     * @return NavigationItem[]
     */
    public function getItems(): array
    {
        $wizardData = $this->storage->get($this->wizardId) ?? [];
        $completedSteps = $wizardData['completed_steps'] ?? [];
        $currentStepId = $wizardData['current_step'] ?? null;

        return array_map(
            fn (WizardStepInterface $step, int $index) => new NavigationItem(
                stepId: $step->getId(),
                title: $step->getTitle(),
                position: $index + 1,
                status: in_array($step->getId(), $completedSteps)
                    ? \Invelity\WizardPackage\Enums\StepStatus::Completed
                    : ($step->getId() === $currentStepId
                        ? \Invelity\WizardPackage\Enums\StepStatus::InProgress
                        : \Invelity\WizardPackage\Enums\StepStatus::Pending),
                isAccessible: $this->canNavigateTo($step->getId()),
                isOptional: $step->isOptional(),
                url: $this->getStepUrl($step->getId()),
            ),
            $this->steps,
            array_keys($this->steps)
        );
    }

    public function canNavigateTo(string $stepId): bool
    {
        $wizardData = $this->storage->get($this->wizardId) ?? [];
        $completedSteps = $wizardData['completed_steps'] ?? [];

        if ($this->configuration->allowsJumpNavigation()) {
            return true;
        }

        $step = $this->findStep($stepId);
        if ($step === null) {
            return false;
        }

        $dependencies = $step->getDependencies();
        $hasMissingDependency = array_any(
            $dependencies,
            fn (string $dependencyId) => ! in_array($dependencyId, $completedSteps)
        );

        if ($hasMissingDependency) {
            return false;
        }

        $previousSteps = $this->getStepsBefore($stepId);
        $hasIncompleteRequiredStep = array_any(
            $previousSteps,
            fn (WizardStepInterface $previousStep) => ! $previousStep->isOptional() && ! in_array($previousStep->getId(), $completedSteps)
        );

        if ($hasIncompleteRequiredStep) {
            return false;
        }

        return true;
    }

    public function canGoBack(): bool
    {
        $wizardData = $this->storage->get($this->wizardId) ?? [];
        $currentStepId = $wizardData['current_step'] ?? null;

        if ($currentStepId === null) {
            return false;
        }

        $previousStep = $this->getPreviousStep($currentStepId);

        return $previousStep !== null;
    }

    public function canGoForward(): bool
    {
        $wizardData = $this->storage->get($this->wizardId) ?? [];
        $currentStepId = $wizardData['current_step'] ?? null;

        if ($currentStepId === null) {
            return false;
        }

        $nextStep = $this->getNextStep($currentStepId);

        return $nextStep !== null && $this->canNavigateTo($nextStep->getId());
    }

    public function getStepUrl(string $stepId, ?string $wizardId = null): ?string
    {
        $wizard = $wizardId ?? $this->wizardId;

        return route('wizard.show', [
            'wizard' => $wizard,
            'step' => $stepId,
        ]);
    }

    public function getNextStep(?string $currentStepId = null): ?WizardStepInterface
    {
        if ($currentStepId === null) {
            $wizardData = $this->storage->get($this->wizardId) ?? [];
            $currentStepId = $wizardData['current_step'] ?? null;
        }

        if ($currentStepId === null) {
            return $this->steps[0] ?? null;
        }

        $currentIndex = $this->findStepIndex($currentStepId);
        if ($currentIndex === null) {
            return null;
        }

        for ($i = $currentIndex + 1; $i < count($this->steps); $i++) {
            $step = $this->steps[$i];

            if ($step->shouldSkip($this->storage->get($this->wizardId) ?? [])) {
                continue;
            }

            return $step;
        }

        return null;
    }

    public function getPreviousStep(?string $currentStepId = null): ?WizardStepInterface
    {
        if ($currentStepId === null) {
            $wizardData = $this->storage->get($this->wizardId) ?? [];
            $currentStepId = $wizardData['current_step'] ?? null;
        }

        if ($currentStepId === null) {
            return null;
        }

        $currentIndex = $this->findStepIndex($currentStepId);
        if ($currentIndex === null || $currentIndex === 0) {
            return null;
        }

        for ($i = $currentIndex - 1; $i >= 0; $i--) {
            $step = $this->steps[$i];

            if ($step->shouldSkip($this->storage->get($this->wizardId) ?? [])) {
                continue;
            }

            return $step;
        }

        return null;
    }

    /**
     * @return array<WizardStepInterface>
     */
    private function getStepsBefore(string $stepId): array
    {
        $index = $this->findStepIndex($stepId);
        if ($index === null) {
            return [];
        }

        return array_slice($this->steps, 0, $index);
    }

    private function findStep(string $stepId): ?WizardStepInterface
    {
        return $this->stepFinder->findStep($this->steps, $stepId);
    }

    private function findStepIndex(string $stepId): ?int
    {
        return $this->stepFinder->findStepIndex($this->steps, $stepId);
    }
}
