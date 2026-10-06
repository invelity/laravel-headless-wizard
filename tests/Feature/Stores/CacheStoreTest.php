<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Invelity\WizardPackage\StoreManager;

beforeEach(function () {
    config(['wizard.stores.cache.store' => 'array']);
});

it('expires the state after the configured time to live', function () {
    config(['wizard.stores.cache.ttl' => 60]);

    $store = app(StoreManager::class)->store('cache');
    $store->put('order', 'visitor-1', ['current_step' => 'summary']);

    $this->travel(59)->seconds();
    expect($store->get('order', 'visitor-1'))->not->toBeNull();

    $this->travel(2)->seconds();
    expect($store->get('order', 'visitor-1'))->toBeNull();
});

it('keeps the state forever without a time to live', function () {
    config(['wizard.stores.cache.ttl' => null]);

    $store = app(StoreManager::class)->store('cache');
    $store->put('order', 'visitor-1', ['current_step' => 'summary']);

    $this->travel(10)->years();

    expect($store->get('order', 'visitor-1'))->toBe(['current_step' => 'summary']);
});

it('prefixes the key and hashes the scope', function () {
    app(StoreManager::class)->store('cache')->put('order', 'App\Models\User|42', ['current_step' => 'summary']);

    expect(Cache::store('array')->get('wizard:order:'.hash('xxh128', 'App\Models\User|42')))->toBe(['current_step' => 'summary']);
});
