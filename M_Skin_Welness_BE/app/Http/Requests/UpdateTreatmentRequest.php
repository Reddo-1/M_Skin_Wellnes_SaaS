<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTreatmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('treatment'));
    }

    public function rules(): array
    {
        $centerId = (int) $this->attributes->get('center_id');
        $treatmentId = $this->route('treatment')->id;

        return [
            'name' => [
                'sometimes', 'string', 'max:120',
                Rule::unique('treatments', 'name')
                    ->where('center_id', $centerId)
                    ->ignore($treatmentId),
            ],
            'duration_minutes' => ['sometimes', 'integer', 'min:1'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'machine_ids' => ['sometimes', 'array'],
            'machine_ids.*' => [
                'integer',
                Rule::exists('machines', 'id')->where('center_id', $centerId),
            ],
            'role_ids' => ['sometimes', 'array'],
            'role_ids.*' => ['integer', Rule::exists('roles', 'id')],
        ];
    }
}
