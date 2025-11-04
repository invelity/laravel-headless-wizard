<?php

declare(strict_types=1);

use Invelity\WizardPackage\Enums\StepStatus;

test('pending status is not accessible', function () {
    expect(StepStatus::Pending->isAccessible())->toBeFalse();
});

test('in progress status is accessible', function () {
    expect(StepStatus::InProgress->isAccessible())->toBeTrue();
});

test('completed status is accessible', function () {
    expect(StepStatus::Completed->isAccessible())->toBeTrue();
});

test('skipped status is accessible', function () {
    expect(StepStatus::Skipped->isAccessible())->toBeTrue();
});

test('invalid status is not accessible', function () {
    expect(StepStatus::Invalid->isAccessible())->toBeFalse();
});

test('pending status cannot be edited', function () {
    expect(StepStatus::Pending->canEdit())->toBeFalse();
});

test('in progress status can be edited', function () {
    expect(StepStatus::InProgress->canEdit())->toBeTrue();
});

test('completed status can be edited', function () {
    expect(StepStatus::Completed->canEdit())->toBeTrue();
});

test('skipped status cannot be edited', function () {
    expect(StepStatus::Skipped->canEdit())->toBeFalse();
});

test('invalid status cannot be edited', function () {
    expect(StepStatus::Invalid->canEdit())->toBeFalse();
});

test('status has correct string values', function () {
    expect(StepStatus::Pending->value)->toBe('pending')
        ->and(StepStatus::InProgress->value)->toBe('in_progress')
        ->and(StepStatus::Completed->value)->toBe('completed')
        ->and(StepStatus::Skipped->value)->toBe('skipped')
        ->and(StepStatus::Invalid->value)->toBe('invalid');
});
