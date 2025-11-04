<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Tests\Unit;

use Invelity\WizardPackage\Exceptions\StepValidationException;
use Invelity\WizardPackage\Steps\AbstractStep;
use Invelity\WizardPackage\Tests\TestCase;
use Invelity\WizardPackage\ValueObjects\StepData;
use Invelity\WizardPackage\ValueObjects\StepResult;

class StepValidationTest extends TestCase
{
    public function test_it_validates_required_fields(): void
    {
        $step = new class('test', 'Test', 1) extends AbstractStep
        {
            public function rules(): array
            {
                return ['name' => 'required|string'];
            }

            public function process(StepData $data): StepResult
            {
                return StepResult::success();
            }
        };

        expect(fn () => $step->validate([]))
            ->toThrow(StepValidationException::class);
    }

    public function test_it_passes_validation_with_valid_data(): void
    {
        $step = new class('test', 'Test', 1) extends AbstractStep
        {
            public function rules(): array
            {
                return ['name' => 'required|string'];
            }

            public function process(StepData $data): StepResult
            {
                return StepResult::success();
            }
        };

        $validated = $step->validate(['name' => 'John']);

        expect($validated)->toBe(['name' => 'John']);
    }

    public function test_it_validates_email_format(): void
    {
        $step = new class('test', 'Test', 1) extends AbstractStep
        {
            public function rules(): array
            {
                return ['email' => 'required|email'];
            }

            public function process(StepData $data): StepResult
            {
                return StepResult::success();
            }
        };

        expect(fn () => $step->validate(['email' => 'invalid-email']))
            ->toThrow(StepValidationException::class);
    }

    public function test_it_validates_numeric_fields(): void
    {
        $step = new class('test', 'Test', 1) extends AbstractStep
        {
            public function rules(): array
            {
                return ['age' => 'required|numeric|min:18'];
            }

            public function process(StepData $data): StepResult
            {
                return StepResult::success();
            }
        };

        expect(fn () => $step->validate(['age' => 15]))
            ->toThrow(StepValidationException::class);

        $validated = $step->validate(['age' => 25]);
        expect($validated)->toBe(['age' => 25]);
    }

    public function test_it_validates_array_fields(): void
    {
        $step = new class('test', 'Test', 1) extends AbstractStep
        {
            public function rules(): array
            {
                return [
                    'tags' => 'required|array',
                    'tags.*' => 'string',
                ];
            }

            public function process(StepData $data): StepResult
            {
                return StepResult::success();
            }
        };

        $validated = $step->validate(['tags' => ['php', 'laravel']]);
        expect($validated)->toHaveKey('tags');
    }
}
