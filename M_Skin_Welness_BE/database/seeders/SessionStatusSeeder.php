<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SessionStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['id' => 1, 'name' => 'pendiente',      'sort_order' => 1],
            ['id' => 2, 'name' => 'confirmada',     'sort_order' => 2],
            ['id' => 3, 'name' => 'en_curso',       'sort_order' => 3],
            ['id' => 4, 'name' => 'realizada',      'sort_order' => 4],
            ['id' => 5, 'name' => 'cancelada',      'sort_order' => 5],
            ['id' => 6, 'name' => 'no_presentada',  'sort_order' => 6],
        ];

        DB::table('session_statuses')->upsert($statuses, ['id'], ['name', 'sort_order']);

        DB::statement("SELECT setval('session_statuses_id_seq', (SELECT MAX(id) FROM session_statuses))");
    }
}
