<?php

declare(strict_types=1);

use Invelity\WizardPackage\Enums\StepStatus;
use Invelity\WizardPackage\ValueObjects\NavigationItem;

test('navigation item has label property hook', function () {
    $item = new NavigationItem(
        'personal-info',
        'Personal Information',
        1,
        StepStatus::InProgress,
        true,
        false,
        '/wizard/test/personal-info'
    );

    expect($item->label)->toBe('1. Personal Information');
});

test('navigation item icon for completed status', function () {
    $item = new NavigationItem(
        'step-1',
        'Step One',
        1,
        StepStatus::Completed,
        true,
        false,
        null
    );

    expect($item->icon)->toBe('check');
});

test('navigation item icon for in progress status', function () {
    $item = new NavigationItem(
        'step-1',
        'Step One',
        1,
        StepStatus::InProgress,
        true,
        false,
        null
    );

    expect($item->icon)->toBe('arrow-right');
});

test('navigation item icon for pending status', function () {
    $item = new NavigationItem(
        'step-1',
        'Step One',
        1,
        StepStatus::Pending,
        false,
        false,
        null
    );

    expect($item->icon)->toBe('circle');
});

test('navigation item icon for skipped status', function () {
    $item = new NavigationItem(
        'step-1',
        'Step One',
        1,
        StepStatus::Skipped,
        true,
        true,
        null
    );

    expect($item->icon)->toBe('skip-forward');
});

test('navigation item icon for invalid status', function () {
    $item = new NavigationItem(
        'step-1',
        'Step One',
        1,
        StepStatus::Invalid,
        false,
        false,
        null
    );

    expect($item->icon)->toBe('x-circle');
});

test('is current returns true for in progress status', function () {
    $item = new NavigationItem(
        'step-1',
        'Step One',
        1,
        StepStatus::InProgress,
        true,
        false,
        null
    );

    expect($item->isCurrent())->toBeTrue();
});

test('is current returns false for non in progress status', function () {
    $item = new NavigationItem(
        'step-1',
        'Step One',
        1,
        StepStatus::Completed,
        true,
        false,
        null
    );

    expect($item->isCurrent())->toBeFalse();
});

test('is completed returns true for completed status', function () {
    $item = new NavigationItem(
        'step-1',
        'Step One',
        1,
        StepStatus::Completed,
        true,
        false,
        null
    );

    expect($item->isCompleted())->toBeTrue();
});

test('is completed returns false for non completed status', function () {
    $item = new NavigationItem(
        'step-1',
        'Step One',
        1,
        StepStatus::Pending,
        false,
        false,
        null
    );

    expect($item->isCompleted())->toBeFalse();
});

test('to array returns correct structure', function () {
    $item = new NavigationItem(
        'personal-info',
        'Personal Information',
        1,
        StepStatus::InProgress,
        true,
        false,
        '/wizard/test/personal-info'
    );

    $array = $item->toArray();

    expect($array)->toHaveKeys(['step_id', 'title', 'position', 'status', 'is_accessible', 'is_optional', 'url'])
        ->and($array['step_id'])->toBe('personal-info')
        ->and($array['title'])->toBe('Personal Information')
        ->and($array['position'])->toBe(1)
        ->and($array['status'])->toBe('in_progress')
        ->and($array['is_accessible'])->toBeTrue()
        ->and($array['is_optional'])->toBeFalse()
        ->and($array['url'])->toBe('/wizard/test/personal-info');
});

test('navigation item with null url', function () {
    $item = new NavigationItem(
        'step-1',
        'Step One',
        2,
        StepStatus::Pending,
        false,
        true,
        null
    );

    expect($item->url)->toBeNull()
        ->and($item->toArray()['url'])->toBeNull();
});
