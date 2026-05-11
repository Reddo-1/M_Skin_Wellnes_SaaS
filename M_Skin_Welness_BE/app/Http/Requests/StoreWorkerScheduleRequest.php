<?php

namespace App\Http\Requests;

use App\Models\WorkerSchedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkerScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', WorkerSchedule::class);
    }

    public function rules(): array
    {
        $centerId = (int) $this->attributes->get('center_id');

        return [
            'worker_id' => [
                'required', 'integer',
                Rule::exists('users', 'id')->where('center_id', $centerId),
            ],
            'weekday' => ['required', 'integer', 'between:1,7'],
            'time_slot_id' => [
                'required', 'integer',
                Rule::exists('time_slots', 'id')->where('center_id', $centerId),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }
}
