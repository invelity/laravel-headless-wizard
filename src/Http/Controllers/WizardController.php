<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Invelity\WizardPackage\Actions\EditWizardStepAction;
use Invelity\WizardPackage\Actions\ProcessWizardStepAction;
use Invelity\WizardPackage\Actions\ShowWizardStepAction;
use Invelity\WizardPackage\Actions\UpdateWizardStepAction;
use Invelity\WizardPackage\Contracts\WizardManagerInterface;
use Invelity\WizardPackage\Http\Responses\WizardJsonResponse;

class WizardController extends Controller
{
    public function __construct(
        private readonly ShowWizardStepAction $showAction,
        private readonly ProcessWizardStepAction $processAction,
        private readonly EditWizardStepAction $editAction,
        private readonly UpdateWizardStepAction $updateAction,
        private readonly WizardManagerInterface $manager,
    ) {}

    public function show(string $wizard, string $step): JsonResponse
    {
        return $this->showAction->execute($wizard, $step);
    }

    public function store(Request $request, string $wizard, string $step): JsonResponse
    {
        return $this->processAction->execute($wizard, $step, $request->all());
    }

    public function edit(string $wizard, int $wizardId, string $step): JsonResponse
    {
        return $this->editAction->execute($wizard, $wizardId, $step);
    }

    public function update(Request $request, string $wizard, int $wizardId, string $step): JsonResponse
    {
        return $this->updateAction->execute($wizard, $wizardId, $step, $request->all());
    }

    public function destroy(string $wizard, int $wizardId): JsonResponse
    {
        $this->manager->deleteWizard($wizard, $wizardId);

        return WizardJsonResponse::deleted();
    }
}
