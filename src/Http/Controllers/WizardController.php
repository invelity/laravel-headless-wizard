<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Invelity\WizardPackage\Contracts\Factory;
use Invelity\WizardPackage\Http\Controllers\Concerns\ResolvesWizard;
use Invelity\WizardPackage\Http\Resources\WizardResource;

final class WizardController
{
    use ResolvesWizard;

    /**
     * Create a new controller instance.
     */
    public function __construct(
        private readonly Factory $wizards,
    ) {}

    /**
     * Show the wizard as seen from the step the visitor is on.
     */
    public function show(Request $request): WizardResource
    {
        return new WizardResource($this->wizard($request, $this->wizards));
    }

    /**
     * Complete the wizard.
     */
    public function store(Request $request): WizardResource
    {
        $wizard = $this->wizard($request, $this->wizards);

        $wizard->complete();

        return new WizardResource($wizard);
    }

    /**
     * Remove the stored state of the wizard.
     */
    public function destroy(Request $request): Response
    {
        $this->wizard($request, $this->wizards)->reset();

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
