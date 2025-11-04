<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Tests\Fixtures;

use Invelity\WizardPackage\Steps\AbstractStep;
use Invelity\WizardPackage\ValueObjects\StepData;
use Invelity\WizardPackage\ValueObjects\StepResult;

class OptionalStep extends AbstractStep
{
    public function __construct()
    {
        parent::__construct(
            id: 'optional-step',
            title: 'Optional Step',
            order: 2,
            isOptional: true,
            canSkip: true
        );
    }

    public function rules(): array
    {
        return [
            'optional_field' => ['nullable', 'string'],
        ];
    }

    public function process(StepData $data): StepResult
    {
        return StepResult::success(
            data: $data->all(),
            message: 'Optional step processed'
        );
    }
}
