<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Actions;

use Illuminate\Http\JsonResponse;
use Invelity\WizardPackage\Contracts\WizardDataInterface;
use Invelity\WizardPackage\Contracts\WizardInitializationInterface;
use Invelity\WizardPackage\Contracts\WizardNavigationManagerInterface;
use Invelity\WizardPackage\Contracts\WizardStepAccessInterface;
use Invelity\WizardPackage\Http\Responses\WizardJsonResponse;
use Invelity\WizardPackage\Http\Responses\WizardStepResponseBuilder;

final readonly class ShowWizardStepAction
{
    public function __construct(
        private readonly WizardInitializationInterface $initialization,
        private readonly WizardStepAccessInterface $stepAccess,
        private readonly WizardDataInterface $data,
        private readonly WizardNavigationManagerInterface $navigation,
        private readonly WizardStepResponseBuilder $responseBuilder,
    ) {}

    public function execute(string $wizard, string $step): JsonResponse
    {
        $this->initialization->initialize($wizard);

        if (! $this->stepAccess->canAccessStep($step)) {
            return WizardJsonResponse::stepAccessDenied(
                $this->stepAccess->getCurrentStep(),
                $step
            );
        }

        $stepInstance = $this->stepAccess->getStep($step);
        $wizardData = $this->data->getAllData();
        $progress = $this->data->getProgress();
        $navigationInstance = $this->navigation->getNavigation();

        return response()->json(
            $this->responseBuilder->buildStepShowResponse(
                wizardId: $wizard,
                step: $stepInstance,
                stepData: $wizardData[$step] ?? [],
                progress: $progress,
                navigation: $navigationInstance,
                previousStepId: $this->navigation->getPreviousStep()?->getId(),
                nextStepId: $this->navigation->getNextStep()?->getId()
            )
        );
    }
}
