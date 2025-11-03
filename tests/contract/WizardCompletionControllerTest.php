<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\Tests\Contract;

use Illuminate\Foundation\Testing\RefreshDatabase;
use WebSystem\WizardPackage\Tests\TestCase;

class WizardCompletionControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_displays_completion_page_when_wizard_is_complete(): void
    {
        config(['wizard.wizards.test-wizard.steps' => [
            \WebSystem\WizardPackage\Tests\Fixtures\PersonalInfoStep::class,
        ]]);

        $this->withoutExceptionHandling();

        session()->put('test-wizard', [
            'wizard_id' => 'test-wizard',
            'current_step' => 'personal-info',
            'completed_steps' => ['personal-info'],
            'steps' => ['personal-info' => ['name' => 'John']],
            'metadata' => [],
            'started_at' => now()->toIso8601String(),
        ]);

        $response = $this->post(route('wizard.completed', 'test-wizard'));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'data' => [
                'personal-info' => ['name' => 'John'],
            ],
        ]);
    }

    /** @test */
    public function it_returns_error_when_wizard_not_complete(): void
    {
        config(['wizard.wizards.test-wizard.steps' => [
            \WebSystem\WizardPackage\Tests\Fixtures\PersonalInfoStep::class,
            \WebSystem\WizardPackage\Tests\Fixtures\ContactDetailsStep::class,
        ]]);

        session()->put('test-wizard', [
            'wizard_id' => 'test-wizard',
            'current_step' => 'personal-info',
            'completed_steps' => ['personal-info'],
            'steps' => ['personal-info' => ['name' => 'John']],
            'metadata' => [],
            'started_at' => now()->toIso8601String(),
        ]);

        $response = $this->get(route('wizard.completed', 'test-wizard'));

        $response->assertOk();
        $response->assertViewHas('message');
    }

    /** @test */
    public function it_has_only_invoke_method(): void
    {
        $controller = new \WebSystem\WizardPackage\Http\Controllers\WizardCompletionController(
            app(\WebSystem\WizardPackage\Contracts\WizardManagerInterface::class)
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
