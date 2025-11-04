<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Tests\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Invelity\WizardPackage\Tests\TestCase;

class WizardCompletionFlowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_completes_wizard_successfully_through_completion_route(): void
    {
        config(['wizard.wizards.test-wizard.steps' => [
            \Invelity\WizardPackage\Tests\Fixtures\PersonalInfoStep::class,
            \Invelity\WizardPackage\Tests\Fixtures\ContactDetailsStep::class,
        ]]);

        session()->put('test-wizard', [
            'wizard_id' => 'test-wizard',
            'current_step' => 'contact-details',
            'completed_steps' => ['personal-info', 'contact-details'],
            'steps' => [
                'personal-info' => ['name' => 'John Doe'],
                'contact-details' => ['email' => 'john@example.com'],
            ],
            'metadata' => [],
            'started_at' => now()->toIso8601String(),
        ]);

        $response = $this->post(route('wizard.completed', 'test-wizard'));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'data' => [
                'personal-info' => ['name' => 'John Doe'],
                'contact-details' => ['email' => 'john@example.com'],
            ],
        ]);
        expect(session()->get('test-wizard'))->toHaveKey('completed_at');
        expect(session()->get('test-wizard')['status'])->toBe('completed');
    }

    /** @test */
    public function it_prevents_completion_when_steps_incomplete(): void
    {
        config(['wizard.wizards.test-wizard.steps' => [
            \Invelity\WizardPackage\Tests\Fixtures\PersonalInfoStep::class,
            \Invelity\WizardPackage\Tests\Fixtures\ContactDetailsStep::class,
        ]]);

        session()->put('test-wizard', [
            'wizard_id' => 'test-wizard',
            'current_step' => 'personal-info',
            'completed_steps' => ['personal-info'],
            'steps' => [
                'personal-info' => ['name' => 'John Doe'],
            ],
            'metadata' => [],
            'started_at' => now()->toIso8601String(),
        ]);

        $response = $this->get(route('wizard.completed', 'test-wizard'));

        $response->assertOk();
        expect(session()->get('test-wizard'))->not->toHaveKey('completed_at');
    }
}
