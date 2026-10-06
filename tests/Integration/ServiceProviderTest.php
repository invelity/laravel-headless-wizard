<?php

declare(strict_types=1);

use Invelity\WizardPackage\Contracts\WizardManagerInterface;
use Invelity\WizardPackage\Contracts\WizardStorageInterface;
use Invelity\WizardPackage\Core\WizardConfiguration;
use Invelity\WizardPackage\Storage\CacheStorage;
use Invelity\WizardPackage\Storage\DatabaseStorage;
use Invelity\WizardPackage\Storage\SessionStorage;
use Invelity\WizardPackage\Wizard;

test('it registers wizard configuration as singleton', function () {
    $config1 = app(WizardConfiguration::class);
    $config2 = app(WizardConfiguration::class);

    expect($config1)
        ->toBe($config2)
        ->toBeInstanceOf(WizardConfiguration::class);
});

test('it registers wizard storage based on config', function () {
    config(['wizard.storage' => 'session']);

    $storage = app(WizardStorageInterface::class);

    expect($storage)->toBeInstanceOf(SessionStorage::class);
});

test('it registers wizard manager as singleton', function () {
    $manager1 = app(WizardManagerInterface::class);
    $manager2 = app(WizardManagerInterface::class);

    expect($manager1)
        ->toBe($manager2)
        ->toBeInstanceOf(WizardManagerInterface::class);
});

test('it registers middleware aliases', function () {
    $router = app('router');
    $reflection = new ReflectionClass($router);
    $property = $reflection->getProperty('middleware');
    $property->setAccessible(true);
    $middleware = $property->getValue($router);

    expect($middleware)->toHaveKey('wizard.session')
        ->and($middleware)->toHaveKey('wizard.step-access');
});

test('config file is published', function () {
    expect(config('wizard.storage'))->not->toBeNull();
});

test('it registers database storage when configured', function () {
    config(['wizard.storage' => 'database']);
    $this->app->forgetInstance(WizardStorageInterface::class);

    $storage = app(WizardStorageInterface::class);

    expect($storage)->toBeInstanceOf(DatabaseStorage::class);
});

test('it registers cache storage when configured', function () {
    config(['wizard.storage' => 'cache']);
    $this->app->forgetInstance(WizardStorageInterface::class);

    $storage = app(WizardStorageInterface::class);

    expect($storage)->toBeInstanceOf(CacheStorage::class);
});

test('it registers Wizard facade singleton', function () {
    $wizard1 = app(Wizard::class);
    $wizard2 = app(Wizard::class);

    expect($wizard1)->toBe($wizard2);
});
