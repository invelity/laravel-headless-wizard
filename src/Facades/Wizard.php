<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Facades;

use Illuminate\Support\Facades\Facade;
use Invelity\WizardPackage\Contracts\Factory;
use Invelity\WizardPackage\WizardManager;

/**
 * @method static TWizard for<TWizard of \Invelity\WizardPackage\Wizard>(class-string<TWizard>|TWizard $wizard, mixed $scope = null)
 * @method static \Invelity\WizardPackage\Contracts\Store store(string|null $name = null)
 * @method static \Invelity\WizardPackage\WizardManager extend(string $driver, \Closure $callback)
 * @method static \Invelity\WizardPackage\WizardManager resolveScopeUsing(\Closure|null $resolver)
 * @method static \Invelity\WizardPackage\WizardManager resolveUrlsUsing(\Closure|null $resolver)
 * @method static string normalizeScope(mixed $scope)
 *
 * @see WizardManager
 */
class Wizard extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return Factory::class;
    }
}
