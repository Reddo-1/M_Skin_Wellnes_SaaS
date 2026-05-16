<?php

namespace Database\Seeders;

use App\Models\Center;
use App\Models\Sale;
use App\Models\Treatment;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SaleSeeder extends Seeder
{
    public function run(): void
    {
        $centerId = Center::query()->where('slug', 'demo')->value('id');

        if ($centerId === null) {
            return;
        }

        $reception = User::query()->where('email', 'recepcion@demo.test')->value('id');

        $clients = User::query()
            ->where('center_id', $centerId)
            ->whereIn('email', ['cliente1@demo.test', 'cliente2@demo.test', 'cliente3@demo.test'])
            ->pluck('id', 'email')
            ->all();

        $treatments = Treatment::query()->where('center_id', $centerId)->pluck('id', 'name')->all();

        $cremaProductId = DB::table('products')->where('center_id', $centerId)->where('name', 'Crema hidratante facial')->value('id');
        $serumProductId = DB::table('products')->where('center_id', $centerId)->where('name', 'Sérum vitamina C')->value('id');

        $saleStatusPaid = DB::table('sale_statuses')->where('name', 'pagada')->value('id');
        $paymentMethodCard = DB::table('payment_methods')->where('name', 'tarjeta')->value('id');

        if ($reception === null || $saleStatusPaid === null || $paymentMethodCard === null) {
            return;
        }

        $sales = [
            [
                'client_email' => 'cliente1@demo.test',
                'lines' => [
                    ['type' => 'treatment', 'reference_id' => $treatments['Limpieza facial profunda'] ?? null, 'description' => 'Limpieza facial profunda', 'unit_price' => 45.00, 'quantity' => 1],
                    ['type' => 'product',   'reference_id' => $cremaProductId,                                 'description' => 'Crema hidratante facial', 'unit_price' => 29.90, 'quantity' => 1],
                ],
            ],
            [
                'client_email' => 'cliente2@demo.test',
                'lines' => [
                    ['type' => 'treatment', 'reference_id' => $treatments['Tratamiento antiedad'] ?? null, 'description' => 'Tratamiento antiedad', 'unit_price' => 65.00, 'quantity' => 1],
                ],
            ],
            [
                'client_email' => 'cliente3@demo.test',
                'lines' => [
                    ['type' => 'treatment', 'reference_id' => $treatments['Manicura completa'] ?? null, 'description' => 'Manicura completa', 'unit_price' => 25.00, 'quantity' => 1],
                    ['type' => 'product',   'reference_id' => $serumProductId,                          'description' => 'Sérum vitamina C',  'unit_price' => 39.50, 'quantity' => 1],
                ],
            ],
        ];

        foreach ($sales as $sale) {
            $clientId = $clients[$sale['client_email']] ?? null;
            if ($clientId === null) {
                continue;
            }

            $subtotal = 0;
            foreach ($sale['lines'] as $line) {
                $subtotal += $line['unit_price'] * $line['quantity'];
            }
            $total = $subtotal;

            $existingSale = DB::table('sales')
                ->where('center_id', $centerId)
                ->where('client_id', $clientId)
                ->where('subtotal', $subtotal)
                ->first();

            if ($existingSale !== null) {
                continue;
            }

            $saleId = DB::table('sales')->insertGetId([
                'center_id' => $centerId,
                'client_id' => $clientId,
                'appointment_id' => null,
                'created_by_user_id' => $reception,
                'subtotal' => $subtotal,
                'discount' => 0,
                'total' => $total,
                'status_id' => $saleStatusPaid,
                'payment_method_id' => $paymentMethodCard,
                'paid_at' => now(),
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($sale['lines'] as $line) {
                if ($line['reference_id'] === null) {
                    continue;
                }

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

            //emite la factura asociada (las ventas seed nacen pagadas)
            $saleModel = Sale::query()->whereKey($saleId)->first();
            if ($saleModel !== null) {
                app(InvoiceService::class)->issueForSale($saleModel, $reception);
            }
        }
    }
}
