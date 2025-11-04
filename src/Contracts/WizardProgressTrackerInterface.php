<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Contracts;

use Invelity\WizardPackage\ValueObjects\WizardProgressValue;

interface WizardProgressTrackerInterface
{
    public function getProgress(): WizardProgressValue;

    public function getAllData(): array;

    public function getCurrentStep(): ?WizardStepInterface;

    public function getNextStep(): ?WizardStepInterface;

    public function getPreviousStep(): ?WizardStepInterface;

    public function getNavigation(): WizardNavigationInterface;
}
