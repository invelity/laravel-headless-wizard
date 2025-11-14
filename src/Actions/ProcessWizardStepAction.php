<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Actions;

use Illuminate\Http\JsonResponse;
use Invelity\WizardPackage\Contracts\WizardDataInterface;
use Invelity\WizardPackage\Contracts\WizardInitializationInterface;
use Invelity\WizardPackage\Contracts\WizardNavigationManagerInterface;
use Invelity\WizardPackage\Http\Responses\WizardJsonResponse;

final readonly class ProcessWizardStepAction
{
    public function __construct(
        private readonly WizardInitializationInterface $initialization,
        private readonly WizardDataInterface $data,
        private readonly WizardNavigationManagerInterface $navigation,
    ) {}

    public function execute(string $wizard, string $step, array $data): JsonResponse
    {
        $this->initialization->initialize($wizard);

        $result = $this->data->processStep($step, $data);

        if (! $result->success) {
            return WizardJsonResponse::validationError($result->errors);
        }

        return WizardJsonResponse::stepProcessed(
            $result,
            $this->navigation->getNextStep(),
            $this->data->getProgress()
        );
    }
}
