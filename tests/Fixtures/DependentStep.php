<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Tests\Fixtures;

use Invelity\WizardPackage\Steps\AbstractStep;
use Invelity\WizardPackage\ValueObjects\StepData;
use Invelity\WizardPackage\ValueObjects\StepResult;

class DependentStep extends AbstractStep
{
    public function __construct()
    {
        parent::__construct(
            id: 'dependent-step',
            title: 'Dependent Step',
            order: 3,
            isOptional: false,
            canSkip: false
        );
    }
    
    public function getDependencies(): array
    {
        return ['personal-info', 'contact-details'];
    }

    public function rules(): array
    {
        return [
            'data' => ['required', 'string'],
        ];
    }

    public function process(StepData $data): StepResult
    {
        return StepResult::success(
            data: $data->all(),
            message: 'Dependent step processed'
        );
    }
}
