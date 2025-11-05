<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Contracts;

use Invelity\WizardPackage\Exceptions\StepValidationException;

interface FormRequestValidatorInterface
{
    /**
     * Validate data using the given FormRequest class.
     *
     * @return array<string, mixed> Validated data
     *
     * @throws StepValidationException
     */
    public function validate(string $formRequestClass, array $data): array;
}
