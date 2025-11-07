<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Components;

use Illuminate\View\Component;

class ProgressBar extends Component
{
    public int $percentage;

    public function __construct(
        public array $steps,
        public string $currentStep
    ) {
        $this->percentage = $this->calculatePercentage();
    }

    protected function calculatePercentage(): int
    {
        $totalSteps = count($this->steps);
        if ($totalSteps === 0) {
            return 0;
        }

        $currentIndex = array_search($this->currentStep, array_column($this->steps, 'id'));
        if ($currentIndex === false) {
            return 0;
        }

        return (int) (($currentIndex + 1) / $totalSteps * 100);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('wizard-package::components.progress-bar');
    }
}
