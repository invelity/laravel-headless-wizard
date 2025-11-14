<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Actions;

use Illuminate\Http\JsonResponse;
use Invelity\WizardPackage\Contracts\WizardDataInterface;
use Invelity\WizardPackage\Contracts\WizardInitializationInterface;
use Invelity\WizardPackage\Http\Responses\WizardJsonResponse;

final readonly class UpdateWizardStepAction
{
    public function __construct(
        private readonly WizardInitializationInterface $initialization,
        private readonly WizardDataInterface $data,
    ) {}

    public function execute(string $wizard, int $wizardId, string $step, array $data): JsonResponse
    {
        $this->initialization->loadFromStorage($wizard, $wizardId);

        $result = $this->data->processStep($step, $data);

        if (! $result->success) {
            return WizardJsonResponse::validationError($result->errors);
        }

        return WizardJsonResponse::stepUpdated($result);
    }
}
