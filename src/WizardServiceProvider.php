<?php

declare(strict_types=1);

namespace Invelity\WizardPackage;

use Invelity\WizardPackage\Contracts\FormRequestValidatorInterface;
use Invelity\WizardPackage\Contracts\WizardManagerInterface;
use Invelity\WizardPackage\Contracts\WizardStorageInterface;
use Invelity\WizardPackage\Core\WizardConfiguration;
use Invelity\WizardPackage\Core\WizardManager;
use Invelity\WizardPackage\Http\Middleware\StepAccess;
use Invelity\WizardPackage\Http\Middleware\WizardSession;
use Invelity\WizardPackage\Services\Validation\FormRequestValidator;
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

        // Register validation service
        $this->app->singleton(FormRequestValidatorInterface::class, FormRequestValidator::class);

        $this->app->singleton(WizardManagerInterface::class, WizardManager::class);

        $this->app->singleton(Wizard::class, function ($app) {
            return new Wizard($app->make(WizardManagerInterface::class));
        });
    }

    public function packageBooted(): void
    {
        $this->registerMiddleware();
        $this->registerPublishableStubs();
        $this->discoverWizards();
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

    protected function discoverWizards(): void
    {
        $wizardsPath = app_path('Wizards');

        if (! is_dir($wizardsPath)) {
            return;
        }

        $wizards = [];
        $directories = glob($wizardsPath.'/*Wizard', GLOB_ONLYDIR);

        foreach ($directories as $wizardDir) {
            $wizardFolderName = basename($wizardDir);
            $wizardClassName = str_replace('Wizard', '', $wizardFolderName);
            $wizardFile = $wizardDir.'/'.$wizardClassName.'.php';

            if (! file_exists($wizardFile)) {
                continue;
            }

            $wizardClass = "App\\Wizards\\{$wizardFolderName}\\{$wizardClassName}";

            if (! class_exists($wizardClass)) {
                continue;
            }

            $steps = $this->discoverStepsForWizard($wizardDir);

            $wizardInstance = new $wizardClass;
            $wizardId = method_exists($wizardInstance, 'getId') ? $wizardInstance->getId() : str($wizardClassName)->kebab()->toString();

            $wizards[$wizardId] = [
                'class' => $wizardClass,
                'steps' => $steps,
            ];
        }

        config(['wizard.wizards' => $wizards]);
    }

    protected function discoverStepsForWizard(string $wizardDir): array
    {
        $stepsDir = $wizardDir.'/Steps';

        if (! is_dir($stepsDir)) {
            return [];
        }

        $stepFiles = glob($stepsDir.'/*Step.php');
        $steps = [];

        foreach ($stepFiles as $stepFile) {
            $stepClassName = basename($stepFile, '.php');
            $wizardFolderName = basename($wizardDir);
            $stepClass = "App\\Wizards\\{$wizardFolderName}\\Steps\\{$stepClassName}";

            if (class_exists($stepClass)) {
                $steps[] = $stepClass;
            }
        }

        return $steps;
    }
}
