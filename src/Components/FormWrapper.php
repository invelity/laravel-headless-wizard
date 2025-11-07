<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Components;

use Illuminate\View\Component;

class FormWrapper extends Component
{
    public function __construct(
        public string $action,
        public string $method = 'POST'
    ) {}

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('wizard-package::components.form-wrapper');
    }
}
