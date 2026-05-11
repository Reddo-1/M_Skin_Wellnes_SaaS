<?php

namespace App\Http\Requests;

use App\Models\TimeSlot;
use Illuminate\Foundation\Http\FormRequest;

class StoreTimeSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', TimeSlot::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'nullable', 'string', 'max:50'],
            'start_time' => ['required', 'date_format:H:i:s,H:i'],
            'end_time' => ['required', 'date_format:H:i:s,H:i', 'after:start_time'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
