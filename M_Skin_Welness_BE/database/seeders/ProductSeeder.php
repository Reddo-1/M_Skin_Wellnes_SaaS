<?php

namespace Database\Seeders;

use App\Models\Center;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $centerId = Center::query()->where('slug', 'demo')->value('id');

        if ($centerId === null) {
            return;
        }

        $products = [
            ['name' => 'Crema hidratante facial', 'description' => 'Crema básica para rostro', 'measurement_unit' => 'unit', 'sale_price' => 29.90, 'cost_price' => 12.00, 'minimum_stock' => 5, 'is_sellable' => true],
            ['name' => 'Sérum vitamina C',         'description' => 'Sérum antioxidante',     'measurement_unit' => 'unit', 'sale_price' => 39.50, 'cost_price' => 15.00, 'minimum_stock' => 5, 'is_sellable' => true],
            ['name' => 'Aceite esencial corporal', 'description' => null,                     'measurement_unit' => 'ml',   'sale_price' => 19.00, 'cost_price' => 8.00,  'minimum_stock' => 200, 'is_sellable' => true],
            ['name' => 'Esmalte uñas rojo',        'description' => null,                     'measurement_unit' => 'unit', 'sale_price' => 12.00, 'cost_price' => 4.50,  'minimum_stock' => 3, 'is_sellable' => true],
            ['name' => 'Mascarilla limpieza',      'description' => 'Insumo de cabina',       'measurement_unit' => 'unit', 'sale_price' => null,  'cost_price' => 2.50,  'minimum_stock' => 30, 'is_sellable' => false],
        ];

        foreach ($products as $p) {
            DB::table('products')->updateOrInsert(
                ['center_id' => $centerId, 'name' => $p['name']],
                array_merge($p, [
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
