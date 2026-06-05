<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSkinEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('skin_evaluation'));
    }

    public function rules(): array
    {
        return [
            'skin_type_id' => ['sometimes', 'integer', 'exists:skin_types,id'],
            'evaluation_date' => ['sometimes', 'date'],
            'general_notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'variation_ids' => ['sometimes', 'array'],
            'variation_ids.*' => ['integer', 'exists:variations,id'],
        ];
    }
}
