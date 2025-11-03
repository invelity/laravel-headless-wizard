<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use WebSystem\WizardPackage\Contracts\WizardManagerInterface;
use WebSystem\WizardPackage\Exceptions\StepValidationException;

class WizardController extends Controller
{
    public function __construct(
        private readonly WizardManagerInterface $manager,
    ) {}

    public function show(string $wizard, string $step): JsonResponse
    {
        $this->manager->initialize($wizard);

        if (! $this->manager->canAccessStep($step)) {
            $currentStep = $this->manager->getCurrentStep();

            return response()->json([
                'success' => false,
                'error' => 'Cannot access this step',
                'redirect_to' => $currentStep?->getId() ?? $step,
            ], 403);
        }

        $stepInstance = $this->manager->getStep($step);
        $wizardData = $this->manager->getAllData();
        $stepData = $wizardData[$step] ?? [];
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
                    'previous_step' => $this->manager->getPreviousStep()?->getId(),
                    'next_step' => $this->manager->getNextStep()?->getId(),
                    'items' => $navigation->getItems(),
                ],
            ],
        ]);
    }

    public function store(Request $request, string $wizard, string $step): JsonResponse
    {
        $this->manager->initialize($wizard);

        try {
            $result = $this->manager->processStep($step, $request->all());

            if (! $result->success) {
                return response()->json([
                    'success' => false,
                    'errors' => $result->errors,
                ], 422);
            }

            $nextStep = $this->manager->getNextStep();
            $progress = $this->manager->getProgress();

            return response()->json([
                'success' => true,
                'data' => [
                    'next_step' => $nextStep?->getId(),
                    'is_completed' => $nextStep === null,
                    'progress' => [
                        'completion_percentage' => $progress->completionPercentage,
                        'is_complete' => $progress->isComplete,
                    ],
                ],
                'message' => $result->message ?? 'Step completed successfully',
            ]);
        } catch (StepValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->getErrors(),
            ], 422);
        }
    }

    public function edit(string $wizard, int $wizardId, string $step): JsonResponse
    {
        $this->manager->loadFromStorage($wizard, $wizardId);

        if (! $this->manager->canAccessStep($step)) {
            $currentStep = $this->manager->getCurrentStep();

            return response()->json([
                'success' => false,
                'error' => 'Cannot access this step',
                'redirect_to' => $currentStep?->getId() ?? $step,
            ], 403);
        }

        $stepInstance = $this->manager->getStep($step);
        $wizardData = $this->manager->getAllData();
        $stepData = $wizardData[$step] ?? [];
        $progress = $this->manager->getProgress();

        return response()->json([
            'success' => true,
            'data' => [
                'wizard_id' => $wizard,
                'instance_id' => $wizardId,
                'step' => [
                    'id' => $stepInstance->getId(),
                    'title' => $stepInstance->getTitle(),
                    'order' => $stepInstance->getOrder(),
                ],
                'step_data' => $stepData,
                'progress' => [
                    'completion_percentage' => $progress->completionPercentage,
                ],
                'is_edit_mode' => true,
            ],
        ]);
    }

    public function update(Request $request, string $wizard, int $wizardId, string $step): JsonResponse
    {
        $this->manager->loadFromStorage($wizard, $wizardId);

        try {
            $result = $this->manager->processStep($step, $request->all());

            if (! $result->success) {
                return response()->json([
                    'success' => false,
                    'errors' => $result->errors,
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data' => $result->data,
                'message' => $result->message ?? 'Step updated successfully',
            ]);
        } catch (StepValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->getErrors(),
            ], 422);
        }
    }

    public function destroy(string $wizard, int $wizardId): JsonResponse
    {
        $this->manager->deleteWizard($wizard, $wizardId);

        return response()->json([
            'success' => true,
            'message' => 'Wizard deleted successfully',
        ]);
    }
}
