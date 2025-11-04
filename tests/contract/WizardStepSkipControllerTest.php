<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Tests\Contract;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Invelity\WizardPackage\Tests\TestCase;

class WizardStepSkipControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_skips_optional_step_and_redirects_to_next(): void
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

        $response = $this->get(route('wizard.skip', ['wizard' => 'test-wizard', 'step' => 'optional-step']));

        $response->assertRedirect(route('wizard.show', [
            'wizard' => 'test-wizard',
            'step' => 'contact-details',
        ]));
        $response->assertSessionHas('success', 'Step skipped.');
    }

    /** @test */
    public function it_redirects_to_completion_when_skipping_last_step(): void
    {
        config(['wizard.wizards.test-wizard.steps' => [
            \Invelity\WizardPackage\Tests\Fixtures\PersonalInfoStep::class,
            \Invelity\WizardPackage\Tests\Fixtures\OptionalStep::class,
        ]]);

        session()->put('test-wizard', [
            'wizard_id' => 'test-wizard',
            'current_step' => 'optional-step',
            'completed_steps' => ['personal-info'],
            'steps' => ['personal-info' => ['name' => 'John']],
            'metadata' => [],
            'started_at' => now()->toIso8601String(),
        ]);

        $response = $this->get(route('wizard.skip', ['wizard' => 'test-wizard', 'step' => 'optional-step']));

        $response->assertRedirect(route('wizard.completed', 'test-wizard'));
    }

    /** @test */
    public function it_has_only_invoke_method(): void
    {
        $controller = new \Invelity\WizardPackage\Http\Controllers\WizardStepSkipController(
            app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class)
        );

        $reflection = new \ReflectionClass($controller);
        $publicMethods = array_filter(
            $reflection->getMethods(\ReflectionMethod::IS_PUBLIC),
            fn ($method) => ! $method->isConstructor() && $method->class === $reflection->getName()
        );

        $methodNames = array_map(fn ($m) => $m->name, $publicMethods);

        expect($methodNames)->toEqual(['__invoke']);
    }
}
