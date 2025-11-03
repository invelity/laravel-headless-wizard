<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\Enums;

enum StepStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Skipped = 'skipped';
    case Invalid = 'invalid';

    public function isAccessible(): bool
    {
        return match ($this) {
            self::Completed, self::Skipped, self::InProgress => true,
            self::Pending, self::Invalid => false,
        };
    }

    public function canEdit(): bool
    {
        return match ($this) {
            self::Completed, self::InProgress => true,
            self::Pending, self::Skipped, self::Invalid => false,
        };
    }
}
