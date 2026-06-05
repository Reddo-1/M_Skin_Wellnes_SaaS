<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateTreatmentConsentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('treatment_consent'));
    }

    public function rules(): array
    {
        return [
            //is_suitable: true = apto, false = no apto
            'is_suitable' => ['required', 'boolean'],
            'unsuitability_reason' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            //marcar no apto exige un motivo: dejar la contraindicación sin justificar no es defendible clínicamente
            if ($this->input('is_suitable') === false && trim((string) $this->input('unsuitability_reason')) === '') {
                $v->errors()->add('unsuitability_reason', 'Indica el motivo por el que el cliente no es apto.');
            }
        });
    }
}
