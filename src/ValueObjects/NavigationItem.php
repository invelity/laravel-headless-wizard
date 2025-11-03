<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\ValueObjects;

use WebSystem\WizardPackage\Enums\StepStatus;

class NavigationItem
{
    public string $label {
        get => "{$this->position}. {$this->title}";
    }

    public string $icon {
        get => match ($this->status) {
            StepStatus::Completed => 'check',
            StepStatus::InProgress => 'arrow-right',
            StepStatus::Pending => 'circle',
            StepStatus::Skipped => 'skip-forward',
            StepStatus::Invalid => 'x-circle',
        };
    }

    public function __construct(
        public readonly string $stepId,
        public readonly string $title,
        public readonly int $position,
        public readonly StepStatus $status,
        public readonly bool $isAccessible,
        public readonly bool $isOptional,
        public readonly ?string $url,
    ) {}

    public function isCurrent(): bool
    {
        return $this->status === StepStatus::InProgress;
    }

    public function isCompleted(): bool
    {
        return $this->status === StepStatus::Completed;
    }

    public function toArray(): array
    {
        return [
            'step_id' => $this->stepId,
            'title' => $this->title,
            'position' => $this->position,
            'status' => $this->status->value,
            'is_accessible' => $this->isAccessible,
            'is_optional' => $this->isOptional,
            'url' => $this->url,
        ];
    }
}
