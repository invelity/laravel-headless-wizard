<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Tests\Fixtures\Steps;

use Invelity\WizardPackage\Step;

final class ConfirmationStep extends Step
{
    protected bool $displayOnly = true;
}
