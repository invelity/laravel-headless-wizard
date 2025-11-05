<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Services\Validation;

use Illuminate\Container\Container;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Invelity\WizardPackage\Contracts\FormRequestValidatorInterface;
use Invelity\WizardPackage\Exceptions\StepValidationException;

final readonly class FormRequestValidator implements FormRequestValidatorInterface
{
    public function __construct(
        private Container $container,
    ) {}

    /**
     * Validate data using the given FormRequest class.
     *
     * @throws StepValidationException
     */
    public function validate(string $formRequestClass, array $data): array
    {
        // Resolve FormRequest from container
        /** @var FormRequest $request */
        $request = $this->container->make($formRequestClass);

        // Merge data into request
        $request->merge($data);

        // Get validation rules from FormRequest
        $rules = $request->rules();

        // Run validation
        $validator = Validator::make($data, $rules, $request->messages(), $request->attributes());

        if ($validator->fails()) {
            throw new StepValidationException($validator->errors()->toArray());
        }

        return $validator->validated();
    }
}
