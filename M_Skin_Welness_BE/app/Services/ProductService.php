<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductService
{
    public function create(int $centerId, array $data): Product
    {
        return DB::transaction(function () use ($centerId, $data) {
            $product = Product::create(array_merge($data, ['center_id' => $centerId]));

            $product->stock()->create([
                'center_id' => $centerId,
                'current_quantity' => 0,
            ]);

            return $product->load('stock');
        });
    }

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $product->fill($data)->save();

            return $product;
        });
    }

    public function delete(Product $product): void
    {
        $hasMovements = DB::table('stock_movements')
            ->where('product_id', $product->id)
            ->exists();

        $hasSales = DB::table('sale_lines')
            ->where('type', 'product')
            ->where('reference_id', $product->id)
            ->exists();

        $hasAppointments = DB::table('appointment_products')
            ->where('product_id', $product->id)
            ->exists();

        if ($hasMovements || $hasSales || $hasAppointments) {
            throw ValidationException::withMessages([
                'product' => ['No se puede borrar el producto porque tiene historial de movimientos, ventas o uso en sesiones. Desactívalo en su lugar.'],
            ]);
        }

        DB::transaction(function () use ($product) {
            DB::table('product_stocks')
                ->where('product_id', $product->id)
                ->delete();

            $product->delete();
        });
    }
}
