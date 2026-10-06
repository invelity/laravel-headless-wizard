<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Tests\Fixtures\Steps;

use Invelity\WizardPackage\Step;

final class NewsletterStep extends Step
{
    protected bool $optional = true;
}
