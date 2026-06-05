<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Spatie\Permission\Models\Role;

class SyncUserRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageRoles', $this->route('user'));
    }

    public function rules(): array
    {
        return [
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
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

            if (in_array('superadmin', $roleNames, true)) {
                $v->errors()->add('role_ids', 'No se puede asignar el rol de superadministrador desde el panel.');
                return;
            }

            $assigningClient = in_array('cliente', $roleNames, true);
            $assigningStaff = !empty(array_diff($roleNames, ['cliente']));

            if ($assigningClient && !$actor->can('users.create_client')) {
                $v->errors()->add('role_ids', 'No tienes permiso para asignar el rol de cliente.');
            }

            if ($assigningStaff && !$actor->can('users.create_staff')) {
                $v->errors()->add('role_ids', 'No tienes permiso para asignar roles de trabajador.');
            }
        });
    }
}
