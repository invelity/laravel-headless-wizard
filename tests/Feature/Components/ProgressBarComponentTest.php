<?php

declare(strict_types=1);

use Invelity\WizardPackage\Components\ProgressBar;

it('renders progress bar with steps', function () {
    $steps = [
        ['id' => 'step1', 'title' => 'Step 1', 'order' => 1],
        ['id' => 'step2', 'title' => 'Step 2', 'order' => 2],
        ['id' => 'step3', 'title' => 'Step 3', 'order' => 3],
    ];

    $component = new ProgressBar($steps, 'step1');

    expect($component->steps)->toHaveCount(3);
    expect($component->currentStep)->toBe('step1');
});

it('sets current step correctly', function () {
    $steps = [
        ['id' => 'step1', 'title' => 'Step 1', 'order' => 1],
        ['id' => 'step2', 'title' => 'Step 2', 'order' => 2],
    ];

    $component = new ProgressBar($steps, 'step2');

    expect($component->currentStep)->toBe('step2');
});

it('calculates progress percentage', function () {
    $steps = [
        ['id' => 'step1', 'title' => 'Step 1', 'order' => 1],
        ['id' => 'step2', 'title' => 'Step 2', 'order' => 2],
        ['id' => 'step3', 'title' => 'Step 3', 'order' => 3],
        ['id' => 'step4', 'title' => 'Step 4', 'order' => 4],
    ];

    $component = new ProgressBar($steps, 'step2');

    expect($component->percentage)->toBe(50);
});
