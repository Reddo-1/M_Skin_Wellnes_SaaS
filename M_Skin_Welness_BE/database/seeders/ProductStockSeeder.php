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

        //todas las cantidades van en dosis. La conversion paquete→dosis ya esta hecha aqui:
        //Crema 100 = 2 botes (50 dosis/bote); Sérum 90 = 3 frascos (30 dosis); etc.
        $stocks = [
            'Crema hidratante facial' => 100,
            'Sérum vitamina C'        => 90,
            'Aceite esencial corporal'=> 60,
            'Esmalte uñas rojo'       => 80,
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
