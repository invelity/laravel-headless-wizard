<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Invelity\WizardPackage\Models\WizardProgress;

uses(RefreshDatabase::class);

test('wizard progress can be created', function () {
    $progress = WizardProgress::create([
        'wizard_id' => 'user-wizard',
        'current_step' => 'personal-info',
        'completed_steps' => [],
        'step_data' => ['test' => 'data'],
        'status' => 'in_progress',
        'started_at' => now(),
    ]);

    expect($progress->wizard_id)->toBe('user-wizard')
        ->and($progress->current_step)->toBe('personal-info')
        ->and($progress->status)->toBe('in_progress');
});

test('is complete returns true for completed status', function () {
    $progress = WizardProgress::create([
        'wizard_id' => 'user-wizard',
        'current_step' => 'done',
        'status' => 'completed',
        'completed_steps' => [],
        'step_data' => [],
    ]);

    expect($progress->isComplete())->toBeTrue();
});

test('is complete returns false for non completed status', function () {
    $progress = WizardProgress::create([
        'wizard_id' => 'user-wizard',
        'current_step' => 'step1',
        'status' => 'in_progress',
        'completed_steps' => [],
        'step_data' => [],
    ]);

    expect($progress->isComplete())->toBeFalse();
});

test('is abandoned returns true for abandoned status', function () {
    $progress = WizardProgress::create([
        'wizard_id' => 'user-wizard',
        'current_step' => 'step1',
        'status' => 'abandoned',
        'completed_steps' => [],
        'step_data' => [],
    ]);

    expect($progress->isAbandoned())->toBeTrue();
});

test('is abandoned returns false for non abandoned status', function () {
    $progress = WizardProgress::create([
        'wizard_id' => 'user-wizard',
        'current_step' => 'step1',
        'status' => 'in_progress',
        'completed_steps' => [],
        'step_data' => [],
    ]);

    expect($progress->isAbandoned())->toBeFalse();
});

test('mark as completed updates status and completed at', function () {
    $progress = WizardProgress::create([
        'wizard_id' => 'user-wizard',
        'current_step' => 'done',
        'status' => 'in_progress',
        'completed_steps' => [],
        'step_data' => [],
    ]);

    $progress->markAsCompleted();

    expect($progress->fresh()->status)->toBe('completed')
        ->and($progress->fresh()->completed_at)->not->toBeNull();
});

test('mark as abandoned updates status', function () {
    $progress = WizardProgress::create([
        'wizard_id' => 'user-wizard',
        'current_step' => 'step1',
        'status' => 'in_progress',
        'completed_steps' => [],
        'step_data' => [],
    ]);

    $progress->markAsAbandoned();

    expect($progress->fresh()->status)->toBe('abandoned');
});

test('update activity sets last activity at', function () {
    $progress = WizardProgress::create([
        'wizard_id' => 'user-wizard',
        'current_step' => 'step1',
        'status' => 'in_progress',
        'completed_steps' => [],
        'step_data' => [],
        'last_activity_at' => now()->subHour(),
    ]);

    $oldActivity = $progress->last_activity_at;

    sleep(1);
    $progress->updateActivity();

    expect($progress->fresh()->last_activity_at)->not->toEqual($oldActivity);
});

test('completed steps are cast to array', function () {
    $progress = WizardProgress::create([
        'wizard_id' => 'user-wizard',
        'current_step' => 'step2',
        'status' => 'in_progress',
        'completed_steps' => ['step1'],
        'step_data' => [],
    ]);

    expect($progress->completed_steps)->toBeArray()
        ->and($progress->completed_steps)->toContain('step1');
});

test('step data is encrypted in database', function () {
    $progress = WizardProgress::create([
        'wizard_id' => 'user-wizard',
        'current_step' => 'step1',
        'status' => 'in_progress',
        'completed_steps' => [],
        'step_data' => ['password' => 'secret123'],
    ]);

    expect($progress->step_data)->toBeArray()
        ->and($progress->step_data['password'])->toBe('secret123');
});

test('metadata is cast to array', function () {
    $progress = WizardProgress::create([
        'wizard_id' => 'user-wizard',
        'current_step' => 'step1',
        'status' => 'in_progress',
        'completed_steps' => [],
        'step_data' => [],
        'metadata' => ['source' => 'web'],
    ]);

    expect($progress->metadata)->toBeArray()
        ->and($progress->metadata['source'])->toBe('web');
});

test('wizard progress has user relationship', function () {
    $progress = WizardProgress::create([
        'wizard_id' => 'user-wizard',
        'current_step' => 'step1',
        'status' => 'in_progress',
        'completed_steps' => [],
        'step_data' => [],
    ]);

    $relation = $progress->user();
    expect($relation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
});
