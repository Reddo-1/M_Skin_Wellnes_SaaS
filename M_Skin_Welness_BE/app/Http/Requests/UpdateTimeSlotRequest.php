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
            //descanso interno opcional: ambos o ninguno, y dentro de la franja
            'break_start' => ['sometimes', 'nullable', 'required_with:break_end', 'date_format:H:i:s,H:i', 'after_or_equal:start_time', 'before:end_time'],
            'break_end' => ['sometimes', 'nullable', 'required_with:break_start', 'date_format:H:i:s,H:i', 'after:break_start', 'before_or_equal:end_time'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'start_time.unique' => 'Ya existe una franja con ese mismo horario en el centro.',
            'break_start.required_with' => 'Indica también el inicio del descanso.',
            'break_end.required_with' => 'Indica también el fin del descanso.',
            'break_start.after_or_equal' => 'El descanso no puede empezar antes de la franja.',
            'break_start.before' => 'El descanso debe quedar dentro de la franja.',
            'break_end.after' => 'El fin del descanso debe ser posterior a su inicio.',
            'break_end.before_or_equal' => 'El descanso no puede terminar después de la franja.',
        ];
    }
}
