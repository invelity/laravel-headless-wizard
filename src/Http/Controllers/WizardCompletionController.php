<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use WebSystem\WizardPackage\Actions\CompleteWizardAction;

class WizardCompletionController extends Controller
{
    public function __construct(
        private readonly CompleteWizardAction $completeAction,
    ) {}

    public function __invoke(string $wizard): JsonResponse
    {
        return $this->completeAction->execute($wizard);
    }
}
