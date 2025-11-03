<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\Steps;

use WebSystem\WizardPackage\Contracts\WizardStepInterface;
use WebSystem\WizardPackage\Traits\HasWizardSteps;
use WebSystem\WizardPackage\Traits\PersistsStepData;
use WebSystem\WizardPackage\Traits\ValidatesStepData;
use WebSystem\WizardPackage\ValueObjects\StepData;
use WebSystem\WizardPackage\ValueObjects\StepResult;

abstract class AbstractStep implements WizardStepInterface
{
    use HasWizardSteps;
    use PersistsStepData;
    use ValidatesStepData;

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
