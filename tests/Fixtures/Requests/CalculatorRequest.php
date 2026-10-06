<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Tests\Fixtures\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CalculatorRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'weight' => ['required', 'numeric', 'min:0.1'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($weight = $this->input('weight'))) {
            $this->merge(['weight' => str_replace(',', '.', $weight)]);
        }
    }
}
