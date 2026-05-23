<?php

namespace App\Services;

use App\Models\{Appointment, AppointmentProduct};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentProductService
{
    public function __construct(private readonly StockMovementService $stockMovements)
    {
    }

    public function attach(Appointment $appointment, int $productId, float $quantity): AppointmentProduct
    {
        return DB::transaction(function () use ($appointment, $productId, $quantity) {
            $existing = AppointmentProduct::query()
                ->where('appointment_id', $appointment->id)
                ->where('product_id', $productId)
                ->exists();

            if ($existing) {
                throw ValidationException::withMessages([
                    'product_id' => ['Este producto ya está asociado a la cita. Edita la línea o bórrala y vuelve a añadirla.'],
                ]);
            }

            return AppointmentProduct::create([
                'center_id' => $appointment->center_id,
                'appointment_id' => $appointment->id,
                'product_id' => $productId,
                'quantity' => $quantity,
            ])->load('product');
        });
    }

    public function detach(AppointmentProduct $line): void
    {
        DB::transaction(function () use ($line) {
            $line->delete();
        });
    }

    //consume el stock real al pasar la cita a 'realizada'. Idempotente: si ya se descontó,
    //la función no se debe volver a llamar; el AppointmentService garantiza un solo paso a 'realizada'.
    public function applyStockConsumption(Appointment $appointment, int $actorId): void
    {
        $lines = AppointmentProduct::query()
            ->where('appointment_id', $appointment->id)
            ->get();

        foreach ($lines as $line) {
            $this->stockMovements->register(
                centerId: $appointment->center_id,
                actorId: $actorId,
                productId: $line->product_id,
                typeId: (int) config('lookups.stock_movement_types.uso_sesion'),
                quantity: -1 * (float) $line->quantity,
                reason: null,
                referenceType: 'appointment',
                referenceId: $appointment->id,
            );
        }
    }
}
