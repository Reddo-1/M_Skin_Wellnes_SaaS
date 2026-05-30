<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\{Rule, Validator};

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            'name' => ['sometimes', 'string', 'max:120'],
            'email' => [
                'sometimes', 'nullable', 'string', 'email', 'max:150',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'birth_date' => ['sometimes', 'nullable', 'date', 'before:today'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            //solo aplica si el request realmente trae el email; un PATCH que no lo toca se deja pasar
            if (! $this->has('email')) {
                return;
            }

            //un trabajador sin email no tendria forma de iniciar sesion; el cliente walk-in si puede quedarse sin correo
            $roleNames = $this->route('user')->roles->pluck('name')->all();
            $isStaff = !empty(array_diff($roleNames, ['cliente']));

            if ($isStaff && empty($this->input('email'))) {
                $v->errors()->add('email', 'El correo es obligatorio para usuarios del personal.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Ya existe un usuario con ese correo.',
            'email.email' => 'El correo no tiene un formato valido.',
        ];
    }
}
