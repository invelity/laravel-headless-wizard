<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\Tests\Unit;

use WebSystem\WizardPackage\Tests\TestCase;
use WebSystem\WizardPackage\ValueObjects\WizardProgressValue;

class ProgressCalculationTest extends TestCase
{
    public function test_it_calculates_zero_percent_with_no_completed_steps(): void
    {
        $progress = WizardProgressValue::calculate(
            totalSteps: 5,
            completedSteps: 0,
            currentStepPosition: 1,
            remainingStepIds: ['step-1', 'step-2', 'step-3', 'step-4', 'step-5']
        );

        expect($progress->percentComplete)->toBe(0);
    }

    public function test_it_calculates_correct_percentage(): void
    {
        $progress = WizardProgressValue::calculate(
            totalSteps: 4,
            completedSteps: 2,
            currentStepPosition: 3,
            remainingStepIds: ['step-3', 'step-4']
        );

        expect($progress->percentComplete)->toBe(50);
    }

    public function test_it_calculates_100_percent_when_all_steps_completed(): void
    {
        $progress = WizardProgressValue::calculate(
            totalSteps: 3,
            completedSteps: 3,
            currentStepPosition: 3,
            remainingStepIds: []
        );

        expect($progress->percentComplete)->toBe(100)
            ->and($progress->isComplete)->toBeTrue();
    }

    public function test_it_marks_wizard_as_incomplete_when_steps_remaining(): void
    {
        $progress = WizardProgressValue::calculate(
            totalSteps: 5,
            completedSteps: 3,
            currentStepPosition: 4,
            remainingStepIds: ['step-4', 'step-5']
        );

        expect($progress->isComplete)->toBeFalse();
    }

    public function test_it_converts_to_array(): void
    {
        $progress = WizardProgressValue::calculate(
            totalSteps: 3,
            completedSteps: 1,
            currentStepPosition: 2,
            remainingStepIds: ['step-2', 'step-3']
        );

        $array = $progress->toArray();

        expect($array)
            ->toHaveKeys([
                'total_steps',
                'completed_steps',
                'current_step_position',
                'percent_complete',
                'remaining_step_ids',
                'is_complete',
            ]);
    }

    public function test_it_handles_edge_case_of_zero_total_steps(): void
    {
        $progress = WizardProgressValue::calculate(
            totalSteps: 0,
            completedSteps: 0,
            currentStepPosition: 0,
            remainingStepIds: []
        );

        expect($progress->percentComplete)->toBe(0);
    }

    public function test_it_rounds_percentage_correctly(): void
    {
        $progress = WizardProgressValue::calculate(
            totalSteps: 3,
            completedSteps: 1,
            currentStepPosition: 2,
            remainingStepIds: ['step-2', 'step-3']
        );

        // 1/3 = 33.33%, should round to 33%
        expect($progress->percentComplete)->toBe(33);
    }
}
