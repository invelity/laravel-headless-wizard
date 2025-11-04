<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\Actions;

use Illuminate\Http\JsonResponse;
use WebSystem\WizardPackage\Contracts\WizardManagerInterface;
use WebSystem\WizardPackage\Http\Responses\WizardJsonResponse;

final readonly class ShowWizardStepAction
{
    public function __construct(
        private readonly WizardManagerInterface $manager,
    ) {}

    public function execute(string $wizard, string $step): JsonResponse
    {
        $this->manager->initialize($wizard);

        if (! $this->manager->canAccessStep($step)) {
            return WizardJsonResponse::stepAccessDenied(
                $this->manager->getCurrentStep(),
                $step
            );
        }

        $stepInstance = $this->manager->getStep($step);
        $wizardData = $this->manager->getAllData();
        $progress = $this->manager->getProgress();
        $navigation = $this->manager->getNavigation();

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
                    'can_go_back' => $navigation->canGoBack(),
                    'can_go_forward' => $navigation->canGoForward(),
                    'previous_step' => $this->manager->getPreviousStep()?->getId(),
                    'next_step' => $this->manager->getNextStep()?->getId(),
                    'items' => $navigation->getItems(),
                ],
            ],
        ]);
    }
}
