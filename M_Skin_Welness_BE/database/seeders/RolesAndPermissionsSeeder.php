<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $appointmentsPermissions = [
            'appointments.view',
            'appointments.create',
            'appointments.update',
            'appointments.delete',
            'appointments.change_status',
        ];

        $permissions = array_merge(
                            $appointmentsPermissions
        );
        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $rolesWithPermissions = [
            'superadmin' => $permissions,
            'administrador' => $permissions,
            'recepcionista' => [
                'appointments.view',
                'appointments.create',
                'appointments.update',
                'appointments.change_status',
            ],
            'rrhh' => [
                'appointments.view',
            ],
            'diagnosticador' => [
                'appointments.view',
                'appointments.change_status',
            ],
            'facialista' => [
                'appointments.view',
                'appointments.change_status',
            ],
            'especialista_maquinaria' => [
                'appointments.view',
                'appointments.change_status',
            ],
            'cliente' => [
                'appointments.view',
                'appointments.create',
                'appointments.change_status',
            ],
        ];

        foreach ($rolesWithPermissions as $roleName => $perms) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($perms);
        }
    }
}
