<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Invelity\WizardPackage\Storage\CacheStorage;

beforeEach(function () {
    Cache::flush();
    $this->storage = new CacheStorage(Cache::store(), 3600, 'test:');
});

test('cache storage can put data', function () {
    $this->storage->put('wizard-1', ['step' => 'personal-info']);

    expect(Cache::has('test:wizard-1'))->toBeTrue();
});

test('cache storage can get data', function () {
    $this->storage->put('wizard-1', ['step' => 'personal-info', 'name' => 'John']);

    $data = $this->storage->get('wizard-1');

    expect($data)->toBeArray()
        ->and($data['step'])->toBe('personal-info')
        ->and($data['name'])->toBe('John');
});

test('cache storage returns null for non existent key', function () {
    $data = $this->storage->get('non-existent');

    expect($data)->toBeNull();
});

test('cache storage can check existence', function () {
    $this->storage->put('wizard-1', ['step' => 'contact']);

    expect($this->storage->exists('wizard-1'))->toBeTrue()
        ->and($this->storage->exists('non-existent'))->toBeFalse();
});

test('cache storage can forget data', function () {
    $this->storage->put('wizard-1', ['step' => 'personal-info']);

    expect($this->storage->exists('wizard-1'))->toBeTrue();

    $this->storage->forget('wizard-1');

    expect($this->storage->exists('wizard-1'))->toBeFalse();
});

test('cache storage can update field', function () {
    $this->storage->put('wizard-1', ['step' => 'personal-info', 'name' => 'John']);

    $this->storage->update('wizard-1', 'name', 'Jane');

    $data = $this->storage->get('wizard-1');

    expect($data['name'])->toBe('Jane')
        ->and($data['step'])->toBe('personal-info');
});

test('cache storage can update nested field', function () {
    $this->storage->put('wizard-1', [
        'steps' => [
            'personal-info' => ['name' => 'John'],
        ],
    ]);

    $this->storage->update('wizard-1', 'steps.personal-info.name', 'Jane');

    $data = $this->storage->get('wizard-1');

    expect($data['steps']['personal-info']['name'])->toBe('Jane');
});

test('cache storage uses custom prefix', function () {
    $this->storage->put('wizard-1', ['test' => 'data']);

    expect(Cache::has('test:wizard-1'))->toBeTrue()
        ->and(Cache::has('wizard-1'))->toBeFalse();
});

test('cache storage update creates data if not exists', function () {
    expect($this->storage->exists('wizard-new'))->toBeFalse();

    $this->storage->update('wizard-new', 'name', 'John');

    expect($this->storage->exists('wizard-new'))->toBeTrue();

    $data = $this->storage->get('wizard-new');
    expect($data['name'])->toBe('John');
});
