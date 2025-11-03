<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\ValueObjects;

class WizardProgressValue
{
    public int $completionPercentage {
        get => $this->percentComplete;
    }

    public function __construct(
        public readonly int $totalSteps,
        public readonly int $completedSteps,
        public readonly int $currentStepPosition,
        public readonly int $percentComplete,
        public readonly array $remainingStepIds,
        public readonly bool $isComplete,
    ) {}

    public static function calculate(
        int $totalSteps,
        int $completedSteps,
        int $currentStepPosition,
        array $remainingStepIds
    ): self {
        $percentComplete = $totalSteps > 0
            ? (int) round(($completedSteps / $totalSteps) * 100)
            : 0;

        $isComplete = $completedSteps === $totalSteps && count($remainingStepIds) === 0;

        return new self(
            totalSteps: $totalSteps,
            completedSteps: $completedSteps,
            currentStepPosition: $currentStepPosition,
            percentComplete: $percentComplete,
            remainingStepIds: $remainingStepIds,
            isComplete: $isComplete,
        );
    }

    public function toArray(): array
    {
        return [
            'total_steps' => $this->totalSteps,
            'completed_steps' => $this->completedSteps,
            'current_step_position' => $this->currentStepPosition,
            'percent_complete' => $this->percentComplete,
            'remaining_step_ids' => $this->remainingStepIds,
            'is_complete' => $this->isComplete,
        ];
    }
}
