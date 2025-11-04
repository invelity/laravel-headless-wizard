<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Invelity\WizardPackage\Contracts\WizardManagerInterface;
use Symfony\Component\HttpFoundation\Response;

final readonly class StepAccess
{
    public function __construct(
        private WizardManagerInterface $manager,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $wizard = $request->route('wizard');
        $step = $request->route('step');

        if ($wizard === null || $step === null) {
            return $next($request);
        }

        $this->manager->initialize($wizard);

        if (! $this->manager->canAccessStep($step)) {
            $currentStep = $this->manager->getCurrentStep();

            return redirect()->route('wizard.show', [
                'wizard' => $wizard,
                'step' => $currentStep?->getId() ?? $step,
            ])->with('error', __('You cannot access this step yet. Please complete previous steps first.'));
        }

        return $next($request);
    }
}
