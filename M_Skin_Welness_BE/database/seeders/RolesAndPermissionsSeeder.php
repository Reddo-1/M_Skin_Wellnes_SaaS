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

        $roomsPermissions = [
            'rooms.view',
            'rooms.create',
            'rooms.update',
            'rooms.delete',
        ];

        $machinesPermissions = [
            'machines.view',
            'machines.create',
            'machines.update',
            'machines.delete',
        ];

        $timeSlotsPermissions = [
            'time_slots.view',
            'time_slots.create',
            'time_slots.update',
            'time_slots.delete',
        ];

        $workerSchedulesPermissions = [
            'worker_schedules.view',
            'worker_schedules.create',
            'worker_schedules.update',
            'worker_schedules.delete',
        ];

        $workerAbsencesPermissions = [
            'worker_absences.view',
            'worker_absences.create',
            'worker_absences.update',
            'worker_absences.delete',
        ];

        $workerExtraAvailabilitiesPermissions = [
            'worker_extra_availabilities.view',
            'worker_extra_availabilities.create',
            'worker_extra_availabilities.update',
            'worker_extra_availabilities.delete',
        ];

        $usersPermissions = [
            'users.view',
            'users.create_staff',
            'users.create_client',
            'users.update',
            'users.deactivate',
        ];

        $clientProfilesPermissions = [
            'client_profiles.view',
            'client_profiles.create',
            'client_profiles.update',
        ];

        $skinEvaluationsPermissions = [
            'skin_evaluations.view',
            'skin_evaluations.create',
            'skin_evaluations.update',
        ];

        $permissions = array_merge(
                            $appointmentsPermissions,
                            $treatmentsPermissions,
                            $roomsPermissions,
                            $machinesPermissions,
                            $timeSlotsPermissions,
                            $workerSchedulesPermissions,
                            $workerAbsencesPermissions,
                            $workerExtraAvailabilitiesPermissions,
                            $usersPermissions,
                            $clientProfilesPermissions,
                            $skinEvaluationsPermissions
        );
        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $staffPermissions = [
            'appointments.view',
            'appointments.change_status',
            'treatments.view',
            'rooms.view',
            'machines.view',
            'time_slots.view',
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
                'rooms.view',
                'machines.view',
                'time_slots.view',
                'users.view',
                'users.create_client',
                'users.update',
                'users.deactivate',
                'client_profiles.view',
                'skin_evaluations.view',
            ],

            'rrhh' => array_merge(
                [
                    'appointments.view',
                    'treatments.view',
                    'rooms.view',
                    'machines.view',
                    'time_slots.view',
                    'users.view',
                    'users.create_staff',
                    'users.update',
                    'users.deactivate',
                ],
                $workerSchedulesPermissions,
                $workerAbsencesPermissions,
                $workerExtraAvailabilitiesPermissions,
            ),

            'diagnosticador' => array_merge(
                $staffPermissions,
                [
                    'client_profiles.view',
                    'client_profiles.create',
                    'client_profiles.update',
                ],
                $skinEvaluationsPermissions,
            ),
            'dermo_esteticien' => array_merge($staffPermissions, ['client_profiles.view', 'skin_evaluations.view']),
            'fisioterapeuta'   => array_merge($staffPermissions, ['client_profiles.view', 'skin_evaluations.view']),
            'manicurista'      => array_merge($staffPermissions, ['client_profiles.view', 'skin_evaluations.view']),
            'cliente' => [
                'appointments.view',
                'appointments.create',
                'appointments.change_status',
                'treatments.view',
                'rooms.view',
            ],
        ];

        foreach ($rolesWithPermissions as $roleName => $perms) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($perms);
        }
    }
}
