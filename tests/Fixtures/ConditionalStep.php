<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Tests\Fixtures;

use Invelity\WizardPackage\Steps\AbstractStep;
use Invelity\WizardPackage\ValueObjects\StepData;
use Invelity\WizardPackage\ValueObjects\StepResult;

class ConditionalStep extends AbstractStep
{
    public function __construct()
    {
        parent::__construct(
            id: 'conditional-step',
            title: 'Conditional Step',
            order: 2,
            isOptional: true,
            canSkip: true
        );
    }

    public function rules(): array
    {
        return [
            'field' => ['nullable', 'string'],
        ];
    }

    public function shouldSkip(array $wizardData): bool
    {
        return $wizardData['steps']['skip_conditional'] ?? false;
    }

    public function process(StepData $data): StepResult
    {
        return StepResult::success(
            data: $data->all(),
            message: 'Conditional step processed'
        );
    }
}
