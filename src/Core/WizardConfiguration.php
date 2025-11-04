<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Core;

readonly class WizardConfiguration
{
    public function __construct(
        public string $storage,
        public array $navigation,
        public array $ui,
        public array $validation,
        public bool $fireEvents,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            storage: config('wizard.storage', 'session'),
            navigation: config('wizard.navigation', []),
            ui: config('wizard.ui', []),
            validation: config('wizard.validation', []),
            fireEvents: config('wizard.events.fire_events', true),
        );
    }

    public function allowsJumpNavigation(): bool
    {
        return $this->navigation['allow_jump'] ?? false;
    }

    public function showsAllSteps(): bool
    {
        return $this->navigation['show_all_steps'] ?? true;
    }

    public function marksCompleted(): bool
    {
        return $this->navigation['mark_completed'] ?? true;
    }

    public function validateOnNavigate(): bool
    {
        return $this->validation['validate_on_navigate'] ?? true;
    }

    public function allowsSkippingOptional(): bool
    {
        return $this->validation['allow_skip_optional'] ?? true;
    }
}
