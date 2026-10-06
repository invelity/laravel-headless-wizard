<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Tests\Fixtures\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class SummaryRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'terms' => ['accepted'],
            'coupon' => ['nullable', 'string'],
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return list<callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('coupon') === 'EXPIRED') {
                    $validator->errors()->add('coupon', 'The coupon has expired.');
                }
            },
        ];
    }
}
