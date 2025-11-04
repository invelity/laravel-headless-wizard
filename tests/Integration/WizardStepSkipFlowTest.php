<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Tests\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Invelity\WizardPackage\Tests\TestCase;

class WizardStepSkipFlowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_marks_step_as_completed_when_skipped(): void
    {
        config(['wizard.wizards.test-wizard.steps' => [
            \Invelity\WizardPackage\Tests\Fixtures\PersonalInfoStep::class,
            \Invelity\WizardPackage\Tests\Fixtures\OptionalStep::class,
            \Invelity\WizardPackage\Tests\Fixtures\ContactDetailsStep::class,
        ]]);

        session()->put('test-wizard', [
            'wizard_id' => 'test-wizard',
            'current_step' => 'optional-step',
            'completed_steps' => ['personal-info'],
            'steps' => ['personal-info' => ['name' => 'John']],
            'metadata' => [],
            'started_at' => now()->toIso8601String(),
        ]);

        $this->get(route('wizard.skip', ['wizard' => 'test-wizard', 'step' => 'optional-step']));

        $wizardData = session()->get('test-wizard');
        expect($wizardData['completed_steps'])->toContain('optional-step');
        expect($wizardData['current_step'])->toBe('contact-details');
    }

    /** @test */
    public function it_dispatches_step_skipped_event(): void
    {
        config(['wizard.wizards.test-wizard.steps' => [
            \Invelity\WizardPackage\Tests\Fixtures\PersonalInfoStep::class,
            \Invelity\WizardPackage\Tests\Fixtures\OptionalStep::class,
        ]]);

        \Illuminate\Support\Facades\Event::fake();

        session()->put('test-wizard', [
            'wizard_id' => 'test-wizard',
            'current_step' => 'optional-step',
            'completed_steps' => ['personal-info'],
            'steps' => ['personal-info' => ['name' => 'John']],
            'metadata' => [],
            'started_at' => now()->toIso8601String(),
        ]);

        $this->get(route('wizard.skip', ['wizard' => 'test-wizard', 'step' => 'optional-step']));

        \Illuminate\Support\Facades\Event::assertDispatched(
            \Invelity\WizardPackage\Events\StepSkipped::class,
            fn ($event) => $event->wizardId === 'test-wizard' && $event->stepId === 'optional-step'
        );
    }

    /** @test */
    public function it_prevents_skipping_required_steps(): void
    {
        config(['wizard.wizards.test-wizard.steps' => [
            \Invelity\WizardPackage\Tests\Fixtures\PersonalInfoStep::class,
            \Invelity\WizardPackage\Tests\Fixtures\ContactDetailsStep::class,
        ]]);

        session()->put('test-wizard', [
            'wizard_id' => 'test-wizard',
            'current_step' => 'contact-details',
            'completed_steps' => ['personal-info'],
            'steps' => ['personal-info' => ['name' => 'John']],
            'metadata' => [],
            'started_at' => now()->toIso8601String(),
        ]);

        $this->expectException(\Invelity\WizardPackage\Exceptions\InvalidStepException::class);

        $this->get(route('wizard.skip', ['wizard' => 'test-wizard', 'step' => 'contact-details']));
    }
}
