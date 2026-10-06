<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Http\Controllers;

use Illuminate\Http\Request;
use Invelity\WizardPackage\Contracts\Factory;
use Invelity\WizardPackage\Http\Controllers\Concerns\ResolvesWizard;
use Invelity\WizardPackage\Http\Resources\WizardResource;

final class SkipStepController
{
    use ResolvesWizard;

    /**
     * Create a new controller instance.
     */
    public function __construct(
        private readonly Factory $wizards,
    ) {}

    /**
     * Skip the given optional step.
     */
    public function __invoke(Request $request, string $step): WizardResource
    {
        $wizard = $this->wizard($request, $this->wizards);

        $wizard->skip($step);

        return new WizardResource($wizard);
    }
}
