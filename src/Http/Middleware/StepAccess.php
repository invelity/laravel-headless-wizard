<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use WebSystem\WizardPackage\Contracts\WizardManagerInterface;

class StepAccess
{
    public function __construct(
        private readonly WizardManagerInterface $manager,
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
            ])->with('error', 'You cannot access this step yet. Please complete previous steps first.');
        }

        return $next($request);
    }
}
