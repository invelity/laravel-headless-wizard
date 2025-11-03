<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use WebSystem\WizardPackage\Contracts\WizardManagerInterface;
use WebSystem\WizardPackage\Contracts\WizardStorageInterface;
use WebSystem\WizardPackage\Core\WizardConfiguration;
use WebSystem\WizardPackage\Core\WizardManager;
use WebSystem\WizardPackage\Http\Middleware\StepAccess;
use WebSystem\WizardPackage\Http\Middleware\WizardSession;
use WebSystem\WizardPackage\Storage\CacheStorage;
use WebSystem\WizardPackage\Storage\DatabaseStorage;
use WebSystem\WizardPackage\Storage\SessionStorage;

class WizardPackageServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('wizard')
            ->hasConfigFile('wizard')
            ->hasRoute('web')
            ->hasMigration('create_wizard_progress_table')
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

        $this->app->singleton(WizardPackage::class, function ($app) {
            return new WizardPackage($app->make(WizardManagerInterface::class));
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
