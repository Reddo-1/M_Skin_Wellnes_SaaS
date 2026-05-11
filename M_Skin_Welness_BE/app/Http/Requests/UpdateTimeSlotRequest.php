<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTimeSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('time_slot'));
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'nullable', 'string', 'max:50'],
            'start_time' => ['sometimes', 'date_format:H:i:s,H:i'],
            'end_time' => ['sometimes', 'date_format:H:i:s,H:i', 'after:start_time'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
