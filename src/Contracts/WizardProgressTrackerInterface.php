<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Contracts;

use Invelity\WizardPackage\ValueObjects\WizardProgressValue;

interface WizardProgressTrackerInterface
{
    public function getProgress(string $wizardId, array $steps): WizardProgressValue;
}
