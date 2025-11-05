<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Contracts;

use Invelity\WizardPackage\ValueObjects\StepData;
use Invelity\WizardPackage\ValueObjects\StepResult;

interface WizardStepInterface
{
    public function getId(): string;

    public function getTitle(): string;

    public function getOrder(): int;

    public function isOptional(): bool;

    public function canSkip(): bool;

    public function getFormRequest(): ?string;

    public function process(StepData $data): StepResult;

    public function beforeProcess(StepData $data): void;

    public function afterProcess(StepResult $result): void;

    public function shouldSkip(array $wizardData): bool;

    /**
     * @return string[]
     */
    public function getDependencies(): array;
}
