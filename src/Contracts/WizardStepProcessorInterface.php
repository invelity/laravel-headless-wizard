<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Contracts;

use Invelity\WizardPackage\Exceptions\InvalidStepException;
use Invelity\WizardPackage\ValueObjects\StepResult;

interface WizardStepProcessorInterface
{
    public function processStep(string $stepId, array $data): StepResult;

    /**
     * @throws InvalidStepException
     */
    public function skipStep(string $stepId): void;

    public function canAccessStep(string $stepId): bool;

    /**
     * @throws InvalidStepException
     */
    public function navigateToStep(string $stepId): void;
}
