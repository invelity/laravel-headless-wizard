<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Tests\Unit\Php84;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Invelity\WizardPackage\Models\WizardProgress;
use Invelity\WizardPackage\Tests\TestCase;

class AsymmetricVisibilityTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function wizard_progress_status_has_asymmetric_visibility(): void
    {
        $progress = WizardProgress::factory()->create([
            'status' => 'in_progress',
        ]);

        expect($progress->status)->toBe('in_progress');

        $progress->markAsCompleted();

        expect($progress->fresh()->status)->toBe('completed');
    }

    /** @test */
    public function wizard_progress_status_is_publicly_readable(): void
    {
        $progress = WizardProgress::factory()->create([
            'status' => 'in_progress',
        ]);

        $statusValue = $progress->status;

        expect($statusValue)->toBe('in_progress');
    }

    /** @test */
    public function wizard_progress_status_can_be_modified_internally(): void
    {
        $progress = WizardProgress::factory()->create([
            'status' => 'in_progress',
        ]);

        $progress->markAsCompleted();

        expect($progress->fresh()->status)->toBe('completed');

        $progress->markAsAbandoned();

        expect($progress->fresh()->status)->toBe('abandoned');
    }
}
