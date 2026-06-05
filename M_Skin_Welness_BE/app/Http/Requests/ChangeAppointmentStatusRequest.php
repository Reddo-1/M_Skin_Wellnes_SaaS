<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeAppointmentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('changeStatus', $this->route('appointment'));
    }

    public function rules(): array
    {
        $centerId = (int) $this->attributes->get('center_id');

        return [
            'status_id' => ['required', 'integer', 'exists:session_statuses,id'],
            //productos consumidos: solo se aplican al pasar a 'realizada'
            'products' => ['sometimes', 'array'],
            'products.*.product_id' => [
                'required_with:products',
                'integer',
                'distinct',
                Rule::exists('products', 'id')->where('center_id', $centerId),
            ],
            'products.*.quantity' => ['required_with:products', 'numeric', 'gt:0'],
        ];
    }
}
