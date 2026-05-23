<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StockMovementTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['id' => 1, 'name' => 'entrada'],
            ['id' => 2, 'name' => 'salida_venta'],
            ['id' => 3, 'name' => 'uso_sesion'],
            ['id' => 4, 'name' => 'ajuste_manual'],
            ['id' => 5, 'name' => 'devolucion'],
        ];

        DB::table('stock_movement_types')->upsert($types, ['id'], ['name']);

        DB::statement("SELECT setval('stock_movement_types_id_seq', (SELECT MAX(id) FROM stock_movement_types))");
    }
}
