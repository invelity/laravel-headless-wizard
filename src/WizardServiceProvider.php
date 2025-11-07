<?php

declare(strict_types=1);

namespace Invelity\WizardPackage;

use Illuminate\Contracts\Container\BindingResolutionException;
use Invelity\WizardPackage\Contracts\FormRequestValidatorInterface;
use Invelity\WizardPackage\Contracts\WizardManagerInterface;
use Invelity\WizardPackage\Contracts\WizardStorageInterface;
use Invelity\WizardPackage\Core\WizardConfiguration;
use Invelity\WizardPackage\Core\WizardManager;
use Invelity\WizardPackage\Http\Middleware\StepAccess;
use Invelity\WizardPackage\Http\Middleware\WizardSession;
use Invelity\WizardPackage\Services\Validation\FormRequestValidator;
use Invelity\WizardPackage\Services\WizardDiscoveryService;
use Invelity\WizardPackage\Storage\CacheStorage;
use Invelity\WizardPackage\Storage\DatabaseStorage;
use Invelity\WizardPackage\Storage\SessionStorage;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class WizardServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('wizard')
            ->hasConfigFile('wizard')
            ->hasRoute('web')
            ->hasMigration('create_wizard_progress_table')
            ->hasTranslations()
            ->hasViews('wizard-package')
            ->hasViewComponents(
                'wizard',
                Components\Layout::class,
                Components\ProgressBar::class,
                Components\StepNavigation::class,
                Components\FormWrapper::class
            )
            ->hasAssets()
            ->hasCommands([
                Commands\MakeStepCommand::class,
                Commands\MakeWizardCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(WizardConfiguration::class, function ($app) {
            return WizardConfiguration::fromConfig();
        });

        $this->app->singleton(WizardStorageInterface::class, function ($app) {
            $storageConfig = config('wizard.storage', 'session');
            $storage = is_array($storageConfig) ? ($storageConfig['driver'] ?? 'session') : $storageConfig;

            return match ($storage) {
                'database' => $app->make(DatabaseStorage::class),
                'cache' => $app->make(CacheStorage::class),
                default => $app->make(SessionStorage::class),
            };
        });

        // Register validation service
        $this->app->singleton(FormRequestValidatorInterface::class, FormRequestValidator::class);

        $this->app->singleton(WizardManagerInterface::class, WizardManager::class);

        $this->app->singleton(Wizard::class, function ($app) {
            return new Wizard($app->make(WizardManagerInterface::class));
        });

        // Register wizard discovery service
        $this->app->singleton(WizardDiscoveryService::class);
    }

    /**
     * @throws BindingResolutionException
     */
    public function packageBooted(): void
    {
        $this->registerMiddleware();
        $this->registerPublishableStubs();
        $this->registerDiscoveredWizards();
    }

    protected function registerPublishableStubs(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../resources/stubs' => base_path('stubs/vendor/wizard'),
            ], 'wizard-stubs');
        }
    }

    protected function registerMiddleware(): void
    {
        $this->app['router']->aliasMiddleware('wizard.session', WizardSession::class);
        $this->app['router']->aliasMiddleware('wizard.step-access', StepAccess::class);
    }

    /**
     * @throws BindingResolutionException
     */
    protected function registerDiscoveredWizards(): void
    {
        $discoveryService = $this->app->make(WizardDiscoveryService::class);
        $wizards = $discoveryService->discoverWizards();

        $wizardsConfig = [];

        $wizards->each(function ($wizard) use ($discoveryService, &$wizardsConfig) {
            $wizardClass = get_class($wizard);
            $steps = $discoveryService->discoverSteps($wizardClass);

            $wizardId = method_exists($wizard, 'getId')
                ? $wizard->getId()
                : str(class_basename($wizardClass))->kebab()->toString();

            $wizardsConfig[$wizardId] = [
                'class' => $wizardClass,
                'steps' => $steps->map(fn ($step) => get_class($step))->toArray(),
            ];
        });

        config(['wizard.wizards' => $wizardsConfig]);
    }
}
