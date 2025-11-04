<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Invelity\WizardPackage\WizardServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    private $previousErrorHandler;

    private $previousExceptionHandler;

    protected function setUp(): void
    {
        $this->previousExceptionHandler = set_exception_handler(function ($e) {
            throw $e;
        });
        if ($this->previousExceptionHandler !== null) {
            set_exception_handler($this->previousExceptionHandler);
        } else {
            restore_exception_handler();
        }

        $this->previousErrorHandler = set_error_handler(function ($errno, $errstr, $errfile = null, $errline = null) {
            return false;
        });
        if ($this->previousErrorHandler !== null) {
            set_error_handler($this->previousErrorHandler);
        } else {
            restore_error_handler();
        }

        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Invelity\\WizardPackage\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function tearDown(): void
    {
        if ($this->previousExceptionHandler !== null) {
            set_exception_handler($this->previousExceptionHandler);
        } else {
            restore_exception_handler();
        }

        if ($this->previousErrorHandler !== null) {
            set_error_handler($this->previousErrorHandler);
        } else {
            restore_error_handler();
        }

        parent::tearDown();
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
        config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        config()->set('wizard.storage', 'session');
        config()->set('wizard.navigation.allow_jump', false);
        config()->set('wizard.navigation.show_all_steps', true);
        config()->set('wizard.navigation.mark_completed', true);
        config()->set('wizard.validation.validate_on_navigate', true);
        config()->set('wizard.validation.allow_skip_optional', true);
        config()->set('wizard.events.fire_events', true);
        config()->set('wizard.route.prefix', 'wizard');
        config()->set('wizard.route.middleware', ['web', 'wizard.session']);
    }
}
