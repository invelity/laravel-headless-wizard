<?php

declare(strict_types=1);

namespace Invelity\WizardPackage;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Invelity\WizardPackage\Contracts\WizardManagerInterface;
use Invelity\WizardPackage\Contracts\WizardStorageInterface;
use Invelity\WizardPackage\Core\WizardConfiguration;
use Invelity\WizardPackage\Core\WizardManager;
use Invelity\WizardPackage\Http\Middleware\StepAccess;
use Invelity\WizardPackage\Http\Middleware\WizardSession;
use Invelity\WizardPackage\Storage\CacheStorage;
use Invelity\WizardPackage\Storage\DatabaseStorage;
use Invelity\WizardPackage\Storage\SessionStorage;

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
            $storage = config('wizard.storage', 'session');

            return match ($storage) {
                'database' => $app->make(DatabaseStorage::class),
                'cache' => $app->make(CacheStorage::class),
                default => $app->make(SessionStorage::class),
            };
        });

        $this->app->singleton(WizardManagerInterface::class, WizardManager::class);

        $this->app->singleton(Wizard::class, function ($app) {
            return new Wizard($app->make(WizardManagerInterface::class));
        });
    }

    public function packageBooted(): void
    {
        $this->registerMiddleware();
        $this->registerPublishableStubs();
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
}
