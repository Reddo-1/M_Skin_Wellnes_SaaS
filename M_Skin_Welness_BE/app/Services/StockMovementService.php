<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\StockMovementType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockMovementService
{
    //variante para flujos en paquetes (entradas de proveedor, devoluciones, venta presencial).
    public function registerByPackages(
        int $centerId,
        int $actorId,
        int $productId,
        string $typeName,
        float $packageQuantity,
        ?string $reason = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): StockMovement {
        $product = Product::query()
            ->forCenter($centerId)
            ->whereKey($productId)
            ->firstOrFail();

        $doses = $packageQuantity * (int) $product->doses_per_package;

        return $this->register(
            centerId: $centerId,
            actorId: $actorId,
            productId: $productId,
            typeName: $typeName,
            quantity: $doses,
            reason: $reason,
            referenceType: $referenceType,
            referenceId: $referenceId,
        );
    }

    public function register(
        int $centerId,
        int $actorId,
        int $productId,
        string $typeName,
        float $quantity,
        ?string $reason = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): StockMovement {
        return DB::transaction(function () use ($centerId, $actorId, $productId, $typeName, $quantity, $reason, $referenceType, $referenceId) {
            $type = StockMovementType::query()
                ->where('name', $typeName)
                ->firstOrFail();

            $stock = ProductStock::query()
                ->forCenter($centerId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->firstOrFail();

            $previous = (float) $stock->current_quantity;
            $new = $previous + $quantity;

            if ($new < 0) {
                throw ValidationException::withMessages([
                    'quantity' => ['No hay suficiente stock para registrar este movimiento.'],
                ]);
            }

            $movement = StockMovement::create([
                'center_id' => $centerId,
                'product_id' => $productId,
                'movement_type_id' => $type->id,
                'quantity' => $quantity,
                'previous_quantity' => $previous,
                'new_quantity' => $new,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'user_id' => $actorId,
                'reason' => $reason,
            ]);

            $stock->current_quantity = $new;
            $stock->save();

            return $movement->load(['product', 'type', 'user']);
        });
    }
}
