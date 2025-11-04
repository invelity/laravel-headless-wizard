<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\ValueObjects;

use WebSystem\WizardPackage\Enums\StepStatus;

final class NavigationItem
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
        public string $stepId,
        public string $title,
        public int $position,
        public StepStatus $status,
        public bool $isAccessible,
        public bool $isOptional,
        public ?string $url,
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
