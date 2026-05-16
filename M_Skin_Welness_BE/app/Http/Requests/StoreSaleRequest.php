<?php

namespace App\Http\Requests;

use App\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Sale::class);
    }

    public function rules(): array
    {
        $centerId = (int) $this->attributes->get('center_id');

        return [
            'client_id' => [
                'required',
                Rule::exists('users', 'id')->where('center_id', $centerId),
            ],
            'appointment_id' => [
                'nullable',
                Rule::exists('appointments', 'id')->where('center_id', $centerId),
            ],
            'discount' => ['sometimes', 'numeric', 'min:0'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'notes' => ['nullable', 'string', 'max:5000'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.type' => ['required', Rule::in(['treatment', 'product'])],
            'lines.*.reference_id' => ['required', 'integer'],
            'lines.*.description' => ['required', 'string', 'max:200'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.line_discount' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}
