<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Tests\Fixtures\Steps;

use Invelity\WizardPackage\Step;
use Invelity\WizardPackage\Tests\Fixtures\Requests\SummaryRequest;

final class SummaryStep extends Step
{
    protected ?string $formRequest = SummaryRequest::class;

    protected array $dependencies = [CalculatorStep::class, PersonalDataStep::class];
}
