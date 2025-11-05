<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Steps;

use Invelity\WizardPackage\Contracts\WizardStepInterface;
use Invelity\WizardPackage\Traits\HasWizardSteps;
use Invelity\WizardPackage\Traits\PersistsStepData;
use Invelity\WizardPackage\ValueObjects\StepData;
use Invelity\WizardPackage\ValueObjects\StepResult;

abstract class AbstractStep implements WizardStepInterface
{
    use HasWizardSteps;
    use PersistsStepData;

    public function __construct(
        protected readonly string $id,
        protected readonly string $title,
        protected readonly int $order,
        protected readonly bool $isOptional = false,
        protected readonly bool $canSkip = false,
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function isOptional(): bool
    {
        return $this->isOptional;
    }

    public function canSkip(): bool
    {
        return $this->canSkip;
    }

    public function getFormRequest(): ?string
    {
        return null;
    }

    abstract public function process(StepData $data): StepResult;

    public function beforeProcess(StepData $data): void
    {
        // Hook for subclasses
    }

    public function afterProcess(StepResult $result): void
    {
        // Hook for subclasses
    }

    public function shouldSkip(array $wizardData): bool
    {
        return false;
    }

    public function getDependencies(): array
    {
        return [];
    }
}
