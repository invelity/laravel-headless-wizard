<?php

declare(strict_types=1);

use Invelity\WizardPackage\Contracts\Store;
use Invelity\WizardPackage\StoreManager;

dataset('stores', [
    'array' => ['array', true],
    'session' => ['session', false],
    'cache' => ['cache', true],
    'database' => ['database', true],
]);

beforeEach(function () {
    config(['wizard.stores.cache.store' => 'array']);

    $this->store = fn (string $name): Store => app(StoreManager::class)->store($name);
});

it('returns null when nothing is stored', function (string $name) {
    expect(($this->store)($name)->get('order', 'visitor-1'))->toBeNull();
})->with('stores');

it('returns the state it stored', function (string $name) {
    $state = [
        'current_step' => 'summary',
        'steps' => ['calculator' => ['weight' => 2.5, 'note' => 'Krehké – nepreklápať']],
        'metadata' => ['nested' => ['list' => [1, 2, 3]], 'flag' => true, 'nothing' => null],
    ];

    $store = ($this->store)($name);
    $store->put('order', 'visitor-1', $state);

    expect($store->get('order', 'visitor-1'))->toBe($state);
})->with('stores');

it('overwrites the stored state', function (string $name) {
    $store = ($this->store)($name);
    $store->put('order', 'visitor-1', ['current_step' => 'calculator']);
    $store->put('order', 'visitor-1', ['current_step' => 'summary']);

    expect($store->get('order', 'visitor-1'))->toBe(['current_step' => 'summary']);
})->with('stores');

it('forgets the stored state', function (string $name) {
    $store = ($this->store)($name);
    $store->put('order', 'visitor-1', ['current_step' => 'summary']);
    $store->forget('order', 'visitor-1');

    expect($store->get('order', 'visitor-1'))->toBeNull();
})->with('stores');

it('keeps the states of different wizards apart', function (string $name) {
    $store = ($this->store)($name);
    $store->put('order', 'visitor-1', ['current_step' => 'summary']);
    $store->put('onboarding', 'visitor-1', ['current_step' => 'profile']);

    expect($store->get('order', 'visitor-1'))->toBe(['current_step' => 'summary'])
        ->and($store->get('onboarding', 'visitor-1'))->toBe(['current_step' => 'profile']);
})->with('stores');

it('keeps the states of different visitors apart unless the store belongs to one visitor', function (string $name, bool $scoped) {
    $store = ($this->store)($name);
    $store->put('order', 'visitor-1', ['current_step' => 'summary']);

    expect($store->get('order', 'visitor-2'))->toBe($scoped ? null : ['current_step' => 'summary']);
})->with('stores');

it('forgets only the given visitor', function (string $name, bool $scoped) {
    $store = ($this->store)($name);
    $store->put('order', 'visitor-1', ['current_step' => 'summary']);
    $store->put('order', 'visitor-2', ['current_step' => 'calculator']);
    $store->forget('order', 'visitor-1');

    expect($store->get('order', 'visitor-2'))->toBe($scoped ? ['current_step' => 'calculator'] : null);
})->with('stores');
