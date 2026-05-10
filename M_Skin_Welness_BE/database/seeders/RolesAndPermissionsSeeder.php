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

        $treatmentsPermissions = [
            'treatments.view',
            'treatments.create',
            'treatments.update',
            'treatments.delete',
        ];

        $permissions = array_merge(
                            $appointmentsPermissions,
                            $treatmentsPermissions
        );
        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $staffPermissions = [
            'appointments.view',
            'appointments.change_status',
            'treatments.view',
        ];

        $rolesWithPermissions = [
            
            'superadmin' => $permissions,

            'administrador' => $permissions,

            'recepcionista' => [
                'appointments.view',
                'appointments.create',
                'appointments.update',
                'appointments.change_status',
                'treatments.view',
            ],

            'rrhh' => [
                'appointments.view',
                'treatments.view',
            ],

            'diagnosticador' => $staffPermissions,
            'dermo_esteticien' => $staffPermissions,
            'fisioterapeuta' => $staffPermissions,
            'manicurista' => $staffPermissions,
            'cliente' => [
                'appointments.view',
                'appointments.create',
                'appointments.change_status',
                'treatments.view',
            ],
        ];

        foreach ($rolesWithPermissions as $roleName => $perms) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($perms);
        }
    }
}
