<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Invelity\WizardPackage\Models\WizardProgress;
use Invelity\WizardPackage\Storage\DatabaseStorage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->storage = new DatabaseStorage;
});

test('database storage can put data', function () {
    $this->storage->put('wizard-1', ['step' => 'personal-info']);

    expect(WizardProgress::where('wizard_id', 'wizard-1')->exists())->toBeTrue();
});

test('database storage can get data', function () {
    $this->storage->put('wizard-1', ['step' => 'personal-info', 'name' => 'John']);

    $data = $this->storage->get('wizard-1');

    expect($data)->toBeArray()
        ->and($data['step'])->toBe('personal-info')
        ->and($data['name'])->toBe('John');
});

test('database storage returns null for non existent key', function () {
    $data = $this->storage->get('non-existent');

    expect($data)->toBeNull();
});

test('database storage can check existence', function () {
    $this->storage->put('wizard-1', ['step' => 'contact']);

    expect($this->storage->exists('wizard-1'))->toBeTrue()
        ->and($this->storage->exists('non-existent'))->toBeFalse();
});

test('database storage can forget data', function () {
    $this->storage->put('wizard-1', ['step' => 'personal-info']);

    expect($this->storage->exists('wizard-1'))->toBeTrue();

    $this->storage->forget('wizard-1');

    expect($this->storage->exists('wizard-1'))->toBeFalse();
});

test('database storage can update field', function () {
    $this->storage->put('wizard-1', ['step' => 'personal-info', 'name' => 'John']);

    $this->storage->update('wizard-1', 'name', 'Jane');

    $data = $this->storage->get('wizard-1');

    expect($data['name'])->toBe('Jane')
        ->and($data['step'])->toBe('personal-info');
});

test('database storage can update nested field', function () {
    $this->storage->put('wizard-1', [
        'steps' => [
            'personal-info' => ['name' => 'John'],
        ],
    ]);

    $this->storage->update('wizard-1', 'steps.personal-info.name', 'Jane');

    $data = $this->storage->get('wizard-1');

    expect($data['steps']['personal-info']['name'])->toBe('Jane');
});

test('database storage update or create updates existing record', function () {
    $this->storage->put('wizard-1', ['step' => 'step1']);
    $this->storage->put('wizard-1', ['step' => 'step2']);

    expect(WizardProgress::where('wizard_id', 'wizard-1')->count())->toBe(1);

    $data = $this->storage->get('wizard-1');
    expect($data['step'])->toBe('step2');
});

test('database storage update creates data if not exists', function () {
    expect($this->storage->exists('wizard-new'))->toBeFalse();

    $this->storage->update('wizard-new', 'name', 'John');

    expect($this->storage->exists('wizard-new'))->toBeTrue();

    $data = $this->storage->get('wizard-new');
    expect($data['name'])->toBe('John');
});
