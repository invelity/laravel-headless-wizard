<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\Tests\Unit\Php84;

use WebSystem\WizardPackage\Contracts\WizardStepInterface;

test('array_find returns matching step', function () {
    $step1 = mock(WizardStepInterface::class);
    $step1->shouldReceive('getId')->andReturn('personal-info');

    $step2 = mock(WizardStepInterface::class);
    $step2->shouldReceive('getId')->andReturn('contact-details');

    $steps = [$step1, $step2];

    $found = array_find(
        $steps,
        fn (WizardStepInterface $step) => $step->getId() === 'contact-details'
    );

    expect($found)->toBe($step2);
});

test('array_find returns null when no match found', function () {
    $step1 = mock(WizardStepInterface::class);
    $step1->shouldReceive('getId')->andReturn('personal-info');

    $steps = [$step1];

    $found = array_find(
        $steps,
        fn (WizardStepInterface $step) => $step->getId() === 'non-existent'
    );

    expect($found)->toBeNull();
});

test('array_any returns true when condition matches', function () {
    $dependencies = ['step-1', 'step-2', 'step-3'];
    $completedSteps = ['step-1', 'step-2'];

    $hasMissing = array_any(
        $dependencies,
        fn (string $dep) => ! in_array($dep, $completedSteps)
    );

    expect($hasMissing)->toBeTrue();
});

test('array_any returns false when no condition matches', function () {
    $dependencies = ['step-1', 'step-2'];
    $completedSteps = ['step-1', 'step-2', 'step-3'];

    $hasMissing = array_any(
        $dependencies,
        fn (string $dep) => ! in_array($dep, $completedSteps)
    );

    expect($hasMissing)->toBeFalse();
});
