<?php

namespace App\Http\Requests;

use App\Models\TreatmentConsent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTreatmentConsentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', TreatmentConsent::class);
    }

    public function rules(): array
    {
        $centerId = (int) $this->attributes->get('center_id');

        return [
            'user_id' => [
                'required',
                Rule::exists('users', 'id')->where('center_id', $centerId),
            ],
            'treatment_id' => [
                'required',
                Rule::exists('treatments', 'id')->where('center_id', $centerId),
            ],
            'review_date' => ['sometimes', 'date'],
            'is_suitable' => ['required', 'boolean'],
            'unsuitability_reason' => ['nullable', 'string', 'max:150', 'required_if:is_suitable,false'],
            'treatment_consent' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
