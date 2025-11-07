<?php

declare(strict_types=1);

use Invelity\WizardPackage\Components\StepNavigation;

it('creates navigation with back and next enabled', function () {
    $component = new StepNavigation(
        canGoBack: true,
        canGoForward: true,
        previousStep: 'step1',
        nextStep: 'step3'
    );

    expect($component->canGoBack)->toBeTrue();
    expect($component->canGoForward)->toBeTrue();
    expect($component->previousStep)->toBe('step1');
    expect($component->nextStep)->toBe('step3');
});

it('sets canGoBack to false', function () {
    $component = new StepNavigation(
        canGoBack: false,
        canGoForward: true,
        nextStep: 'step2'
    );

    expect($component->canGoBack)->toBeFalse();
});

it('marks as last step', function () {
    $component = new StepNavigation(
        canGoBack: true,
        canGoForward: false,
        isLastStep: true,
        previousStep: 'step2'
    );

    expect($component->isLastStep)->toBeTrue();
    expect($component->completeText)->toBe('Complete');
});

it('accepts custom button text', function () {
    $component = new StepNavigation(
        canGoBack: true,
        canGoForward: true,
        previousStep: 'step1',
        nextStep: 'step3',
        backText: 'Go Back',
        nextText: 'Continue'
    );

    expect($component->backText)->toBe('Go Back');
    expect($component->nextText)->toBe('Continue');
});
