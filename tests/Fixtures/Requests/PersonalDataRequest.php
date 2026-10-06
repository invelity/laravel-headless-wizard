<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Tests\Fixtures\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PersonalDataRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
        ];
    }
}
