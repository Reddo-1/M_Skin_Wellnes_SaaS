<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkerExtraAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('worker_extra_availability'));
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
            'start_time' => ['sometimes', 'date_format:H:i:s,H:i'],
            'end_time' => ['sometimes', 'date_format:H:i:s,H:i', 'after:start_time'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:120'],
        ];
    }
}
