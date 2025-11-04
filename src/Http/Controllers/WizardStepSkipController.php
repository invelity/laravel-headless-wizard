<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Invelity\WizardPackage\Actions\SkipWizardStepAction;

class WizardStepSkipController extends Controller
{
    public function __construct(
        private readonly SkipWizardStepAction $skipAction,
    ) {}

    public function __invoke(string $wizard, string $step): JsonResponse
    {
        return $this->skipAction->execute($wizard, $step);
    }
}
