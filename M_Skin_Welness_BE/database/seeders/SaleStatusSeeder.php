<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SaleStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['name' => 'pendiente'],
            ['name' => 'pagada'],
            ['name' => 'parcialmente_reembolsada'],
            ['name' => 'reembolsada'],
            ['name' => 'cancelada'],
        ];

        DB::table('sale_statuses')->upsert($statuses, ['name']);
    }
}
