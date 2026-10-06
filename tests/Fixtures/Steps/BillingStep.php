<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Tests\Fixtures\Steps;

use Invelity\WizardPackage\State;
use Invelity\WizardPackage\Step;

final class BillingStep extends Step
{
    public function shouldSkip(State $state): bool
    {
        return ($state->metadata['plan'] ?? null) === 'free';
    }
}
