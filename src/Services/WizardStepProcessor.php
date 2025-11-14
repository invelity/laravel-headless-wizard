<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Services;

use Invelity\WizardPackage\Contracts\FormRequestValidatorInterface;
use Invelity\WizardPackage\Contracts\WizardEventManagerInterface;
use Invelity\WizardPackage\Contracts\WizardStepInterface;
use Invelity\WizardPackage\Contracts\WizardStepProcessorInterface;
use Invelity\WizardPackage\Contracts\WizardStorageInterface;
use Invelity\WizardPackage\ValueObjects\StepData;
use Invelity\WizardPackage\ValueObjects\StepResult;

class WizardStepProcessor implements WizardStepProcessorInterface
{
    public function __construct(
        private readonly WizardStorageInterface $storage,
        private readonly FormRequestValidatorInterface $formRequestValidator,
        private readonly WizardEventManagerInterface $eventManager,
    ) {}

    public function processStep(
        string $wizardId,
        string $stepId,
        array $data,
        WizardStepInterface $step
    ): StepResult {
        $formRequestClass = $step->getFormRequest();
        $validated = $this->formRequestValidator->validate($formRequestClass, $data);

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
            $this->storage->update($wizardId, "steps.{$stepId}", $validated);
            $this->markStepCompleted($wizardId, $stepId, $validated);
        }

        return $result;
    }

    private function markStepCompleted(string $wizardId, string $stepId, array $validated): void
    {
        $wizardData = $this->storage->get($wizardId);
        $completedSteps = $wizardData['completed_steps'] ?? [];

        if (! in_array($stepId, $completedSteps)) {
            $completedSteps[] = $stepId;
            $this->storage->update($wizardId, 'completed_steps', $completedSteps);

            $totalSteps = count($wizardData['steps'] ?? []);
            $percentComplete = $totalSteps > 0 ? (int) round((count($completedSteps) / $totalSteps) * 100) : 0;

            $this->eventManager->fireStepCompleted(
                wizardId: $wizardId,
                stepId: $stepId,
                stepData: $validated,
                percentComplete: $percentComplete
            );
        }
    }
}
