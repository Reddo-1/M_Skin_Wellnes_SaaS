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
            'grid_position' => ['sometimes', 'array'],
            'grid_position.x' => ['required_with:grid_position', 'integer', 'min:0'],
            'grid_position.y' => ['required_with:grid_position', 'integer', 'min:0'],
            'grid_position.w' => ['required_with:grid_position', 'integer', 'min:1'],
            'grid_position.h' => ['required_with:grid_position', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
