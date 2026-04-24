<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'admin'],
            ['name' => 'superadmin'],
            ['name' => 'administrador'],
            ['name' => 'recepcionista'],
            ['name' => 'facialista'],
            ['name' => 'diagnosticador'],
            ['name' => 'especialista en maquinología'],
            ['name' => 'cliente'],
        ];

        DB::table('roles')->upsert($roles, ['name']);
    }
}
