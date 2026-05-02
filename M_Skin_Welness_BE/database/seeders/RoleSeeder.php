<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'superadmin'],
            ['name' => 'administrador'],
            ['name' => 'recepcionista'],
            ['name' => 'rrhh'],
            ['name' => 'diagnosticador'],
            ['name' => 'facialista'],
            ['name' => 'especialista_maquinaria'],
            ['name' => 'cliente'],
        ];

        DB::table('roles')->upsert($roles, ['name']);
    }
}
