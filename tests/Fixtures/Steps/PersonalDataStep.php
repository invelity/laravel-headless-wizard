<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Tests\Fixtures\Steps;

use Invelity\WizardPackage\Step;
use Invelity\WizardPackage\Tests\Fixtures\Requests\PersonalDataRequest;

final class PersonalDataStep extends Step
{
    protected ?string $formRequest = PersonalDataRequest::class;
}
