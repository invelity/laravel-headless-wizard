<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Invelity\WizardPackage\Models\WizardProgress;

uses(RefreshDatabase::class);

test('wizard progress status has asymmetric visibility', function () {
    $progress = WizardProgress::factory()->create([
        'status' => 'in_progress',
    ]);

    expect($progress->status)->toBe('in_progress');

    $progress->markAsCompleted();

    expect($progress->fresh()->status)->toBe('completed');
});

test('wizard progress status is publicly readable', function () {
    $progress = WizardProgress::factory()->create([
        'status' => 'in_progress',
    ]);

    $statusValue = $progress->status;

    expect($statusValue)->toBe('in_progress');
});

test('wizard progress status can be modified internally', function () {
    $progress = WizardProgress::factory()->create([
        'status' => 'in_progress',
    ]);

    $progress->markAsCompleted();

    expect($progress->fresh()->status)->toBe('completed');

    $progress->markAsAbandoned();

    expect($progress->fresh()->status)->toBe('abandoned');
});
