<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Enums;

enum WizardMode: string
{
    case Create = 'create';
    case Edit = 'edit';
    case View = 'view';

    public function allowsDataModification(): bool
    {
        return match ($this) {
            self::Create, self::Edit => true,
            self::View => false,
        };
    }

    public function requiresExistingData(): bool
    {
        return match ($this) {
            self::Edit, self::View => true,
            self::Create => false,
        };
    }
}
