<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Layout extends Component
{
    public function __construct(
        public string $title = 'Wizard'
    ) {}

    public function render(): View
    {
        /** @var view-string $viewPath */
        $viewPath = 'wizard-package::components.layout';

        return view($viewPath);
    }
}
