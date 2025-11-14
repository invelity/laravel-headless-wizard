<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Actions;

use Illuminate\Http\JsonResponse;
use Invelity\WizardPackage\Contracts\WizardDataInterface;
use Invelity\WizardPackage\Contracts\WizardInitializationInterface;
use Invelity\WizardPackage\Contracts\WizardStepAccessInterface;
use Invelity\WizardPackage\Http\Responses\WizardJsonResponse;

final readonly class EditWizardStepAction
{
    public function __construct(
        private readonly WizardInitializationInterface $initialization,
        private readonly WizardStepAccessInterface $stepAccess,
        private readonly WizardDataInterface $data,
    ) {}

    public function execute(string $wizard, int $wizardId, string $step): JsonResponse
    {
        $this->initialization->loadFromStorage($wizard, $wizardId);

        if (! $this->stepAccess->canAccessStep($step)) {
            return WizardJsonResponse::stepAccessDenied(
                $this->stepAccess->getCurrentStep(),
                $step
            );
        }

        $stepInstance = $this->stepAccess->getStep($step);
        $wizardData = $this->data->getAllData();
        $progress = $this->data->getProgress();

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
