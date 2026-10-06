<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Contracts;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Invelity\WizardPackage\Wizard as WizardDefinition;

interface Factory
{
    /**
     * Get a wizard bound to the current visitor, or to the given scope.
     *
     * @template TWizard of WizardDefinition
     *
     * @param  class-string<TWizard>|TWizard  $wizard
     * @return TWizard
     */
    public function for(string|WizardDefinition $wizard, mixed $scope = null): WizardDefinition;

    /**
     * Get a wizard store by name, or the default store.
     */
    public function store(?string $name = null): Store;

    /**
     * Register a custom store driver.
     *
     * @param  Closure(Application, array<string, mixed>): Store  $callback
     */
    public function extend(string $driver, Closure $callback): static;

    /**
     * Resolve the scope of the current visitor with the given callback.
     *
     * @param  (Closure(Request): mixed)|null  $resolver
     */
    public function resolveScopeUsing(?Closure $resolver): static;

    /**
     * Resolve the URL of a step with the given callback.
     *
     * @param  (Closure(WizardDefinition, Step): ?string)|null  $resolver
     */
    public function resolveUrlsUsing(?Closure $resolver): static;
}
