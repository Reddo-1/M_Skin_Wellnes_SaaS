<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangeSaleStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('changeStatus', $this->route('sale'));
    }

    public function rules(): array
    {
        return [
            'status_id' => ['required', 'integer', 'exists:sale_statuses,id'],
            'payment_method_id' => ['sometimes', 'nullable', 'integer', 'exists:payment_methods,id'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}
