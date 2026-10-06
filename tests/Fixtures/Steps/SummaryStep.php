<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Tests\Fixtures\Steps;

use Invelity\WizardPackage\Step;

final class SummaryStep extends Step
{
    protected array $dependencies = [CalculatorStep::class, PersonalDataStep::class];
}
