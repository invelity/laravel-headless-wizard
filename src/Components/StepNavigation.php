<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Components;

use Illuminate\View\Component;

class StepNavigation extends Component
{
    public function __construct(
        public bool $canGoBack = false,
        public bool $canGoForward = true,
        public bool $isLastStep = false,
        public ?string $previousStep = null,
        public ?string $nextStep = null,
        public string $backText = 'Previous',
        public string $nextText = 'Next',
        public string $completeText = 'Complete'
    ) {}

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('wizard-package::components.step-navigation');
    }
}
