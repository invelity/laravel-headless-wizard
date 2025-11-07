<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Components;

use Illuminate\View\Component;

class Layout extends Component
{
    public function __construct(
        public string $title = 'Wizard'
    ) {}

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('wizard-package::components.layout');
    }
}
