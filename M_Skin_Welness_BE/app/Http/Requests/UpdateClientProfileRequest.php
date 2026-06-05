<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClientProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('client_profile'));
    }

    public function rules(): array
    {
        //solo se editan las notas permanentes; el estado clinico cambia creando una nueva skin_evaluation
        return [
            'general_notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}
