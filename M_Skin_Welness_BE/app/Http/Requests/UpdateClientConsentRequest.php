<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClientConsentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('client_consent'));
    }

    public function rules(): array
    {
        return [
            'clinical_photos_consent' => ['sometimes', 'boolean'],
            'marketing_data_consent' => ['sometimes', 'boolean'],
            'commercial_images_consent' => ['sometimes', 'boolean'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}
