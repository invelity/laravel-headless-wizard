<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Contracts;

interface WizardStepAccessInterface
{
    public function getCurrentStep(): ?WizardStepInterface;

    public function getStep(string $stepId): WizardStepInterface;

    public function canAccessStep(string $stepId): bool;
}
