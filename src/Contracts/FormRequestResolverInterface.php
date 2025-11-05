<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Contracts;

interface FormRequestResolverInterface
{
    /**
     * Resolve FormRequest class for the given step.
     *
     * Returns null if no FormRequest is found.
     */
    public function resolveForStep(WizardStepInterface $step): ?string;
}
