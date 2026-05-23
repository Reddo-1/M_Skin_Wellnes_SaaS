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
            'appointments.attach_products',
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

        $treatmentConsentsPermissions = [
            'treatment_consents.view',
            'treatment_consents.create',
            'treatment_consents.update',
        ];

        $clientConsentsPermissions = [
            'client_consents.view',
            'client_consents.create',
            'client_consents.update',
        ];

        $productsPermissions = [
            'products.view',
            'products.create',
            'products.update',
            'products.delete',
        ];

        $productStocksPermissions = [
            'product_stocks.view',
            'product_stocks.adjust',
        ];

        $stockMovementsPermissions = [
            'stock_movements.view',
            'stock_movements.create',
        ];

        $salesPermissions = [
            'sales.view',
            'sales.create',
            'sales.change_status',
        ];

        $invoicesPermissions = [
            'invoices.view',
        ];

        $userFilesPermissions = [
            'user_files.view',
            'user_files.upload',
            'user_files.delete',
        ];

        $plansPermissions = [
            'plans.view',
        ];

        $centersPermissions = [
            'centers.view',
            'centers.update',
        ];

        $centerFilesPermissions = [
            'center_files.view',
            'center_files.upload',
            'center_files.delete',
        ];

        $subscriptionsPermissions = [
            'subscriptions.view',
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
            $skinEvaluationsPermissions,
            $treatmentConsentsPermissions,
            $clientConsentsPermissions,
            $productsPermissions,
            $productStocksPermissions,
            $stockMovementsPermissions,
            $salesPermissions,
            $invoicesPermissions,
            $userFilesPermissions,
            $plansPermissions,
            $centersPermissions,
            $centerFilesPermissions,
            $subscriptionsPermissions,
        );
        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $staffPermissions = [
            'appointments.view',
            'appointments.change_status',
            'appointments.attach_products',
            'treatments.view',
            'rooms.view',
            'machines.view',
            'time_slots.view',
            'products.view',
            'product_stocks.view',
            'center_files.view',
        ];

        $practitionerExtraPermissions = [
            'client_profiles.view',
            'skin_evaluations.view',
            'treatment_consents.view',
            'client_consents.view',
            'user_files.view',
        ];

        $rolesWithPermissions = [
            
            'superadmin' => $permissions,

            'administrador' => $permissions,

            'recepcionista' => array_merge(
                $staffPermissions,
                $practitionerExtraPermissions,
                [
                    'appointments.create',
                    'appointments.update',
                    'users.view',
                    'users.create_client',
                    'users.update',
                    'users.deactivate',
                    'client_consents.create',
                    'client_consents.update',
                    'stock_movements.view',
                    'stock_movements.create',
                    'sales.view',
                    'sales.create',
                    'sales.change_status',
                    'invoices.view',
                ],
            ),

            'rrhh' => array_merge(
                [
                    'appointments.view',
                    'treatments.view',
                    'time_slots.view',
                    'time_slots.create',
                    'time_slots.update',
                    'users.view',
                    'users.create_staff',
                    'users.update',
                    'users.deactivate',
                    'center_files.view',
                ],
                $workerSchedulesPermissions,
                $workerAbsencesPermissions,
                $workerExtraAvailabilitiesPermissions,
            ),

            'diagnosticador' => array_merge(
                $staffPermissions,
                $practitionerExtraPermissions,
                ['client_profiles.create', 'client_profiles.update'],
                ['skin_evaluations.create', 'skin_evaluations.update'],
                ['treatment_consents.create', 'treatment_consents.update'],
                ['user_files.upload', 'user_files.delete'],
            ),
            'dermo_esteticien' => array_merge($staffPermissions, $practitionerExtraPermissions),
            'fisioterapeuta'   => array_merge($staffPermissions, $practitionerExtraPermissions),
            'manicurista'      => array_merge($staffPermissions, $practitionerExtraPermissions),
            'cliente' => [
                'appointments.view',
                'treatments.view',
                'products.view',
                'center_files.view',
                'client_consents.view',
                'treatment_consents.view',
                'user_files.view',
            ],
        ];

        foreach ($rolesWithPermissions as $roleName => $perms) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($perms);
        }
    }
}
