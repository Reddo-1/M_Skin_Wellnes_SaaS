<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkerAbsenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('worker_absence'));
    }

    public function rules(): array
    {
        $centerId = (int) $this->attributes->get('center_id');

        return [
            'worker_id' => [
                'sometimes', 'integer',
                Rule::exists('users', 'id')->where('center_id', $centerId),
            ],
            'date' => ['sometimes', 'date'],
            'is_full_day' => ['sometimes', 'boolean'],
            'start_time' => ['sometimes', 'nullable', 'date_format:H:i:s,H:i'],
            'end_time' => ['sometimes', 'nullable', 'date_format:H:i:s,H:i', 'after:start_time'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:120'],
            'absence_type_id' => ['sometimes', 'nullable', 'integer', Rule::exists('absence_types', 'id')],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}
