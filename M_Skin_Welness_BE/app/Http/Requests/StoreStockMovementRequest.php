<?php

namespace App\Http\Requests;

use App\Models\StockMovement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', StockMovement::class);
    }

    public function rules(): array
    {
        $centerId = (int) $this->attributes->get('center_id');
        $allowedTypeIds = [
            (int) config('lookups.stock_movement_types.entrada'),
            (int) config('lookups.stock_movement_types.devolucion'),
        ];

        return [
            'product_id' => [
                'required',
                Rule::exists('products', 'id')->where('center_id', $centerId),
            ],
            'movement_type_id' => ['required', 'integer', Rule::in($allowedTypeIds)],
            'package_quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:200'],
        ];
    }
}
