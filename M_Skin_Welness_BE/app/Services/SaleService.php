<?php

namespace App\Services;

use App\Models\{Product, Sale};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function __construct(
        private readonly StockMovementService $stockMovements,
        private readonly InvoiceService $invoices,
    ) {
    }

    public function create(int $centerId, int $actorId, array $data): Sale
    {
        return DB::transaction(function () use ($centerId, $actorId, $data) {
            $this->validateLineReferences($centerId, $data['lines']);

            $subtotal = 0.0;
            $linesPayload = [];

            foreach ($data['lines'] as $line) {
                $quantity = (float) $line['quantity'];
                $unitPrice = (float) $line['unit_price'];
                $lineDiscount = (float) ($line['line_discount'] ?? 0);

                $lineTotal = round($quantity * $unitPrice - $lineDiscount, 2);

                if ($lineTotal < 0) {
                    throw ValidationException::withMessages([
                        'lines' => ['El descuento de una línea no puede superar su importe.'],
                    ]);
                }

                $subtotal += $lineTotal;

                $linesPayload[] = [
                    'center_id' => $centerId,
                    'type' => $line['type'],
                    'reference_id' => (int) $line['reference_id'],
                    'description' => $line['description'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_discount' => $lineDiscount,
                    'line_total' => $lineTotal,
                ];
            }

            $discount = (float) ($data['discount'] ?? 0);
            $total = round($subtotal - $discount, 2);

            if ($total < 0) {
                throw ValidationException::withMessages([
                    'discount' => ['El descuento global no puede superar el subtotal.'],
                ]);
            }

            $statusId = (int) $data['status_id'];
            $paidStatusId = (int) config('lookups.sale_statuses.pagada');
            $paymentMethodId = $data['payment_method_id'] ?? null;
            $isPaid = $statusId === $paidStatusId;

            if ($isPaid && $paymentMethodId === null) {
                throw ValidationException::withMessages([
                    'payment_method_id' => ['Indica el método de cobro para marcar la venta como pagada.'],
                ]);
            }

            $sale = Sale::create([
                'center_id' => $centerId,
                'client_id' => (int) $data['client_id'],
                'appointment_id' => $data['appointment_id'] ?? null,
                'created_by_user_id' => $actorId,
                'subtotal' => round($subtotal, 2),
                'discount' => $discount,
                'total' => $total,
                'status_id' => $statusId,
                'payment_method_id' => $paymentMethodId,
                'paid_at' => $isPaid ? now() : null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($linesPayload as $payload) {
                $sale->lines()->create($payload);
            }

            //el stock se lleva en dosis: registerByPackages convierte la cantidad vendida de paquetes a dosis
            foreach ($linesPayload as $payload) {
                if ($payload['type'] !== 'product') {
                    continue;
                }

                $this->stockMovements->registerByPackages(
                    centerId: $centerId,
                    actorId: $actorId,
                    productId: $payload['reference_id'],
                    typeId: (int) config('lookups.stock_movement_types.salida_venta'),
                    packageQuantity: -1 * (float) $payload['quantity'],
                    reason: null,
                    referenceType: 'sale',
                    referenceId: $sale->id,
                );
            }

            //si nace ya pagada, emite la factura en el mismo paso
            if ($isPaid) {
                $this->invoices->issueForSale($sale, $actorId);
            }

            return $sale->load(['client', 'status', 'paymentMethod', 'creator', 'lines']);
        });
    }

    public function changeStatus(Sale $sale, int $statusId, ?int $paymentMethodId, int $actorId): Sale
    {
        return DB::transaction(function () use ($sale, $statusId, $paymentMethodId, $actorId) {
            $paidStatusId = (int) config('lookups.sale_statuses.pagada');

            $sale->status_id = $statusId;
            $issueInvoice = false;

            if ($statusId === $paidStatusId && $sale->paid_at === null) {
                if ($paymentMethodId === null) {
                    throw ValidationException::withMessages([
                        'payment_method_id' => ['Indica el método de cobro para marcar la venta como pagada.'],
                    ]);
                }

                $sale->payment_method_id = $paymentMethodId;
                $sale->paid_at = now();
                $issueInvoice = true;
            }

            $sale->save();

            //emitir factura solo en la primera transición a pagada
            if ($issueInvoice) {
                $this->invoices->issueForSale($sale, $actorId);
            }

            return $sale->load(['client', 'status', 'paymentMethod', 'creator', 'lines']);
        });
    }

    private function validateLineReferences(int $centerId, array $lines): void
    {
        $treatmentIds = collect($lines)->where('type', 'treatment')->pluck('reference_id')->unique()->all();
        $productIds = collect($lines)->where('type', 'product')->pluck('reference_id')->unique()->all();

        if (! empty($treatmentIds)) {
            $existing = DB::table('treatments')
                ->where('center_id', $centerId)
                ->whereIn('id', $treatmentIds)
                ->count();

            if ($existing !== count($treatmentIds)) {
                throw ValidationException::withMessages([
                    'lines' => ['Una de las líneas referencia un tratamiento que no existe en este centro.'],
                ]);
            }
        }

        if (! empty($productIds)) {
            $existing = Product::query()
                ->forCenter($centerId)
                ->whereIn('id', $productIds)
                ->count();

            if ($existing !== count($productIds)) {
                throw ValidationException::withMessages([
                    'lines' => ['Una de las líneas referencia un producto que no existe en este centro.'],
                ]);
            }
        }
    }
}
