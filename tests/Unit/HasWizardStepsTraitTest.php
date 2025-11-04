<?php

declare(strict_types=1);

use Invelity\WizardPackage\Traits\HasWizardSteps;

beforeEach(function () {
    $this->class = new class
    {
        use HasWizardSteps;
    };
});

test('trait can get all wizard data', function () {
    $this->class->setWizardData(['name' => 'John', 'email' => 'john@example.com']);

    $data = $this->class->getWizardData();

    expect($data)->toBeArray()
        ->and($data['name'])->toBe('John')
        ->and($data['email'])->toBe('john@example.com');
});

test('trait can get specific wizard data by key', function () {
    $this->class->setWizardData(['name' => 'John', 'email' => 'john@example.com']);

    expect($this->class->getWizardData('name'))->toBe('John')
        ->and($this->class->getWizardData('email'))->toBe('john@example.com');
});

test('trait can get nested wizard data', function () {
    $this->class->setWizardData([
        'user' => [
            'profile' => ['name' => 'John'],
        ],
    ]);

    expect($this->class->getWizardData('user.profile.name'))->toBe('John');
});

test('trait returns null for non existent key', function () {
    $this->class->setWizardData(['name' => 'John']);

    expect($this->class->getWizardData('non_existent'))->toBeNull();
});

test('trait can set wizard data', function () {
    $this->class->setWizardData(['step' => 'personal-info']);

    expect($this->class->getWizardData())->toBe(['step' => 'personal-info']);
});

test('trait can merge wizard data', function () {
    $this->class->setWizardData(['name' => 'John', 'age' => 30]);
    $this->class->mergeWizardData(['age' => 31, 'city' => 'NYC']);

    $data = $this->class->getWizardData();

    expect($data['name'])->toBe('John')
        ->and($data['age'])->toBe(31)
        ->and($data['city'])->toBe('NYC');
});

test('trait merge wizard data overwrites existing keys', function () {
    $this->class->setWizardData(['name' => 'John']);
    $this->class->mergeWizardData(['name' => 'Jane']);

    expect($this->class->getWizardData('name'))->toBe('Jane');
});

test('trait initializes with empty array', function () {
    expect($this->class->getWizardData())->toBe([]);
});
