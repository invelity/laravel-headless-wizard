<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Http\Controllers;

use Illuminate\Http\Request;
use Invelity\WizardPackage\Contracts\Factory;
use Invelity\WizardPackage\Exceptions\StepNotAccessibleException;
use Invelity\WizardPackage\Http\Controllers\Concerns\ResolvesWizard;
use Invelity\WizardPackage\Http\Resources\WizardResource;

final class StepController
{
    use ResolvesWizard;

    /**
     * Create a new controller instance.
     */
    public function __construct(
        private readonly Factory $wizards,
    ) {}

    /**
     * Show the wizard as seen from the given step, with the step's stored data.
     *
     * @throws StepNotAccessibleException
     */
    public function show(Request $request, string $step): WizardResource
    {
        $wizard = $this->wizard($request, $this->wizards);

        if (! $wizard->canAccess($step)) {
            throw new StepNotAccessibleException($wizard->name(), $step, $wizard->current()?->id());
        }

        return new WizardResource($wizard, $step);
    }

    /**
     * Validate and store the input of the given step.
     */
    public function store(Request $request, string $step): WizardResource
    {
        $wizard = $this->wizard($request, $this->wizards);

        $wizard->process($step, $request);

        return new WizardResource($wizard);
    }
}
