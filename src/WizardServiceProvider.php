<?php

declare(strict_types=1);

namespace Invelity\WizardPackage;

use Composer\InstalledVersions;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Support\ServiceProvider;
use Invelity\WizardPackage\Console\PruneCommand;
use Invelity\WizardPackage\Contracts\Factory;
use Invelity\WizardPackage\Exceptions\StepNotAccessibleException;
use Invelity\WizardPackage\Exceptions\StepNotFoundException;
use Invelity\WizardPackage\Exceptions\WizardAlreadyCompletedException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class WizardServiceProvider extends ServiceProvider
{
    /**
     * Register any package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/wizard.php', 'wizard');

        $this->app->singleton(StoreManager::class, fn (Application $app): StoreManager => new StoreManager($app));

        $this->app->singleton(WizardManager::class, fn (Application $app): WizardManager => new WizardManager($app, $app->make(StoreManager::class)));

        $this->app->alias(WizardManager::class, Factory::class);

        // Every wizard the container resolves, including those type-hinted on controllers and
        // jobs, is bound to the current visitor, the same way form requests are bound to the
        // current request.
        $this->app->resolving(Wizard::class, function (Wizard $wizard, Application $app): void {
            $app->make(WizardManager::class)->hydrate($wizard);
        });
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'wizard');

        $this->registerExceptionMapping();
        $this->registerOctaneListeners();

        if ($this->app->runningInConsole()) {
            $this->registerPublishing();
            $this->registerCommands();
        }
    }

    /**
     * Turn the package exceptions into HTTP responses with translated messages.
     */
    private function registerExceptionMapping(): void
    {
        $this->callAfterResolving(ExceptionHandler::class, function (ExceptionHandler $handler, Application $app): void {
            if (! $handler instanceof Handler) {
                return;
            }

            $message = function (string $key) use ($app): string {
                $message = $app->make(Translator::class)->get("wizard::messages.{$key}");

                return is_string($message) ? $message : $key;
            };

            $handler->map(fn (StepNotFoundException $e): NotFoundHttpException => new NotFoundHttpException($message('step_not_found'), $e));
            $handler->map(fn (StepNotAccessibleException $e): AccessDeniedHttpException => new AccessDeniedHttpException($message('step_not_accessible'), $e));
            $handler->map(fn (WizardAlreadyCompletedException $e): ConflictHttpException => new ConflictHttpException($message('already_completed'), $e));
        });
    }

    /**
     * Point the singleton managers at the application that serves the current request.
     *
     * Laravel Octane serves many requests with one booted application and hands every request
     * a sandbox copy, so the managers must resolve requests and sessions from the sandbox.
     */
    private function registerOctaneListeners(): void
    {
        $this->app->make(Dispatcher::class)->listen([
            'Laravel\Octane\Events\RequestReceived',
            'Laravel\Octane\Events\TaskReceived',
            'Laravel\Octane\Events\TickReceived',
        ], function (object $event): void {
            if (property_exists($event, 'sandbox') && $event->sandbox instanceof Application) {
                $this->app->make(StoreManager::class)->setApplication($event->sandbox);
                $this->app->make(WizardManager::class)->setContainer($event->sandbox);
            }
        });
    }

    /**
     * Register the files the application may publish.
     */
    private function registerPublishing(): void
    {
        $this->publishes([
            __DIR__.'/../config/wizard.php' => $this->app->configPath('wizard.php'),
        ], 'wizard-config');

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => $this->app->databasePath('migrations'),
        ], 'wizard-migrations');

        $this->publishes([
            __DIR__.'/../resources/lang' => $this->app->langPath('vendor/wizard'),
        ], 'wizard-translations');
    }

    /**
     * Register the console commands.
     */
    private function registerCommands(): void
    {
        $this->commands([
            PruneCommand::class,
        ]);

        AboutCommand::add('Wizard', fn (): array => [
            'Version' => InstalledVersions::getPrettyVersion('invelity/laravel-headless-wizard') ?? 'unknown',
            'Default store' => $this->app->make(StoreManager::class)->getDefaultInstance(),
        ]);
    }
}
