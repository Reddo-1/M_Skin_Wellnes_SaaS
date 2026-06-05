<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SaleStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['id' => 1, 'name' => 'pendiente'],
            ['id' => 2, 'name' => 'pagada'],
            ['id' => 3, 'name' => 'parcialmente_reembolsada'],
            ['id' => 4, 'name' => 'reembolsada'],
            ['id' => 5, 'name' => 'cancelada'],
        ];

        DB::table('sale_statuses')->upsert($statuses, ['id'], ['name']);

        DB::statement("SELECT setval('sale_statuses_id_seq', (SELECT MAX(id) FROM sale_statuses))");
    }
}
