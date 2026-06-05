<?php

namespace App\Http\Requests;

use App\Models\SkinEvaluation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSkinEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', SkinEvaluation::class);
    }

    public function rules(): array
    {
        $centerId = (int) $this->attributes->get('center_id');

        return [
            'client_profile_id' => [
                'required',
                Rule::exists('client_profiles', 'id')->where('center_id', $centerId),
            ],
            'skin_type_id' => ['required', 'integer', 'exists:skin_types,id'],
            'evaluation_date' => ['sometimes', 'date'],
            'general_notes' => ['nullable', 'string', 'max:5000'],
            'variation_ids' => ['nullable', 'array'],
            'variation_ids.*' => ['integer', 'exists:variations,id'],
        ];
    }
}
