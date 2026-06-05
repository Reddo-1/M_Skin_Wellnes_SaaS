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
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'birth_date' => $data['birth_date'] ?? null,
                'password' => $hasPassword ? $data['password'] : Str::password(32),
                'registration_source' => 'staff',
                'is_active' => $data['is_active'] ?? true,
            ]);

            //si el admin fija contrasena, asume el correo como verificado; si no, queda pendiente de activacion online
            if ($hasPassword) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            $this->assignRolesById($user, $data['role_ids']);

            $this->auditLogs->record(
                action: 'user.created',
                actorUserId: $actorUserId,
                centerId: $centerId,
                metadata: ['user_id' => $user->id, 'role_ids' => $data['role_ids']],
            );

            //sin password fijada, le mandamos el correo para que la establezca el mismo
            if (!$hasPassword && $user->email !== null) {
                Password::sendResetLink(['email' => $user->email]);
            }

            return $user;
        });
    }

    public function registerSelfSignup(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'center_id' => $data['center_id'],
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'birth_date' => $data['birth_date'] ?? null,
                'password' => $data['password'],
                'registration_source' => 'online',
                'is_active' => true,
            ]);

            $user->assignRole('cliente');

            $this->auditLogs->record(
                action: 'user.created',
                centerId: $user->center_id,
                metadata: ['user_id' => $user->id, 'role' => 'cliente', 'source' => 'self_registration'],
            );

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

    //baja logica: ademas de inactivar, revoca los tokens para cerrar sus sesiones
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
        return DB::transaction(function () use ($user, $actorUserId) {
            $user->is_active = true;
            $user->save();

            $this->auditLogs->record(
                action: 'user.reactivated',
                actorUserId: $actorUserId,
                centerId: $user->center_id,
                metadata: ['user_id' => $user->id],
            );

            return $user;
        });
    }

    public function changePassword(User $user, string $newPassword): User
    {
        $user->password = $newPassword;
        $user->save();

        return $user;
    }

    public function activateOnlineAccess(User $user, ?string $email): User
    {
        return DB::transaction(function () use ($user, $email) {
            if ($email !== null && $email !== '') {
                $user->email = $email;
                $user->save();
            }

            //al completar el reset el cliente quedara verificado (AuthController::resetPassword)
            Password::sendResetLink(['email' => $user->email]);

            return $user;
        });
    }

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
