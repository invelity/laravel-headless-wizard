<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Actions;

use Illuminate\Http\JsonResponse;
use Invelity\WizardPackage\Contracts\WizardDataInterface;
use Invelity\WizardPackage\Contracts\WizardInitializationInterface;
use Invelity\WizardPackage\Contracts\WizardNavigationManagerInterface;
use Invelity\WizardPackage\Http\Responses\WizardJsonResponse;

final readonly class SkipWizardStepAction
{
    public function __construct(
        private readonly WizardInitializationInterface $initialization,
        private readonly WizardDataInterface $data,
        private readonly WizardNavigationManagerInterface $navigation,
    ) {}

    public function execute(string $wizard, string $step): JsonResponse
    {
        $this->initialization->initialize($wizard);

        $this->data->skipStep($step);

        return WizardJsonResponse::stepSkipped(
            $this->navigation->getNextStep(),
            $this->data->getProgress()
        );
    }
}
