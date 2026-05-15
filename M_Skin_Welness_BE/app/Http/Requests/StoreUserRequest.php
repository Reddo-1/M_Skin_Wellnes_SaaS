<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Spatie\Permission\Models\Role;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            //un usuario puede tener varios roles a la vez (ej: recepcionista + diagnosticador)
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $roleIds = $this->input('role_ids', []);

            if (empty($roleIds)) {
                return;
            }

            $roleNames = Role::query()->whereIn('id', $roleIds)->pluck('name')->all();
            $actor = $this->user();

            //superadmin nunca se asigna desde la API
            if (in_array('superadmin', $roleNames, true)) {
                $v->errors()->add('role_ids', 'No se puede asignar el rol de superadministrador desde el panel.');
                return;
            }

            $assigningClient = in_array('cliente', $roleNames, true);
            $assigningStaff = !empty(array_diff($roleNames, ['cliente']));

            if ($assigningClient && !$actor->can('users.create_client')) {
                $v->errors()->add('role_ids', 'No tienes permiso para dar de alta a clientes.');
            }

            if ($assigningStaff && !$actor->can('users.create_staff')) {
                $v->errors()->add('role_ids', 'No tienes permiso para dar de alta a trabajadores.');
            }
        });
    }
}
