<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('room'));
    }

    public function rules(): array
    {
        $centerId = (int) $this->attributes->get('center_id');
        $roomId = $this->route('room')->id;

        return [
            'name' => [
                'sometimes', 'string', 'max:120',
                Rule::unique('rooms', 'name')
                    ->where('center_id', $centerId)
                    ->ignore($roomId),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
