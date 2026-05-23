<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\{DB, Password};
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UserService
{
    public function __construct(private readonly AuditLogService $auditLogs)
    {
    }

    public function create(int $centerId, int $actorUserId, array $data): User
    {
        return DB::transaction(function () use ($centerId, $actorUserId, $data) {
            $hasPassword = !empty($data['password']);

            $user = User::create([
                'center_id' => $centerId,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'birth_date' => $data['birth_date'] ?? null,
                'password' => $hasPassword ? $data['password'] : Str::password(32),
                //alta desde panel: el origen siempre es 'staff'. El auto-registro online va por otro flujo
                'registration_source' => 'staff',
                'is_active' => $data['is_active'] ?? true,
            ]);

            //el admin confia en el email que ha introducido; verificacion implicita
            $user->forceFill(['email_verified_at' => now()])->save();

            $this->assignRolesById($user, $data['role_ids']);

            $this->auditLogs->record(
                action: 'user.created',
                actorUserId: $actorUserId,
                centerId: $centerId,
                metadata: ['user_id' => $user->id, 'role_ids' => $data['role_ids']],
            );

            //si el admin no fijo password, el usuario la establece desde un correo
            if (!$hasPassword) {
                Password::sendResetLink(['email' => $user->email]);
            }

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
    public function deactivate(User $user, int $actorUserId): User
    {
        return DB::transaction(function () use ($user, $actorUserId) {
            $user->is_active = false;
            $user->save();
            $user->tokens()->delete();

            $this->auditLogs->record(
                action: 'user.deactivated',
                actorUserId: $actorUserId,
                centerId: $user->center_id,
                metadata: ['user_id' => $user->id],
            );

            return $user;
        });
    }

    public function activate(User $user, int $actorUserId): User
    {
        $user->is_active = true;
        $user->save();

        $this->auditLogs->record(
            action: 'user.reactivated',
            actorUserId: $actorUserId,
            centerId: $user->center_id,
            metadata: ['user_id' => $user->id],
        );

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
