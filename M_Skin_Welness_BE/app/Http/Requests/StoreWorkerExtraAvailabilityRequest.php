<?php

namespace App\Http\Requests;

use App\Models\WorkerExtraAvailability;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkerExtraAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', WorkerExtraAvailability::class);
    }

    public function rules(): array
    {
        $centerId = (int) $this->attributes->get('center_id');

        return [
            'worker_id' => [
                'required', 'integer',
                Rule::exists('users', 'id')->where('center_id', $centerId),
            ],
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i:s,H:i'],
            'end_time' => ['required', 'date_format:H:i:s,H:i', 'after:start_time'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:120'],
        ];
    }
}
