<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class UserService
{
    public function create(int $centerId, array $data): User
    {
        return DB::transaction(function () use ($centerId, $data) {
            $user = User::create([
                'center_id' => $centerId,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'birth_date' => $data['birth_date'] ?? null,
                'password' => $data['password'],
                //alta desde panel: el origen siempre es 'staff'. El auto-registro online va por otro flujo
                'registration_source' => 'staff',
                'is_active' => $data['is_active'] ?? true,
            ]);

            $this->assignRolesById($user, $data['role_ids']);

            return $user;
        });
    }

    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $user->fill($data)->save();

            return $user;
        });
    }

    //baja logica: marca el usuario como inactivo y revoca sus tokens activos
    public function deactivate(User $user): User
    {
        return DB::transaction(function () use ($user) {
            $user->is_active = false;
            $user->save();
            $user->tokens()->delete();

            return $user;
        });
    }

    public function activate(User $user): User
    {
        $user->is_active = true;
        $user->save();

        return $user;
    }

    public function changePassword(User $user, string $newPassword): User
    {
        $user->password = $newPassword;
        $user->save();

        return $user;
    }

    //reemplaza la lista completa de roles del usuario por los indicados
    public function syncRoles(User $user, array $roleIds): User
    {
        return DB::transaction(function () use ($user, $roleIds) {
            $this->assignRolesById($user, $roleIds);

            return $user;
        });
    }

    //resolvemos roles por id y sincronizamos via instancias para evitar ambiguedad en Spatie
    private function assignRolesById(User $user, array $roleIds): void
    {
        $roles = Role::query()->whereIn('id', $roleIds)->get();
        $user->syncRoles($roles);
    }
}
