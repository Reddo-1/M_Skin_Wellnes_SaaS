<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StockMovementTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'entrada'],
            ['name' => 'salida por venta'],
            ['name' => 'uso en sesión'],
            ['name' => 'ajuste manual'],
            ['name' => 'devolución'],
        ];

        DB::table('stock_movement_types')->upsert($types, ['name']);
    }
}
