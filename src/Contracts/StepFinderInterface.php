<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Contracts;

interface StepFinderInterface
{
    /**
     * @param  array<WizardStepInterface>  $steps
     */
    public function findStep(array $steps, string $stepId): ?WizardStepInterface;

    /**
     * @param  array<WizardStepInterface>  $steps
     */
    public function findStepIndex(array $steps, string $stepId): ?int;
}
