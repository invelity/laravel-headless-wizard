<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Actions;

use Illuminate\Http\JsonResponse;
use Invelity\WizardPackage\Contracts\WizardManagerInterface;
use Invelity\WizardPackage\Http\Responses\WizardJsonResponse;

final readonly class UpdateWizardStepAction
{
    public function __construct(
        private readonly WizardManagerInterface $manager,
    ) {}

    public function execute(string $wizard, int $wizardId, string $step, array $data): JsonResponse
    {
        $this->manager->loadFromStorage($wizard, $wizardId);

        $result = $this->manager->processStep($step, $data);

        if (! $result->success) {
            return WizardJsonResponse::validationError($result->errors);
        }

        return WizardJsonResponse::stepUpdated($result);
    }
}
