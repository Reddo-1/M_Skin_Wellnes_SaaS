<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Product::class);
    }

    public function rules(): array
    {
        $centerId = (int) $this->attributes->get('center_id');

        return [
            'name' => [
                'required', 'string', 'max:120',
                Rule::unique('products', 'name')->where('center_id', $centerId),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'doses_per_package' => ['sometimes', 'integer', 'min:1'],
            'minimum_stock' => ['sometimes', 'numeric', 'min:0'],
            'is_sellable' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
