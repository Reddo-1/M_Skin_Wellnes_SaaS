<?php

namespace App\Http\Requests;

use App\Models\Room;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Room::class);
    }

    public function rules(): array
    {
        $centerId = (int) $this->attributes->get('center_id');

        return [
            'name' => [
                'required', 'string', 'max:120',
                Rule::unique('rooms', 'name')->where('center_id', $centerId),
            ],
            'grid_position' => ['sometimes', 'nullable', 'array'],
            'grid_position.x' => ['required_with:grid_position', 'integer', 'min:0'],
            'grid_position.y' => ['required_with:grid_position', 'integer', 'min:0'],
            'grid_position.w' => ['required_with:grid_position', 'integer', 'min:1'],
            'grid_position.h' => ['required_with:grid_position', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
