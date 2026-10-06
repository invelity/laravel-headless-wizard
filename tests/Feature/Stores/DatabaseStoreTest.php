<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Invelity\WizardPackage\StoreManager;

it('encrypts the stored state by default', function () {
    app(StoreManager::class)->store('database')->put('order', 'visitor-1', ['metadata' => ['email' => 'jane@example.com']]);

    $raw = DB::table('wizard_states')->value('state');

    expect($raw)->toBeString()
        ->not->toContain('jane@example.com')
        ->and(json_decode(decrypt($raw, false), true))->toBe(['metadata' => ['email' => 'jane@example.com']]);
});

it('stores plain JSON when encryption is disabled', function () {
    config(['wizard.stores.database.encrypt' => false]);

    app(StoreManager::class)->store('database')->put('order', 'visitor-1', ['current_step' => 'summary']);

    expect(DB::table('wizard_states')->value('state'))->toBe('{"current_step":"summary"}');
});

it('keeps one row per wizard and scope and preserves its creation time', function () {
    $store = app(StoreManager::class)->store('database');

    $this->travelTo(CarbonImmutable::parse('2026-01-01 10:00:00'));
    $store->put('order', 'visitor-1', ['current_step' => 'calculator']);

    $this->travelTo(CarbonImmutable::parse('2026-01-02 10:00:00'));
    $store->put('order', 'visitor-1', ['current_step' => 'summary']);

    $row = DB::table('wizard_states')->first();

    expect(DB::table('wizard_states')->count())->toBe(1)
        ->and(CarbonImmutable::parse($row->created_at)->toDateString())->toBe('2026-01-01')
        ->and(CarbonImmutable::parse($row->updated_at)->toDateString())->toBe('2026-01-02');
});

it('prunes the states that have not changed since a given time', function () {
    $store = app(StoreManager::class)->store('database');

    $this->travelTo(CarbonImmutable::parse('2026-01-01'));
    $store->put('order', 'abandoned', ['current_step' => 'calculator']);

    $this->travelTo(CarbonImmutable::parse('2026-03-01'));
    $store->put('order', 'active', ['current_step' => 'summary']);

    expect($store->prune(CarbonImmutable::parse('2026-02-01')))->toBe(1)
        ->and($store->get('order', 'abandoned'))->toBeNull()
        ->and($store->get('order', 'active'))->toBe(['current_step' => 'summary']);
});

it('uses the configured table and connection', function () {
    config([
        'database.connections.wizards' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
        'wizard.stores.database.connection' => 'wizards',
        'wizard.stores.database.table' => 'custom_wizard_states',
    ]);

    $this->artisan('migrate', [
        '--database' => 'wizards',
        '--path' => realpath(__DIR__.'/../../../database/migrations'),
        '--realpath' => true,
    ])->assertSuccessful();

    app(StoreManager::class)->store('database')->put('order', 'visitor-1', ['current_step' => 'summary']);

    expect(DB::connection('wizards')->table('custom_wizard_states')->count())->toBe(1);
});
