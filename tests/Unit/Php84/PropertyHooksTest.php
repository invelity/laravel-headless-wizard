<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Tests\Unit\Php84;

use Invelity\WizardPackage\Enums\StepStatus;
use Invelity\WizardPackage\ValueObjects\NavigationItem;
use Invelity\WizardPackage\ValueObjects\StepResult;
use Invelity\WizardPackage\ValueObjects\WizardProgressValue;

test('StepResult has isSuccess computed property hook', function () {
    $success = StepResult::success(['name' => 'John']);

    expect($success)->toHaveProperty('isSuccess')
        ->and($success->isSuccess)->toBeTrue();

    $failure = StepResult::failure(['field' => 'error']);

    expect($failure->isSuccess)->toBeFalse();
});

test('StepResult has hasErrors computed property hook', function () {
    $success = StepResult::success();

    expect($success)->toHaveProperty('hasErrors')
        ->and($success->hasErrors)->toBeFalse();

    $failure = StepResult::failure(['field' => 'error']);

    expect($failure->hasErrors)->toBeTrue();
});

test('NavigationItem has label computed property hook', function () {
    $item = new NavigationItem(
        stepId: 'personal-info',
        title: 'Personal Info',
        position: 1,
        status: StepStatus::Pending,
        isAccessible: true,
        isOptional: false,
        url: '/wizard/test/personal-info',
    );

    expect($item)->toHaveProperty('label')
        ->and($item->label)->toBe('1. Personal Info');
});

test('NavigationItem has icon computed property hook based on status', function () {
    $pending = new NavigationItem(
        stepId: 'personal-info',
        title: 'Personal Info',
        position: 1,
        status: StepStatus::Pending,
        isAccessible: true,
        isOptional: false,
        url: null,
    );

    expect($pending)->toHaveProperty('icon')
        ->and($pending->icon)->toBe('circle');

    $inProgress = new NavigationItem(
        stepId: 'contact',
        title: 'Contact',
        position: 2,
        status: StepStatus::InProgress,
        isAccessible: true,
        isOptional: false,
        url: null,
    );

    expect($inProgress->icon)->toBe('arrow-right');

    $completed = new NavigationItem(
        stepId: 'review',
        title: 'Review',
        position: 3,
        status: StepStatus::Completed,
        isAccessible: true,
        isOptional: false,
        url: null,
    );

    expect($completed->icon)->toBe('check');
});

test('WizardProgressValue has completionPercentage computed property hook', function () {
    $progress = WizardProgressValue::calculate(
        totalSteps: 4,
        completedSteps: 2,
        currentStepPosition: 3,
        remainingStepIds: ['step-3', 'step-4']
    );

    expect($progress)->toHaveProperty('completionPercentage')
        ->and($progress->completionPercentage)->toBe(50);

    $zeroProgress = WizardProgressValue::calculate(
        totalSteps: 0,
        completedSteps: 0,
        currentStepPosition: 0,
        remainingStepIds: []
    );

    expect($zeroProgress->completionPercentage)->toBe(0);
});
