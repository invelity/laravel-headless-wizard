<?php

declare(strict_types=1);

use Invelity\WizardPackage\Storage\SessionStorage;

test('session storage can put data', function () {
    $storage = app(SessionStorage::class);

    $storage->put('test', ['key' => 'value']);

    expect($storage->get('test'))->toBe(['key' => 'value']);
});

test('session storage can get data', function () {
    $storage = app(SessionStorage::class);

    $storage->put('test', ['name' => 'John']);

    expect($storage->get('test'))->toHaveKey('name');
    expect($storage->get('test')['name'])->toBe('John');
});

test('session storage returns null for non existent key', function () {
    $storage = app(SessionStorage::class);

    expect($storage->get('nonexistent'))->toBeNull();
});

test('session storage can check existence', function () {
    $storage = app(SessionStorage::class);

    $storage->put('test', ['data' => 'value']);

    expect($storage->exists('test'))->toBeTrue();
    expect($storage->exists('nonexistent'))->toBeFalse();
});

test('session storage can forget data', function () {
    $storage = app(SessionStorage::class);

    $storage->put('test', ['data' => 'value']);
    expect($storage->exists('test'))->toBeTrue();

    $storage->forget('test');

    expect($storage->exists('test'))->toBeFalse();
});

test('session storage can update field', function () {
    $storage = app(SessionStorage::class);

    $storage->put('test', ['name' => 'John']);
    $storage->update('test', 'name', 'Jane');

    $data = $storage->get('test');
    expect($data['name'])->toBe('Jane');
});

test('session storage can update nested field', function () {
    $storage = app(SessionStorage::class);

    $storage->put('test', ['user' => ['name' => 'John']]);
    $storage->update('test', 'user.age', 30);

    $data = $storage->get('test');
    expect($data['user']['age'])->toBe(30);
});

test('session storage update creates data if not exists', function () {
    $storage = app(SessionStorage::class);

    $storage->update('new', 'field', 'value');

    $data = $storage->get('new');
    expect($data['field'])->toBe('value');
});

test('session storage uses custom prefix', function () {
    $session = app('session.store');
    $storage = new SessionStorage($session, 'custom_');

    $storage->put('test', ['data' => 'value']);

    expect($session->has('custom_test'))->toBeTrue();
});
