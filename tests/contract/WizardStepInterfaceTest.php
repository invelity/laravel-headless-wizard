<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Tests\Contract;

use Illuminate\Contracts\View\View;
use Invelity\WizardPackage\Steps\AbstractStep;
use Invelity\WizardPackage\Tests\TestCase;
use Invelity\WizardPackage\ValueObjects\StepData;
use Invelity\WizardPackage\ValueObjects\StepResult;

class WizardStepInterfaceTest extends TestCase
{
    private AbstractStep $step;

    protected function setUp(): void
    {
        parent::setUp();

        $this->step = new class('test-step', 'Test Step', 1) extends AbstractStep
        {
            public function rules(): array
            {
                return [
                    'name' => 'required|string',
                    'email' => 'required|email',
                ];
            }

            public function process(StepData $data): StepResult
            {
                return StepResult::success($data->all());
            }
        };
    }

    /** @test */
    public function step_returns_correct_id(): void
    {
        expect($this->step->getId())->toBe('test-step');
    }

    /** @test */
    public function step_returns_correct_title(): void
    {
        expect($this->step->getTitle())->toBe('Test Step');
    }

    /** @test */
    public function step_returns_correct_order(): void
    {
        expect($this->step->getOrder())->toBe(1);
    }

    /** @test */
    public function step_can_define_validation_rules(): void
    {
        $rules = $this->step->rules();

        expect($rules)
            ->toBeArray()
            ->toHaveKey('name')
            ->toHaveKey('email');
    }

    /** @test */
    public function step_can_process_valid_data(): void
    {
        $stepData = new StepData(
            stepId: 'test-step',
            data: ['name' => 'John', 'email' => 'john@example.com'],
            isValid: true,
            errors: [],
            timestamp: now()
        );

        $result = $this->step->process($stepData);

        expect($result)
            ->toBeInstanceOf(StepResult::class)
            ->and($result->success)->toBeTrue();
    }

    /** @test */
    public function step_can_render_view(): void
    {
        // Mock view for testing
        $this->app['view']->addLocation(__DIR__.'/../fixtures/views');

        $view = $this->step->render(['name' => 'John']);

        expect($view)->toBeInstanceOf(View::class);
    }

    /** @test */
    public function optional_step_returns_false_by_default(): void
    {
        expect($this->step->isOptional())->toBeFalse();
    }

    /** @test */
    public function step_can_be_optional(): void
    {
        $optionalStep = new class('optional-step', 'Optional Step', 2, true) extends AbstractStep
        {
            public function rules(): array
            {
                return [];
            }

            public function process(StepData $data): StepResult
            {
                return StepResult::success();
            }
        };

        expect($optionalStep->isOptional())->toBeTrue();
    }

    /** @test */
    public function step_can_skip_based_on_wizard_data(): void
    {
        $conditionalStep = new class('conditional-step', 'Conditional', 3) extends AbstractStep
        {
            public function rules(): array
            {
                return [];
            }

            public function process(StepData $data): StepResult
            {
                return StepResult::success();
            }

            public function shouldSkip(array $wizardData): bool
            {
                return ($wizardData['user_type'] ?? '') !== 'business';
            }
        };

        expect($conditionalStep->shouldSkip(['user_type' => 'individual']))->toBeTrue()
            ->and($conditionalStep->shouldSkip(['user_type' => 'business']))->toBeFalse();
    }

    /** @test */
    public function step_can_define_dependencies(): void
    {
        $dependentStep = new class('dependent-step', 'Dependent', 4) extends AbstractStep
        {
            public function rules(): array
            {
                return [];
            }

            public function process(StepData $data): StepResult
            {
                return StepResult::success();
            }

            public function getDependencies(): array
            {
                return ['step-1', 'step-2'];
            }
        };

        expect($dependentStep->getDependencies())
            ->toBe(['step-1', 'step-2']);
    }

    /** @test */
    public function step_calls_lifecycle_hooks(): void
    {
        $hooksCalled = [];

        $stepWithHooks = new class('hook-step', 'Hook Step', 1) extends AbstractStep
        {
            public array $hooksLog = [];

            public function rules(): array
            {
                return [];
            }

            public function process(StepData $data): StepResult
            {
                return StepResult::success();
            }

            public function beforeProcess(StepData $data): void
            {
                $this->hooksLog[] = 'before';
            }

            public function afterProcess(StepResult $result): void
            {
                $this->hooksLog[] = 'after';
            }
        };

        $stepData = new StepData('hook-step', [], true, [], now());

        $stepWithHooks->beforeProcess($stepData);
        $result = $stepWithHooks->process($stepData);
        $stepWithHooks->afterProcess($result);

        expect($stepWithHooks->hooksLog)->toBe(['before', 'after']);
    }
}
