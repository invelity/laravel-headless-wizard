<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\Tests\Fixtures;

use WebSystem\WizardPackage\Steps\AbstractStep;
use WebSystem\WizardPackage\ValueObjects\StepData;
use WebSystem\WizardPackage\ValueObjects\StepResult;

class ContactDetailsStep extends AbstractStep
{
    public function __construct()
    {
        parent::__construct(
            id: 'contact-details',
            title: 'Contact Details',
            order: 2,
            isOptional: false,
            canSkip: false
        );
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'phone' => ['nullable', 'string'],
        ];
    }

    public function process(StepData $data): StepResult
    {
        return StepResult::success(
            data: $data->all(),
            message: 'Contact details saved successfully'
        );
    }
}
