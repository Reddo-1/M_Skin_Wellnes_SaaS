<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SessionStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['name' => 'pendiente',   'sort_order' => 1],
            ['name' => 'confirmada',  'sort_order' => 2],
            ['name' => 'en curso',    'sort_order' => 3],
            ['name' => 'completada',  'sort_order' => 4],
            ['name' => 'cancelada',   'sort_order' => 5],
            ['name' => 'no asistió',  'sort_order' => 6],
        ];

        DB::table('session_statuses')->upsert($statuses, ['name'], ['sort_order']);
    }
}
