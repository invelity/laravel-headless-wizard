<?php

declare(strict_types=1);

use Invelity\WizardPackage\ValueObjects\WizardProgressValue;

test('it calculates zero percent with no completed steps', function () {
    $progress = WizardProgressValue::calculate(
        totalSteps: 5,
        completedSteps: 0,
        currentStepPosition: 1,
        remainingStepIds: ['step-1', 'step-2', 'step-3', 'step-4', 'step-5']
    );

    expect($progress->percentComplete)->toBe(0);
});

test('it calculates correct percentage', function () {
    $progress = WizardProgressValue::calculate(
        totalSteps: 4,
        completedSteps: 2,
        currentStepPosition: 3,
        remainingStepIds: ['step-3', 'step-4']
    );

    expect($progress->percentComplete)->toBe(50);
});

test('it calculates 100 percent when all steps completed', function () {
    $progress = WizardProgressValue::calculate(
        totalSteps: 3,
        completedSteps: 3,
        currentStepPosition: 3,
        remainingStepIds: []
    );

    expect($progress->percentComplete)->toBe(100)
        ->and($progress->isComplete)->toBeTrue();
});

test('it marks wizard as incomplete when steps remaining', function () {
    $progress = WizardProgressValue::calculate(
        totalSteps: 5,
        completedSteps: 3,
        currentStepPosition: 4,
        remainingStepIds: ['step-4', 'step-5']
    );

    expect($progress->isComplete)->toBeFalse();
});

test('it converts to array', function () {
    $progress = WizardProgressValue::calculate(
        totalSteps: 3,
        completedSteps: 1,
        currentStepPosition: 2,
        remainingStepIds: ['step-2', 'step-3']
    );

    $array = $progress->toArray();

    expect($array)
        ->toHaveKeys([
            'total_steps',
            'completed_steps',
            'current_step_position',
            'percent_complete',
            'remaining_step_ids',
            'is_complete',
        ]);
});

test('it handles edge case of zero total steps', function () {
    $progress = WizardProgressValue::calculate(
        totalSteps: 0,
        completedSteps: 0,
        currentStepPosition: 0,
        remainingStepIds: []
    );

    expect($progress->percentComplete)->toBe(0);
});

test('it rounds percentage correctly', function () {
    $progress = WizardProgressValue::calculate(
        totalSteps: 3,
        completedSteps: 1,
        currentStepPosition: 2,
        remainingStepIds: ['step-2', 'step-3']
    );

    expect($progress->percentComplete)->toBe(33);
});
