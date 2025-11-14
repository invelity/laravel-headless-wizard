<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Http\Responses;

use Invelity\WizardPackage\Contracts\WizardNavigationInterface;
use Invelity\WizardPackage\Contracts\WizardStepInterface;
use Invelity\WizardPackage\ValueObjects\WizardProgressValue;

class WizardStepResponseBuilder
{
    public function buildStepShowResponse(
        string $wizardId,
        WizardStepInterface $step,
        array $stepData,
        WizardProgressValue $progress,
        WizardNavigationInterface $navigation,
        ?string $previousStepId,
        ?string $nextStepId
    ): array {
        return [
            'success' => true,
            'data' => [
                'wizard_id' => $wizardId,
                'step' => [
                    'id' => $step->getId(),
                    'title' => $step->getTitle(),
                    'order' => $step->getOrder(),
                    'is_optional' => $step->isOptional(),
                    'can_skip' => $step->canSkip(),
                ],
                'step_data' => $stepData,
                'progress' => [
                    'total_steps' => $progress->totalSteps,
                    'completed_steps' => $progress->completedSteps,
                    'current_step_position' => $progress->currentStepPosition,
                    'completion_percentage' => $progress->completionPercentage,
                    'is_complete' => $progress->isComplete,
                ],
                'navigation' => [
                    'can_go_back' => $navigation->canGoBack(),
                    'can_go_forward' => $navigation->canGoForward(),
                    'previous_step' => $previousStepId,
                    'next_step' => $nextStepId,
                    'items' => $navigation->getItems(),
                ],
            ],
        ];
    }

    public function buildStepEditResponse(
        string $wizardId,
        int $instanceId,
        WizardStepInterface $step,
        array $stepData,
        WizardProgressValue $progress
    ): array {
        return [
            'success' => true,
            'data' => [
                'wizard_id' => $wizardId,
                'instance_id' => $instanceId,
                'step' => [
                    'id' => $step->getId(),
                    'title' => $step->getTitle(),
                    'order' => $step->getOrder(),
                ],
                'step_data' => $stepData,
                'progress' => [
                    'completion_percentage' => $progress->completionPercentage,
                ],
                'is_edit_mode' => true,
            ],
        ];
    }
}
