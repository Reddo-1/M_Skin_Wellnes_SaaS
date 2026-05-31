<?php

namespace Database\Seeders;

use App\Models\{Appointment, Center, Sale, User};
use App\Services\InvoiceService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SaleSeeder extends Seeder
{
    public function run(): void
    {
        mt_srand(20260602);

        $centerId = Center::query()->where('slug', 'demo')->value('id');

        if ($centerId === null) {
            return;
        }

        $reception = User::role('recepcionista')->where('center_id', $centerId)->value('id');
        $saleStatusPaid = (int) config('lookups.sale_statuses.pagada');
        $paymentMethods = DB::table('payment_methods')->whereIn('name', ['tarjeta', 'efectivo'])->pluck('id')->all();
        $realizadaId = (int) config('lookups.session_statuses.realizada');

        if ($reception === null || $paymentMethods === [] || $saleStatusPaid === 0) {
            return;
        }

        $products = DB::table('products')
            ->where('center_id', $centerId)
            ->where('is_sellable', true)
            ->where('is_active', true)
            ->get()
            ->values();

        //ventas a partir de las citas realizadas con precio > 0 (se cobran al terminar la sesion)
        $appointments = Appointment::query()
            ->where('center_id', $centerId)
            ->where('status_id', $realizadaId)
            ->where('reserved_price', '>', 0)
            ->with('treatment')
            ->get();

        $invoices = app(InvoiceService::class);

        foreach ($appointments as $appt) {
            if (mt_rand(1, 100) > 55) {
                continue;
            }

            $lines = [[
                'type' => 'treatment',
                'reference_id' => $appt->treatment_id,
                'description' => $appt->treatment?->name ?? 'Tratamiento',
                'unit_price' => (float) $appt->reserved_price,
                'quantity' => 1,
            ]];

            //~40% de las ventas llevan ademas un producto
            if (mt_rand(1, 100) <= 40 && $products->isNotEmpty()) {
                $product = $products[mt_rand(0, $products->count() - 1)];
                $lines[] = [
                    'type' => 'product',
                    'reference_id' => $product->id,
                    'description' => $product->name,
                    'unit_price' => (float) $product->sale_price,
                    'quantity' => 1,
                ];
            }

            $subtotal = array_sum(array_map(fn ($l) => $l['unit_price'] * $l['quantity'], $lines));
            $paidAt = $appt->starts_at;

            $saleId = DB::table('sales')->insertGetId([
                'center_id' => $centerId,
                'client_id' => $appt->client_id,
                'appointment_id' => $appt->id,
                'created_by_user_id' => $reception,
                'subtotal' => $subtotal,
                'discount' => 0,
                'total' => $subtotal,
                'status_id' => $saleStatusPaid,
                'payment_method_id' => $paymentMethods[mt_rand(0, count($paymentMethods) - 1)],
                'paid_at' => $paidAt,
                'notes' => null,
                'created_at' => $paidAt,
                'updated_at' => $paidAt,
            ]);

            foreach ($lines as $line) {
                DB::table('sale_lines')->insert([
                    'sale_id' => $saleId,
                    'center_id' => $centerId,
                    'type' => $line['type'],
                    'reference_id' => $line['reference_id'],
                    'description' => $line['description'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'line_discount' => 0,
                    'line_total' => $line['unit_price'] * $line['quantity'],
                ]);
            }

            $saleModel = Sale::query()->whereKey($saleId)->first();
            if ($saleModel !== null) {
                $invoices->issueForSale($saleModel, $reception);
            }
        }
    }
}
