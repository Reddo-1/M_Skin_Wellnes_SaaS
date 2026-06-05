<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMachineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('machine'));
    }

    public function rules(): array
    {
        $centerId = (int) $this->attributes->get('center_id');
        $machineId = $this->route('machine')->id;

        return [
            'name' => [
                'sometimes', 'string', 'max:120',
                Rule::unique('machines', 'name')
                    ->where('center_id', $centerId)
                    ->ignore($machineId),
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
