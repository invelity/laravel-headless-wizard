<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Traits;

use Illuminate\Support\Facades\Validator;
use Invelity\WizardPackage\Exceptions\StepValidationException;

trait ValidatesStepData
{
    /**
     * @throws StepValidationException
     */
    public function validate(array $data): array
    {
        $validator = Validator::make($data, $this->rules(), $this->messages());

        if ($validator->fails()) {
            throw new StepValidationException($validator->errors()->toArray());
        }

        return $validator->validated();
    }

    protected function messages(): array
    {
        return [];
    }
}
