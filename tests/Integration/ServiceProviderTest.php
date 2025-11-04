<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Tests\Integration;

use Invelity\WizardPackage\Contracts\WizardManagerInterface;
use Invelity\WizardPackage\Contracts\WizardStorageInterface;
use Invelity\WizardPackage\Core\WizardConfiguration;
use Invelity\WizardPackage\Storage\SessionStorage;
use Invelity\WizardPackage\Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    /** @test */
    public function it_registers_wizard_configuration_as_singleton(): void
    {
        $config1 = app(WizardConfiguration::class);
        $config2 = app(WizardConfiguration::class);

        expect($config1)
            ->toBe($config2)
            ->toBeInstanceOf(WizardConfiguration::class);
    }

    /** @test */
    public function it_registers_wizard_storage_based_on_config(): void
    {
        config(['wizard.storage' => 'session']);

        $storage = app(WizardStorageInterface::class);

        expect($storage)->toBeInstanceOf(SessionStorage::class);
    }

    /** @test */
    public function it_registers_wizard_manager_as_singleton(): void
    {
        $manager1 = app(WizardManagerInterface::class);
        $manager2 = app(WizardManagerInterface::class);

        expect($manager1)
            ->toBe($manager2)
            ->toBeInstanceOf(WizardManagerInterface::class);
    }

    /** @test */
    public function it_registers_middleware_aliases(): void
    {
        $router = app('router');

        expect($router->hasMiddlewareGroup('wizard.session'))->toBeTrue()
            ->and($router->hasMiddlewareGroup('wizard.step-access'))->toBeTrue();
    }

    /** @test */
    public function config_file_is_published(): void
    {
        expect(config('wizard.storage'))->not->toBeNull();
    }

    /** @test */
    public function views_are_registered(): void
    {
        $viewFinder = app('view')->getFinder();
        $hints = $viewFinder->getHints();

        expect($hints)->toHaveKey('wizard');
    }
}
