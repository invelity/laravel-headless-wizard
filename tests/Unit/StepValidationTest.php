<?php

declare(strict_types=1);

use Invelity\WizardPackage\Exceptions\StepValidationException;
use Invelity\WizardPackage\Steps\AbstractStep;
use Invelity\WizardPackage\ValueObjects\StepData;
use Invelity\WizardPackage\ValueObjects\StepResult;

test('it validates required fields', function () {
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
});

test('it passes validation with valid data', function () {
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
});

test('it validates email format', function () {
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
});

test('it validates numeric fields', function () {
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
});

test('it validates array fields', function () {
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
});
