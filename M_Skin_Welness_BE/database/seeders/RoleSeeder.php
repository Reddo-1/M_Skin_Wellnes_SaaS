<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = [
            'superadmin',
            'administrador',
            'recepcionista',
            'rrhh',
            'diagnosticador',
            'facialista',
            'especialista_maquinaria',
            'cliente',
        ];

        foreach ($roles as $name) {
            Role::findOrCreate($name, 'web');
        }
    }
}
