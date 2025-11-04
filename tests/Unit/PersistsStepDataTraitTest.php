<?php

declare(strict_types=1);

use Invelity\WizardPackage\Traits\PersistsStepData;

beforeEach(function () {
    $this->class = new class
    {
        use PersistsStepData;
    };
});

test('trait can load step data', function () {
    $this->class->loadStepData(['name' => 'John', 'email' => 'john@example.com']);

    expect($this->class->getStepData())->toBeArray()
        ->and($this->class->getStepData('name'))->toBe('John');
});

test('trait can get all step data', function () {
    $this->class->loadStepData(['name' => 'John', 'age' => 30]);

    $data = $this->class->getStepData();

    expect($data)->toBe(['name' => 'John', 'age' => 30]);
});

test('trait can get specific step data by key', function () {
    $this->class->loadStepData(['name' => 'John', 'email' => 'john@example.com']);

    expect($this->class->getStepData('name'))->toBe('John')
        ->and($this->class->getStepData('email'))->toBe('john@example.com');
});

test('trait can get nested step data', function () {
    $this->class->loadStepData([
        'address' => [
            'street' => '123 Main St',
            'city' => 'NYC',
        ],
    ]);

    expect($this->class->getStepData('address.street'))->toBe('123 Main St')
        ->and($this->class->getStepData('address.city'))->toBe('NYC');
});

test('trait returns null for non existent key', function () {
    $this->class->loadStepData(['name' => 'John']);

    expect($this->class->getStepData('non_existent'))->toBeNull();
});

test('trait can check if step data exists', function () {
    $this->class->loadStepData(['name' => 'John', 'email' => 'john@example.com']);

    expect($this->class->hasStepData('name'))->toBeTrue()
        ->and($this->class->hasStepData('email'))->toBeTrue()
        ->and($this->class->hasStepData('non_existent'))->toBeFalse();
});

test('trait has step data checks direct keys only', function () {
    $this->class->loadStepData([
        'address' => ['city' => 'NYC'],
    ]);

    expect($this->class->hasStepData('address'))->toBeTrue()
        ->and($this->class->hasStepData('address.city'))->toBeFalse();
});

test('trait initializes with empty array', function () {
    expect($this->class->getStepData())->toBe([]);
});

test('trait load step data replaces existing data', function () {
    $this->class->loadStepData(['name' => 'John']);
    $this->class->loadStepData(['email' => 'jane@example.com']);

    expect($this->class->getStepData())->toBe(['email' => 'jane@example.com']);
});
