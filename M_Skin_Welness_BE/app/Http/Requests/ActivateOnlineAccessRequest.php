<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ActivateOnlineAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');

        return $target instanceof User
            && $this->user()->can('activateOnlineAccess', $target);
    }

    public function rules(): array
    {
        $target = $this->route('user');
        $targetId = $target instanceof User ? $target->id : null;

        return [
            //si el usuario destino no tiene email, hay que aportarlo en el body
            'email' => [
                'nullable',
                'string',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($targetId),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $target = $this->route('user');

            if (!$target instanceof User) {
                return;
            }

            if ($target->email_verified_at !== null) {
                $v->errors()->add('email', 'Este usuario ya tiene acceso online.');
                return;
            }

            if ($target->email === null && empty($this->input('email'))) {
                $v->errors()->add('email', 'Introduce un correo para activar el acceso online.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Ya existe un usuario con ese correo.',
            'email.email' => 'El correo no tiene un formato válido.',
        ];
    }
}
