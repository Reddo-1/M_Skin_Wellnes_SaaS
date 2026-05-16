<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTreatmentConsentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('treatment_consent'));
    }

    public function rules(): array
    {
        return [
            'review_date' => ['sometimes', 'date'],
            'is_suitable' => ['sometimes', 'boolean'],
            'unsuitability_reason' => ['sometimes', 'nullable', 'string', 'max:150'],
            'treatment_consent' => ['sometimes', 'boolean'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}
