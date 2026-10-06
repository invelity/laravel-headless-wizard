<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Tests;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Application;
use Invelity\WizardPackage\WizardServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * Get the package providers.
     *
     * @param  Application  $app
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            WizardServiceProvider::class,
        ];
    }

    /**
     * Define the environment.
     *
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        tap($app->make(Repository::class), function (Repository $config): void {
            $config->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
            $config->set('database.default', 'testing');
            $config->set('database.connections.testing', [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]);
        });
    }

    /**
     * Define the database migrations.
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
