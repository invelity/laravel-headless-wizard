<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use WebSystem\WizardPackage\Contracts\WizardManagerInterface;
use WebSystem\WizardPackage\Exceptions\InvalidStepException;

class WizardStepSkipController extends Controller
{
    public function __construct(
        private readonly WizardManagerInterface $manager,
    ) {}

    public function __invoke(string $wizard, string $step): JsonResponse
    {
        $this->manager->initialize($wizard);

        try {
            $this->manager->skipStep($step);
        } catch (InvalidStepException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
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
            'message' => 'Step skipped successfully',
        ]);
    }
}
