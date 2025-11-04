<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Tests\Contract;

use Invelity\WizardPackage\Contracts\WizardManagerInterface;
use Invelity\WizardPackage\Contracts\WizardStepInterface;
use Invelity\WizardPackage\Exceptions\InvalidStepException;
use Invelity\WizardPackage\Exceptions\WizardNotInitializedException;
use Invelity\WizardPackage\Tests\TestCase;
use Invelity\WizardPackage\ValueObjects\StepResult;
use Invelity\WizardPackage\ValueObjects\WizardProgressValue;

class WizardManagerTest extends TestCase
{
    private WizardManagerInterface $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = app(WizardManagerInterface::class);
    }

    /** @test */
    public function it_implements_wizard_manager_interface(): void
    {
        expect($this->manager)
            ->toBeInstanceOf(WizardManagerInterface::class);
    }

    /** @test */
    public function it_can_initialize_wizard(): void
    {
        $this->manager->initialize('test-wizard', ['user_id' => 1]);

        expect($this->manager->getCurrentStep())
            ->toBeInstanceOf(WizardStepInterface::class);
    }

    /** @test */
    public function it_throws_exception_when_getting_step_before_initialization(): void
    {
        expect(fn () => $this->manager->getCurrentStep())
            ->toThrow(WizardNotInitializedException::class);
    }

    /** @test */
    public function it_can_get_step_by_id(): void
    {
        $this->manager->initialize('test-wizard');

        $step = $this->manager->getStep('step-1');

        expect($step)
            ->toBeInstanceOf(WizardStepInterface::class)
            ->and($step->getId())->toBe('step-1');
    }

    /** @test */
    public function it_throws_exception_for_invalid_step_id(): void
    {
        $this->manager->initialize('test-wizard');

        expect(fn () => $this->manager->getStep('invalid-step'))
            ->toThrow(InvalidStepException::class);
    }

    /** @test */
    public function it_can_process_step_with_valid_data(): void
    {
        $this->manager->initialize('test-wizard');

        $result = $this->manager->processStep('step-1', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        expect($result)
            ->toBeInstanceOf(StepResult::class)
            ->and($result->success)->toBeTrue();
    }

    /** @test */
    public function it_returns_failure_result_for_invalid_data(): void
    {
        $this->manager->initialize('test-wizard');

        $result = $this->manager->processStep('step-1', [
            'name' => '', // Invalid: required field
        ]);

        expect($result)
            ->toBeInstanceOf(StepResult::class)
            ->and($result->success)->toBeFalse()
            ->and($result->hasErrors())->toBeTrue();
    }

    /** @test */
    public function it_can_navigate_to_next_step(): void
    {
        $this->manager->initialize('test-wizard');

        // Process first step
        $this->manager->processStep('step-1', ['name' => 'John', 'email' => 'john@example.com']);

        $nextStep = $this->manager->getNextStep();

        expect($nextStep)
            ->toBeInstanceOf(WizardStepInterface::class)
            ->and($nextStep->getId())->toBe('step-2');
    }

    /** @test */
    public function it_can_navigate_to_previous_step(): void
    {
        $this->manager->initialize('test-wizard');
        $this->manager->processStep('step-1', ['name' => 'John', 'email' => 'john@example.com']);
        $this->manager->navigateToStep('step-2');

        $previousStep = $this->manager->getPreviousStep();

        expect($previousStep)
            ->toBeInstanceOf(WizardStepInterface::class)
            ->and($previousStep->getId())->toBe('step-1');
    }

    /** @test */
    public function it_can_check_step_access(): void
    {
        $this->manager->initialize('test-wizard');

        expect($this->manager->canAccessStep('step-1'))->toBeTrue()
            ->and($this->manager->canAccessStep('step-3'))->toBeFalse(); // Can't access step 3 before completing 1 & 2
    }

    /** @test */
    public function it_returns_progress_information(): void
    {
        $this->manager->initialize('test-wizard');

        $progress = $this->manager->getProgress();

        expect($progress)
            ->toBeInstanceOf(WizardProgressValue::class)
            ->and($progress->totalSteps)->toBeGreaterThan(0)
            ->and($progress->completedSteps)->toBe(0)
            ->and($progress->percentComplete)->toBe(0);
    }

    /** @test */
    public function it_can_get_all_wizard_data(): void
    {
        $this->manager->initialize('test-wizard');
        $this->manager->processStep('step-1', ['name' => 'John', 'email' => 'john@example.com']);

        $data = $this->manager->getAllData();

        expect($data)
            ->toBeArray()
            ->toHaveKey('step-1')
            ->and($data['step-1'])->toHaveKey('name', 'John');
    }

    /** @test */
    public function it_can_complete_wizard(): void
    {
        $this->manager->initialize('test-wizard');

        // Complete all steps
        $this->manager->processStep('step-1', ['name' => 'John', 'email' => 'john@example.com']);
        $this->manager->processStep('step-2', ['address' => '123 Main St']);
        $this->manager->processStep('step-3', ['preferences' => 'newsletter']);

        $result = $this->manager->complete();

        expect($result)
            ->toBeInstanceOf(StepResult::class)
            ->and($result->success)->toBeTrue();
    }

    /** @test */
    public function it_can_reset_wizard(): void
    {
        $this->manager->initialize('test-wizard');
        $this->manager->processStep('step-1', ['name' => 'John', 'email' => 'john@example.com']);

        $this->manager->reset();

        $progress = $this->manager->getProgress();
        expect($progress->completedSteps)->toBe(0);
    }
}
