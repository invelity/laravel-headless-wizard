<?php

declare(strict_types=1);

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Session\Store as LaravelSession;
use Invelity\WizardPackage\Contracts\Store;
use Invelity\WizardPackage\StoreManager;
use Invelity\WizardPackage\Stores\ArrayStore;
use Invelity\WizardPackage\Stores\CacheStore;
use Invelity\WizardPackage\Stores\DatabaseStore;
use Invelity\WizardPackage\Stores\SessionStore;

it('resolves the configured stores', function () {
    $stores = app(StoreManager::class);

    expect($stores->store('session'))->toBeInstanceOf(SessionStore::class)
        ->and($stores->store('cache'))->toBeInstanceOf(CacheStore::class)
        ->and($stores->store('database'))->toBeInstanceOf(DatabaseStore::class)
        ->and($stores->store('array'))->toBeInstanceOf(ArrayStore::class);
});

it('resolves the default store', function () {
    $stores = app(StoreManager::class);

    expect($stores->getDefaultInstance())->toBe('session')
        ->and($stores->store())->toBeInstanceOf(SessionStore::class);

    $stores->setDefaultInstance('array');

    expect(config('wizard.default'))->toBe('array')
        ->and($stores->store())->toBeInstanceOf(ArrayStore::class);
});

it('reuses resolved stores', function () {
    $stores = app(StoreManager::class);

    expect($stores->store('array'))->toBe($stores->store('array'))
        ->and(app(StoreManager::class))->toBe($stores);
});

it('fails for a store that is not configured', function () {
    app(StoreManager::class)->store('redis');
})->throws(InvalidArgumentException::class, 'Instance [redis] is not defined.');

it('accepts custom drivers', function () {
    config(['wizard.stores.custom' => ['driver' => 'memory', 'label' => 'custom']]);

    $store = new ArrayStore;

    app(StoreManager::class)->extend('memory', function (Application $app, array $config) use ($store): Store {
        expect($config['label'])->toBe('custom');

        return $store;
    });

    expect(app(StoreManager::class)->store('custom'))->toBe($store);
});

it('rejects custom drivers that do not return a store', function () {
    config(['wizard.stores.custom' => ['driver' => 'broken']]);

    app(StoreManager::class)->extend('broken', fn () => new stdClass);

    app(StoreManager::class)->store('custom');
})->throws(InvalidArgumentException::class, 'Wizard store [custom] must implement');

it('follows the application Octane hands to each request', function () {
    $store = app(StoreManager::class)->store('session');

    $session = tap(new LaravelSession('sandbox', app('session')->getHandler()))->start();
    $request = Request::create('/');
    $request->setLaravelSession($session);

    $sandbox = clone app();
    $sandbox->instance('request', $request);

    event('Laravel\Octane\Events\RequestReceived', [(object) ['sandbox' => $sandbox]]);

    $store->put('order', 'ignored', ['current_step' => 'summary']);

    expect($session->get('wizard_order'))->toBe(['current_step' => 'summary']);
});
