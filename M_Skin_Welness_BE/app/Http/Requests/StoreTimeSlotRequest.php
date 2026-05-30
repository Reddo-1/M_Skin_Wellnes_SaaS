<?php

namespace App\Http\Requests;

use App\Models\TimeSlot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTimeSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', TimeSlot::class);
    }

    public function rules(): array
    {
        $centerId = (int) $this->attributes->get('center_id');

        return [
            'name' => ['sometimes', 'nullable', 'string', 'max:50'],
            //se permite repetir la hora de inicio mientras el fin difiera; solo se bloquea la franja exacta del centro
            'start_time' => [
                'required', 'date_format:H:i:s,H:i',
                Rule::unique('time_slots')->where(function ($q) use ($centerId) {
                    return $q->where('center_id', $centerId)
                        ->where('end_time', $this->input('end_time'));
                }),
            ],
            'end_time' => ['required', 'date_format:H:i:s,H:i', 'after:start_time'],
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
