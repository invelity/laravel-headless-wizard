<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Services\Validation;

use Illuminate\Container\Container;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Foundation\Http\FormRequest;
use Invelity\WizardPackage\Contracts\FormRequestValidatorInterface;
use Invelity\WizardPackage\Exceptions\StepValidationException;

final readonly class FormRequestValidator implements FormRequestValidatorInterface
{
    public function __construct(
        private Container $container,
        private ValidationFactory $validationFactory,
    ) {}

    /**
     * Validate data using the given FormRequest class.
     *
     * @throws StepValidationException
     */
    public function validate(?string $formRequestClass, array $data): array
    {
        // If no FormRequest provided, return data as-is (no validation)
        if ($formRequestClass === null) {
            return $data;
        }

        // Create a new instance without resolving through container
        // to avoid triggering authorization and other middleware
        /** @var FormRequest $formRequest */
        $formRequest = new $formRequestClass;

        // Set the container on the form request
        $formRequest->setContainer($this->container);

        // Get validation rules
        $rules = $formRequest->rules();

        // If no rules defined, return data as-is
        if (empty($rules)) {
            return $data;
        }

        // Create validator using the validation factory
        $validator = $this->validationFactory->make(
            $data,
            $rules,
            $formRequest->messages(),
            $formRequest->attributes()
        );

        if ($validator->fails()) {
            throw new StepValidationException($validator);
        }

        return $validator->validated();
    }
}
