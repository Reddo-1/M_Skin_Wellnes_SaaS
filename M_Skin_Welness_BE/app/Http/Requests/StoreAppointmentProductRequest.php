<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('attachProducts', $this->route('appointment'));
    }

    public function rules(): array
    {
        $centerId = (int) $this->attributes->get('center_id');

        return [
            'product_id' => [
                'required',
                Rule::exists('products', 'id')->where('center_id', $centerId),
            ],
            'quantity' => ['required', 'numeric', 'min:0.001'],
        ];
    }
}
