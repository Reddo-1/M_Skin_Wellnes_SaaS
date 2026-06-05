<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdjustProductStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('adjust', $this->route('product_stock'));
    }

    public function rules(): array
    {
        return [
            'new_quantity' => ['required', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'max:200'],
        ];
    }
}
