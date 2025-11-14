<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Services;

use Invelity\WizardPackage\Contracts\WizardStepInterface;

class StepFinderService
{
    /**
     * @param array<WizardStepInterface> $steps
     */
    public function findStep(array $steps, string $stepId): ?WizardStepInterface
    {
        return array_find(
            $steps,
            fn (WizardStepInterface $step) => $step->getId() === $stepId
        );
    }

    /**
     * @param array<WizardStepInterface> $steps
     */
    public function findStepIndex(array $steps, string $stepId): ?int
    {
        foreach ($steps as $index => $step) {
            if ($step->getId() === $stepId) {
                return $index;
            }
        }

        return null;
    }
}
