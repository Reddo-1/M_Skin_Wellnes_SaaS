<?php

namespace App\Http\Requests;

use App\Models\Treatment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTreatmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Treatment::class);
    }

    public function rules(): array
    {
        $centerId = (int) $this->attributes->get('center_id');

        return [
            'name' => [
                'required', 'string', 'max:120',
                Rule::unique('treatments', 'name')->where('center_id', $centerId),
            ],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'margin_minutes' => ['sometimes', 'integer', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
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
