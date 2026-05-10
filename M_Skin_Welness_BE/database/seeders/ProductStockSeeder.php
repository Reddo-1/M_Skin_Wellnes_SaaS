<?php

namespace Database\Seeders;

use App\Models\Center;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductStockSeeder extends Seeder
{
    public function run(): void
    {
        $centerId = Center::query()->where('slug', 'demo')->value('id');

        if ($centerId === null) {
            return;
        }

        $stocks = [
            'Crema hidratante facial' => 20,
            'Sérum vitamina C'        => 15,
            'Aceite esencial corporal'=> 1000,
            'Esmalte uñas rojo'       => 12,
            'Mascarilla limpieza'     => 80,
        ];

        foreach ($stocks as $productName => $quantity) {
            $productId = DB::table('products')
                ->where('center_id', $centerId)
                ->where('name', $productName)
                ->value('id');

            if ($productId === null) {
                continue;
            }

            DB::table('product_stocks')->updateOrInsert(
                ['center_id' => $centerId, 'product_id' => $productId],
                [
                    'current_quantity' => $quantity,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
