<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Invelity\WizardPackage\WizardServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        if (method_exists(\Illuminate\Foundation\Bootstrap\HandleExceptions::class, 'flushState')) {
            try {
                \Illuminate\Foundation\Bootstrap\HandleExceptions::flushState();
            } catch (\TypeError $e) {
            }
        }

        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Invelity\\WizardPackage\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function resolveApplicationExceptionHandler($app)
    {
        $app->singleton(
            \Illuminate\Contracts\Debug\ExceptionHandler::class,
            \Illuminate\Foundation\Exceptions\Handler::class
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            WizardServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        config()->set('wizard.storage.driver', 'session');
        config()->set('wizard.navigation.allow_jump', false);
        config()->set('wizard.navigation.show_all_steps', true);
        config()->set('wizard.navigation.mark_completed', true);
        config()->set('wizard.validation.validate_on_navigate', true);
        config()->set('wizard.validation.allow_skip_optional', true);
        config()->set('wizard.events.fire_events', true);
        config()->set('wizard.routes.prefix', 'wizard');
        config()->set('wizard.routes.middleware', ['web', 'wizard.session']);

        config()->set('wizard.wizards.test-wizard', [
            'steps' => [
                \Invelity\WizardPackage\Tests\Fixtures\PersonalInfoStep::class,
                \Invelity\WizardPackage\Tests\Fixtures\ContactDetailsStep::class,
            ],
        ]);
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
