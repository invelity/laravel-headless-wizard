<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\Actions;

use Illuminate\Http\JsonResponse;
use WebSystem\WizardPackage\Contracts\WizardManagerInterface;
use WebSystem\WizardPackage\Http\Responses\WizardJsonResponse;

final readonly class EditWizardStepAction
{
    public function __construct(
        private readonly WizardManagerInterface $manager,
    ) {}

    public function execute(string $wizard, int $wizardId, string $step): JsonResponse
    {
        $this->manager->loadFromStorage($wizard, $wizardId);

        if (! $this->manager->canAccessStep($step)) {
            return WizardJsonResponse::stepAccessDenied(
                $this->manager->getCurrentStep(),
                $step
            );
        }

        $stepInstance = $this->manager->getStep($step);
        $wizardData = $this->manager->getAllData();
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
                'step_data' => $wizardData[$step] ?? [],
                'progress' => [
                    'completion_percentage' => $progress->completionPercentage,
                ],
                'is_edit_mode' => true,
            ],
        ]);
    }
}
