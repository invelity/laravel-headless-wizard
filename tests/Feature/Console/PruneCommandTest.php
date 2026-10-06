<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Invelity\WizardPackage\StoreManager;

it('prunes abandoned states from the database store', function () {
    $store = app(StoreManager::class)->store('database');

    $this->travelTo(CarbonImmutable::parse('2026-01-01'));
    $store->put('order', 'abandoned', ['current_step' => 'calculator']);

    $this->travelTo(CarbonImmutable::parse('2026-01-25'));
    $store->put('order', 'active', ['current_step' => 'summary']);

    $this->travelTo(CarbonImmutable::parse('2026-02-15'));

    $this->artisan('wizard:prune', ['--store' => 'database', '--days' => 30])
        ->expectsOutputToContain('1 wizard state(s) pruned from the [database] store.')
        ->assertSuccessful();

    expect($store->get('order', 'abandoned'))->toBeNull()
        ->and($store->get('order', 'active'))->not->toBeNull();
});

it('prunes the default store when no store is given', function () {
    config(['wizard.default' => 'database']);

    $this->artisan('wizard:prune')
        ->expectsOutputToContain('0 wizard state(s) pruned from the [database] store.')
        ->assertSuccessful();
});

it('refuses stores that cannot be pruned', function () {
    $this->artisan('wizard:prune', ['--store' => 'session'])
        ->expectsOutputToContain('The [session] wizard store does not support pruning.')
        ->assertFailed();
});

it('refuses an invalid number of days', function () {
    $this->artisan('wizard:prune', ['--store' => 'database', '--days' => 'many'])
        ->expectsOutputToContain('The --days option must be a whole number of at least zero.')
        ->assertFailed();
});
