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
            ['name' => 'salida_venta'],
            ['name' => 'uso_sesion'],
            ['name' => 'ajuste_manual'],
            ['name' => 'devolucion'],
        ];

        DB::table('stock_movement_types')->upsert($types, ['name']);
    }
}
