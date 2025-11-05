<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Tests\Fixtures;

use Invelity\WizardPackage\Steps\AbstractStep;
use Invelity\WizardPackage\ValueObjects\StepData;
use Invelity\WizardPackage\ValueObjects\StepResult;

class PersonalInfoStep extends AbstractStep
{
    public function __construct()
    {
        parent::__construct(
            id: 'personal-info',
            title: 'Personal Information',
            order: 1,
            isOptional: false,
            canSkip: false
        );
    }

    public function getFormRequest(): ?string
    {
        return PersonalInfoRequest::class;
    }

    public function process(StepData $data): StepResult
    {
        return StepResult::success(
            data: $data->all(),
            message: 'Personal information saved successfully'
        );
    }
}
