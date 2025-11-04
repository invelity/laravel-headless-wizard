<?php

declare(strict_types=1);

use Invelity\WizardPackage\Enums\WizardMode;

test('create mode allows data modification', function () {
    expect(WizardMode::Create->allowsDataModification())->toBeTrue();
});

test('edit mode allows data modification', function () {
    expect(WizardMode::Edit->allowsDataModification())->toBeTrue();
});

test('view mode does not allow data modification', function () {
    expect(WizardMode::View->allowsDataModification())->toBeFalse();
});

test('create mode does not require existing data', function () {
    expect(WizardMode::Create->requiresExistingData())->toBeFalse();
});

test('edit mode requires existing data', function () {
    expect(WizardMode::Edit->requiresExistingData())->toBeTrue();
});

test('view mode requires existing data', function () {
    expect(WizardMode::View->requiresExistingData())->toBeTrue();
});

test('mode has correct string values', function () {
    expect(WizardMode::Create->value)->toBe('create')
        ->and(WizardMode::Edit->value)->toBe('edit')
        ->and(WizardMode::View->value)->toBe('view');
});
