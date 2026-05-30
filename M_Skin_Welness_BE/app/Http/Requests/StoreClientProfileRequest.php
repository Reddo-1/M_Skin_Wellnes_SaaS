<?php

namespace App\Http\Requests;

use App\Models\{ClientProfile, SkinEvaluation};
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        //crear la ficha arranca su primera evaluacion en la misma transaccion, asi que exige ambos permisos
        return $this->user()->can('create', ClientProfile::class)
            && $this->user()->can('create', SkinEvaluation::class);
    }

    public function rules(): array
    {
        $centerId = (int) $this->attributes->get('center_id');

        return [
            'user_id' => [
                'required',
                Rule::exists('users', 'id')->where('center_id', $centerId),
            ],
            //un cliente solo puede tener una ficha facial y una corporal por centro
            'body_type' => [
                'required',
                Rule::in(['facial', 'corporal']),
                Rule::unique('client_profiles')->where(function ($q) use ($centerId) {
                    return $q->where('center_id', $centerId)
                        ->where('user_id', $this->input('user_id'));
                }),
            ],
            'general_notes' => ['nullable', 'string', 'max:5000'],
            //la ficha nace siempre con su primera evaluacion
            'evaluation' => ['required', 'array'],
            'evaluation.skin_type_id' => ['required', 'integer', 'exists:skin_types,id'],
            'evaluation.evaluation_date' => ['sometimes', 'date'],
            'evaluation.general_notes' => ['nullable', 'string', 'max:5000'],
            'evaluation.variation_ids' => ['nullable', 'array'],
            'evaluation.variation_ids.*' => ['integer', 'exists:variations,id'],
        ];
    }
}
