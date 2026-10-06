<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Tests\Fixtures\Steps;

use Invelity\WizardPackage\Step;
use Invelity\WizardPackage\Tests\Fixtures\Requests\NewsletterRequest;

final class NewsletterStep extends Step
{
    protected ?string $formRequest = NewsletterRequest::class;

    protected bool $optional = true;
}
