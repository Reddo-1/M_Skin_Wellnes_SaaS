<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('product'));
    }

    public function rules(): array
    {
        $centerId = (int) $this->attributes->get('center_id');
        $productId = $this->route('product')->id;

        return [
            'name' => [
                'sometimes', 'string', 'max:120',
                Rule::unique('products', 'name')
                    ->where('center_id', $centerId)
                    ->ignore($productId),
            ],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'sale_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'cost_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'doses_per_package' => ['sometimes', 'integer', 'min:1'],
            'minimum_stock' => ['sometimes', 'numeric', 'min:0'],
            'is_sellable' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
