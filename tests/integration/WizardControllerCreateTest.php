<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\Tests\Integration;

use WebSystem\WizardPackage\Tests\TestCase;

class WizardControllerCreateTest extends TestCase
{
    /** @test */
    public function it_shows_first_step_of_wizard(): void
    {
        $response = $this->get(route('wizard.show', [
            'wizard' => 'test-wizard',
            'step' => 'step-1',
        ]));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'wizard_id' => 'test-wizard',
                ],
            ]);
    }

    /** @test */
    public function it_processes_step_with_valid_data(): void
    {
        $response = $this->post(route('wizard.store', [
            'wizard' => 'test-wizard',
            'step' => 'step-1',
        ]), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'next_step' => 'step-2',
                ],
            ]);
    }

    /** @test */
    public function it_returns_validation_errors_for_invalid_data(): void
    {
        $response = $this->post(route('wizard.store', [
            'wizard' => 'test-wizard',
            'step' => 'step-1',
        ]), [
            'name' => '', // Invalid
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_returns_completion_status_after_last_step(): void
    {
        // Complete all steps
        $this->post(route('wizard.store', ['wizard' => 'test-wizard', 'step' => 'step-1']), [
            'name' => 'John',
            'email' => 'john@example.com',
        ]);

        $this->post(route('wizard.store', ['wizard' => 'test-wizard', 'step' => 'step-2']), [
            'address' => '123 Main St',
        ]);

        $response = $this->post(route('wizard.store', ['wizard' => 'test-wizard', 'step' => 'step-3']), [
            'preferences' => 'newsletter',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'is_completed' => true,
                ],
            ]);
    }

    /** @test */
    public function it_prevents_accessing_step_without_completing_prerequisites(): void
    {
        $response = $this->get(route('wizard.show', [
            'wizard' => 'test-wizard',
            'step' => 'step-3', // Try to skip to step 3
        ]));

        $response->assertRedirect(route('wizard.show', [
            'wizard' => 'test-wizard',
            'step' => 'step-1', // Should redirect to first incomplete step
        ]));
    }

    /** @test */
    public function it_preserves_data_when_navigating_back(): void
    {
        // Complete step 1
        $this->post(route('wizard.store', ['wizard' => 'test-wizard', 'step' => 'step-1']), [
            'name' => 'John',
            'email' => 'john@example.com',
        ]);

        // Go to step 2
        $response = $this->get(route('wizard.show', [
            'wizard' => 'test-wizard',
            'step' => 'step-2',
        ]));

        // Navigate back to step 1
        $response = $this->get(route('wizard.show', [
            'wizard' => 'test-wizard',
            'step' => 'step-1',
        ]));

        $response->assertOk()
            ->assertViewHas('data', fn ($data) => $data['name'] === 'John');
    }
}
