<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Appointment::class);
    }

    public function rules(): array
    {
        $centerId = (int) $this->attributes->get('center_id');

        return [
            'treatment_id' => [
                'required',
                Rule::exists('treatments', 'id')->where('center_id', $centerId),
            ],
            'room_id' => [
                'required',
                Rule::exists('rooms', 'id')->where('center_id', $centerId),
            ],
            'client_id' => [
                'required',
                Rule::exists('users', 'id')->where('center_id', $centerId),
            ],
            'worker_id' => [
                'required',
                Rule::exists('users', 'id')->where('center_id', $centerId),
            ],
            'machine_id' => [
                'nullable',
                Rule::exists('machines', 'id')->where('center_id', $centerId),
            ],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'booking_source' => ['required', 'string', Rule::in(['panel', 'online'])],
            'notes' => ['nullable', 'string', 'max:5000'],
            'assistant_ids' => ['nullable', 'array'],
            'assistant_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->where('center_id', $centerId),
                'different:worker_id',
            ],
        ];
    }
}
