<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\Contracts;

use WebSystem\WizardPackage\ValueObjects\StepData;
use WebSystem\WizardPackage\ValueObjects\StepResult;

interface WizardStepInterface
{
    public function getId(): string;

    public function getTitle(): string;

    public function getOrder(): int;

    public function isOptional(): bool;

    public function canSkip(): bool;

    /**
     * @return array<string, string|array>
     */
    public function rules(): array;

    public function process(StepData $data): StepResult;

    public function beforeProcess(StepData $data): void;

    public function afterProcess(StepResult $result): void;

    public function shouldSkip(array $wizardData): bool;

    /**
     * @return string[]
     */
    public function getDependencies(): array;

    /**
     * @return array<string, mixed>
     */
    public function validate(array $data): array;
}
