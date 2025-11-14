<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Contracts;

interface WizardNavigationManagerInterface
{
    public function navigateToStep(string $stepId): void;

    public function getNextStep(): ?WizardStepInterface;

    public function getPreviousStep(): ?WizardStepInterface;

    public function getNavigation(): WizardNavigationInterface;
}
