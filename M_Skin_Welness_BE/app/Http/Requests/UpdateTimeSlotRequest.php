<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTimeSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('time_slot'));
    }

    public function rules(): array
    {
        $centerId = (int) $this->attributes->get('center_id');
        $timeSlotId = $this->route('time_slot')->id;

        return [
            'name' => ['sometimes', 'nullable', 'string', 'max:50'],
            //misma regla que en alta: se bloquea solo la franja exacta (inicio + fin) del centro, ignorando la propia
            'start_time' => [
                'sometimes', 'date_format:H:i:s,H:i',
                Rule::unique('time_slots')
                    ->where(function ($q) use ($centerId) {
                        return $q->where('center_id', $centerId)
                            ->where('end_time', $this->input('end_time'));
                    })
                    ->ignore($timeSlotId),
            ],
            'end_time' => ['sometimes', 'date_format:H:i:s,H:i', 'after:start_time'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'start_time.unique' => 'Ya existe una franja con ese mismo horario en el centro.',
        ];
    }
}
