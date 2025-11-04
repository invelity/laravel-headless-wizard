<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\Actions;

use Illuminate\Http\JsonResponse;
use WebSystem\WizardPackage\Contracts\WizardManagerInterface;
use WebSystem\WizardPackage\Http\Responses\WizardJsonResponse;

final readonly class SkipWizardStepAction
{
    public function __construct(
        private readonly WizardManagerInterface $manager,
    ) {}

    public function execute(string $wizard, string $step): JsonResponse
    {
        $this->manager->initialize($wizard);

        $this->manager->skipStep($step);

        return WizardJsonResponse::stepSkipped(
            $this->manager->getNextStep(),
            $this->manager->getProgress()
        );
    }
}
