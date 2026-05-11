<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkerScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('worker_schedule'));
    }

    public function rules(): array
    {
        $centerId = (int) $this->attributes->get('center_id');

        return [
            'worker_id' => [
                'sometimes', 'integer',
                Rule::exists('users', 'id')->where('center_id', $centerId),
            ],
            'weekday' => ['sometimes', 'integer', 'between:1,7'],
            'time_slot_id' => [
                'sometimes', 'integer',
                Rule::exists('time_slots', 'id')->where('center_id', $centerId),
            ],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
        ];
    }
}
