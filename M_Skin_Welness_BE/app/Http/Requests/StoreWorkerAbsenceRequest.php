<?php

namespace App\Http\Requests;

use App\Models\WorkerAbsence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkerAbsenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', WorkerAbsence::class);
    }

    public function rules(): array
    {
        $centerId = (int) $this->attributes->get('center_id');

        return [
            'worker_id' => [
                'required', 'integer',
                Rule::exists('users', 'id')->where('center_id', $centerId),
            ],
            //rango de fechas: para un solo día, mandar from === to
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'is_full_day' => ['sometimes', 'boolean'],
            'start_time' => ['required_if:is_full_day,false', 'nullable', 'date_format:H:i:s,H:i'],
            'end_time' => ['required_if:is_full_day,false', 'nullable', 'date_format:H:i:s,H:i', 'after:start_time'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:120'],
            'absence_type_id' => ['sometimes', 'nullable', 'integer', Rule::exists('absence_types', 'id')],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}
