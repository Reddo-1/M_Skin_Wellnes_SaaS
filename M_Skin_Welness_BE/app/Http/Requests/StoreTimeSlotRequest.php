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
            //descanso interno opcional: ambos o ninguno, y dentro de la franja
            'break_start' => ['nullable', 'required_with:break_end', 'date_format:H:i:s,H:i', 'after_or_equal:start_time', 'before:end_time'],
            'break_end' => ['nullable', 'required_with:break_start', 'date_format:H:i:s,H:i', 'after:break_start', 'before_or_equal:end_time'],
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
