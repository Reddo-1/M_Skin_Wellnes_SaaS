<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCenterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $centerId = $this->route('center')?->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'custom_domain' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9.-]+\.[a-z]{2,}$/i',
                Rule::unique('centers', 'custom_domain')->ignore($centerId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'custom_domain.regex' => 'El dominio personalizado no tiene un formato válido.',
            'custom_domain.unique' => 'Ese dominio ya está en uso por otro centro.',
        ];
    }
}
