<?php

declare(strict_types=1);

use Carbon\Carbon;
use Invelity\WizardPackage\ValueObjects\StepData;

test('step data can be created', function () {
    $data = new StepData(
        'personal-info',
        ['name' => 'John', 'email' => 'john@example.com'],
        true,
        [],
        Carbon::now()
    );

    expect($data->stepId)->toBe('personal-info')
        ->and($data->isValid)->toBeTrue()
        ->and($data->errors)->toBe([]);
});

test('step data all method returns data array', function () {
    $data = new StepData(
        'contact',
        ['phone' => '123456789', 'address' => 'Main St'],
        true,
        [],
        Carbon::now()
    );

    $all = $data->all();

    expect($all)->toBeArray()
        ->and($all['phone'])->toBe('123456789')
        ->and($all['address'])->toBe('Main St');
});

test('step data get method returns specific value', function () {
    $data = new StepData(
        'contact',
        ['phone' => '123456789', 'address' => 'Main St'],
        true,
        [],
        Carbon::now()
    );

    expect($data->get('phone'))->toBe('123456789')
        ->and($data->get('address'))->toBe('Main St');
});

test('step data get method returns default for non existent key', function () {
    $data = new StepData(
        'contact',
        ['phone' => '123456789'],
        true,
        [],
        Carbon::now()
    );

    expect($data->get('non_existent', 'default'))->toBe('default');
});

test('step data get method supports nested keys', function () {
    $data = new StepData(
        'contact',
        ['address' => ['city' => 'NYC', 'zip' => '10001']],
        true,
        [],
        Carbon::now()
    );

    expect($data->get('address.city'))->toBe('NYC')
        ->and($data->get('address.zip'))->toBe('10001');
});

test('step data has method checks key existence', function () {
    $data = new StepData(
        'contact',
        ['phone' => '123456789', 'email' => 'test@example.com'],
        true,
        [],
        Carbon::now()
    );

    expect($data->has('phone'))->toBeTrue()
        ->and($data->has('email'))->toBeTrue()
        ->and($data->has('non_existent'))->toBeFalse();
});

test('step data only method returns specified keys', function () {
    $data = new StepData(
        'contact',
        ['name' => 'John', 'email' => 'john@example.com', 'phone' => '123'],
        true,
        [],
        Carbon::now()
    );

    $only = $data->only(['name', 'email']);

    expect($only)->toHaveKeys(['name', 'email'])
        ->and($only)->not->toHaveKey('phone')
        ->and($only['name'])->toBe('John')
        ->and($only['email'])->toBe('john@example.com');
});

test('step data except method excludes specified keys', function () {
    $data = new StepData(
        'contact',
        ['name' => 'John', 'email' => 'john@example.com', 'phone' => '123'],
        true,
        [],
        Carbon::now()
    );

    $except = $data->except(['phone']);

    expect($except)->toHaveKeys(['name', 'email'])
        ->and($except)->not->toHaveKey('phone')
        ->and($except['name'])->toBe('John');
});

test('step data to array returns correct structure', function () {
    $timestamp = Carbon::now();
    $data = new StepData(
        'personal-info',
        ['name' => 'John'],
        true,
        [],
        $timestamp
    );

    $array = $data->toArray();

    expect($array)->toHaveKeys(['step_id', 'data', 'is_valid', 'errors', 'timestamp'])
        ->and($array['step_id'])->toBe('personal-info')
        ->and($array['data'])->toBe(['name' => 'John'])
        ->and($array['is_valid'])->toBeTrue()
        ->and($array['errors'])->toBe([])
        ->and($array['timestamp'])->toBe($timestamp->toIso8601String());
});

test('step data with validation errors', function () {
    $data = new StepData(
        'contact',
        ['email' => 'invalid'],
        false,
        ['email' => ['The email format is invalid']],
        Carbon::now()
    );

    expect($data->isValid)->toBeFalse()
        ->and($data->errors)->toHaveKey('email')
        ->and($data->errors['email'][0])->toBe('The email format is invalid');
});

test('step data is immutable', function () {
    $data = new StepData(
        'contact',
        ['name' => 'John'],
        true,
        [],
        Carbon::now()
    );

    expect($data)->toBeInstanceOf(StepData::class);
});
