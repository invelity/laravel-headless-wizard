<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Http\Responses;

use Illuminate\Http\JsonResponse;
use Invelity\WizardPackage\Contracts\WizardStepInterface;
use Invelity\WizardPackage\ValueObjects\StepResult;
use Invelity\WizardPackage\ValueObjects\WizardProgressValue;

final class WizardJsonResponse
{
    public static function stepAccessDenied(?WizardStepInterface $currentStep, string $requestedStep): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => __('Cannot access this step'),
            'redirect_to' => $currentStep?->getId() ?? $requestedStep,
        ], 403);
    }

    public static function validationError(array $errors): JsonResponse
    {
        return response()->json([
            'success' => false,
            'errors' => $errors,
        ], 422);
    }

    public static function stepProcessed(StepResult $result, ?WizardStepInterface $nextStep, WizardProgressValue $progress): JsonResponse
    {
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
            'message' => $result->message ?? __('Step completed successfully'),
        ]);
    }

    public static function stepUpdated(StepResult $result): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $result->data,
            'message' => $result->message ?? __('Step updated successfully'),
        ]);
    }

    public static function deleted(?string $message = null): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message ?? __('Wizard deleted successfully'),
        ]);
    }

    public static function completed(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => __('Wizard completed successfully'),
        ]);
    }

    public static function stepSkipped(?WizardStepInterface $nextStep, WizardProgressValue $progress): JsonResponse
    {
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
            'message' => __('Step skipped successfully'),
        ]);
    }
}
