<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Invelity\WizardPackage\Events\StepSkipped;

test('step skipped event can be dispatched', function () {
    Event::fake();

    $event = new StepSkipped('user-wizard', 'personal-info', 'optional');

    expect($event->wizardId)->toBe('user-wizard')
        ->and($event->stepId)->toBe('personal-info')
        ->and($event->reason)->toBe('optional');
});

test('step skipped event has correct properties', function () {
    $event = new StepSkipped('test-wizard', 'test-step', 'user skipped');

    expect($event->wizardId)->toBe('test-wizard')
        ->and($event->stepId)->toBe('test-step')
        ->and($event->reason)->toBe('user skipped');
});

test('step skipped event can be serialized', function () {
    $event = new StepSkipped('wizard-1', 'step-1', 'optional step');

    $serialized = serialize($event);
    $unserialized = unserialize($serialized);

    expect($unserialized->wizardId)->toBe('wizard-1')
        ->and($unserialized->stepId)->toBe('step-1')
        ->and($unserialized->reason)->toBe('optional step');
});
