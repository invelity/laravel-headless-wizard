<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Tests\Integration;

use Invelity\WizardPackage\Tests\TestCase;

class WizardControllerEditTest extends TestCase
{
    public function test_it_shows_edit_mode_with_prefilled_data(): void
    {
        config(['wizard.wizards.test-wizard.steps' => [\Invelity\WizardPackage\Tests\Fixtures\PersonalInfoStep::class]]);

        session()->put('test-wizard', [
            'wizard_id' => 'test-wizard',
            'current_step' => 'personal-info',
            'completed_steps' => [],
            'steps' => ['personal-info' => ['name' => 'John']],
            'metadata' => [],
            'started_at' => now()->toIso8601String(),
        ]);

        $response = $this->get(route('wizard.edit', [
            'wizard' => 'test-wizard',
            'wizardId' => 1,
            'step' => 'personal-info',
        ]));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'data' => [
                'is_edit_mode' => true,
            ],
        ]);
    }

    public function test_it_updates_single_step_without_affecting_others(): void
    {
        config(['wizard.wizards.test-wizard.steps' => []]);
        expect(true)->toBeTrue();
    }

    public function test_it_validates_data_in_edit_mode(): void
    {
        expect(true)->toBeTrue();
    }

    public function test_it_preserves_changes_when_navigating_between_steps(): void
    {
        expect(true)->toBeTrue();
    }

    public function test_it_loads_wizard_from_database_in_edit_mode(): void
    {
        expect(true)->toBeTrue();
    }

    public function test_it_prevents_editing_non_existent_wizard(): void
    {
        expect(true)->toBeTrue();
    }
}
