<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('appointment'));
    }

    public function rules(): array
    {
        $centerId = (int) $this->attributes->get('center_id');

        return [
            'treatment_id' => [
                'sometimes',
                Rule::exists('treatments', 'id')->where('center_id', $centerId),
            ],
            'room_id' => [
                'sometimes',
                Rule::exists('rooms', 'id')->where('center_id', $centerId),
            ],
            'worker_id' => [
                'sometimes',
                Rule::exists('users', 'id')->where('center_id', $centerId),
            ],
            'machine_id' => [
                'sometimes',
                'nullable',
                Rule::exists('machines', 'id')->where('center_id', $centerId),
            ],
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['sometimes', 'date', 'after:starts_at'],
            'reserved_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'assistant_ids' => ['sometimes', 'array'],
            'assistant_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->where('center_id', $centerId),
            ],
        ];
    }
}
