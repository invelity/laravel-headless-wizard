<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Actions;

use Illuminate\Http\JsonResponse;
use Invelity\WizardPackage\Contracts\WizardManagerInterface;
use Invelity\WizardPackage\Http\Responses\WizardJsonResponse;

final readonly class ProcessWizardStepAction
{
    public function __construct(
        private readonly WizardManagerInterface $manager,
    ) {}

    public function execute(string $wizard, string $step, array $data): JsonResponse
    {
        $this->manager->initialize($wizard);

        $result = $this->manager->processStep($step, $data);

        if (! $result->success) {
            return WizardJsonResponse::validationError($result->errors);
        }

        return WizardJsonResponse::stepProcessed(
            $result,
            $this->manager->getNextStep(),
            $this->manager->getProgress()
        );
    }
}
