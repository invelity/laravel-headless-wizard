<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Actions;

use Illuminate\Http\JsonResponse;
use Invelity\WizardPackage\Contracts\WizardDataInterface;
use Invelity\WizardPackage\Contracts\WizardInitializationInterface;
use Invelity\WizardPackage\Contracts\WizardNavigationManagerInterface;
use Invelity\WizardPackage\Contracts\WizardStepAccessInterface;
use Invelity\WizardPackage\Http\Responses\WizardJsonResponse;

final readonly class ShowWizardStepAction
{
    public function __construct(
        private readonly WizardInitializationInterface $initialization,
        private readonly WizardStepAccessInterface $stepAccess,
        private readonly WizardDataInterface $data,
        private readonly WizardNavigationManagerInterface $navigation,
    ) {}

    public function execute(string $wizard, string $step): JsonResponse
    {
        $this->initialization->initialize($wizard);

        if (! $this->stepAccess->canAccessStep($step)) {
            return WizardJsonResponse::stepAccessDenied(
                $this->stepAccess->getCurrentStep(),
                $step
            );
        }

        $stepInstance = $this->stepAccess->getStep($step);
        $wizardData = $this->data->getAllData();
        $progress = $this->data->getProgress();
        $navigationInstance = $this->navigation->getNavigation();

        return response()->json([
            'success' => true,
            'data' => [
                'wizard_id' => $wizard,
                'step' => [
                    'id' => $stepInstance->getId(),
                    'title' => $stepInstance->getTitle(),
                    'order' => $stepInstance->getOrder(),
                    'is_optional' => $stepInstance->isOptional(),
                    'can_skip' => $stepInstance->canSkip(),
                ],
                'step_data' => $wizardData[$step] ?? [],
                'progress' => [
                    'total_steps' => $progress->totalSteps,
                    'completed_steps' => $progress->completedSteps,
                    'current_step_position' => $progress->currentStepPosition,
                    'completion_percentage' => $progress->completionPercentage,
                    'is_complete' => $progress->isComplete,
                ],
                'navigation' => [
                    'can_go_back' => $navigationInstance->canGoBack(),
                    'can_go_forward' => $navigationInstance->canGoForward(),
                    'previous_step' => $this->navigation->getPreviousStep()?->getId(),
                    'next_step' => $this->navigation->getNextStep()?->getId(),
                    'items' => $navigationInstance->getItems(),
                ],
            ],
        ]);
    }
}
