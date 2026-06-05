<?php

namespace App\Http\Requests;

use App\Models\Machine;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMachineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Machine::class);
    }

    public function rules(): array
    {
        $centerId = (int) $this->attributes->get('center_id');

        return [
            'name' => [
                'required', 'string', 'max:120',
                Rule::unique('machines', 'name')->where('center_id', $centerId),
            ],
            'is_mobile' => ['sometimes', 'boolean'],
            'fixed_room_id' => [
                'sometimes', 'nullable',
                'prohibited_if:is_mobile,true',
                Rule::exists('rooms', 'id')->where('center_id', $centerId),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
