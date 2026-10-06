<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Invelity\WizardPackage\Contracts\Factory;
use Invelity\WizardPackage\Exceptions\InvalidWizardException;
use Invelity\WizardPackage\Wizard;

trait ResolvesWizard
{
    /**
     * Get the wizard of the current route, bound to the current visitor.
     *
     * Route::wizard() stores the wizard class as the "wizard" default of every route it registers.
     *
     * @throws InvalidWizardException
     */
    private function wizard(Request $request, Factory $wizards): Wizard
    {
        $class = $request->route()?->defaults['wizard'] ?? null;

        if (! is_string($class) || ! is_subclass_of($class, Wizard::class)) {
            throw InvalidWizardException::notAWizard(is_string($class) ? $class : 'null');
        }

        return $wizards->for($class);
    }
}
