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
            ['name' => 'Crema hidratante facial', 'description' => 'Crema básica para rostro', 'sale_price' => 29.90, 'cost_price' => 12.00, 'doses_per_package' => 50, 'minimum_stock' => 25, 'is_sellable' => true],
            ['name' => 'Sérum vitamina C',        'description' => 'Sérum antioxidante',       'sale_price' => 39.50, 'cost_price' => 15.00, 'doses_per_package' => 30, 'minimum_stock' => 15, 'is_sellable' => true],
            ['name' => 'Aceite esencial corporal','description' => null,                       'sale_price' => 19.00, 'cost_price' => 8.00,  'doses_per_package' => 20, 'minimum_stock' => 10, 'is_sellable' => true],
            ['name' => 'Esmalte uñas rojo',       'description' => null,                       'sale_price' => 12.00, 'cost_price' => 4.50,  'doses_per_package' => 40, 'minimum_stock' => 20, 'is_sellable' => true],
            ['name' => 'Mascarilla limpieza',     'description' => 'Insumo de cabina',         'sale_price' => null,  'cost_price' => 2.50,  'doses_per_package' => 1,  'minimum_stock' => 30, 'is_sellable' => false],
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
