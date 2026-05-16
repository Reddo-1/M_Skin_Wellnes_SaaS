<?php

namespace App\Services;

use App\Models\Center;
use App\Models\Invoice;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    private const VAT_PERCENTAGE = 21.00;

    public function issueForSale(Sale $sale, int $issuerId): Invoice
    {
        return DB::transaction(function () use ($sale, $issuerId) {
            $existing = Invoice::query()->where('sale_id', $sale->id)->first();
            if ($existing !== null) {
                return $existing;
            }

            $client = User::query()->whereKey($sale->client_id)->first();
            $center = Center::query()->whereKey($sale->center_id)->first();

            $total = (float) $sale->total;
            $vatAmount = round($total * self::VAT_PERCENTAGE / (100 + self::VAT_PERCENTAGE), 2);
            $subtotal = round($total - $vatAmount, 2);

            return Invoice::create([
                'center_id' => $sale->center_id,
                'sale_id' => $sale->id,
                'client_id' => $sale->client_id,
                'invoice_number' => $this->nextInvoiceNumber($sale->center_id),
                'issued_date' => now()->toDateString(),
                'subtotal' => $subtotal,
                'vat_percentage' => self::VAT_PERCENTAGE,
                'vat_amount' => $vatAmount,
                'total' => $total,
                'client_snapshot' => [
                    'name' => $client?->name,
                    'email' => $client?->email,
                ],
                'center_snapshot' => [
                    'name' => $center?->name,
                    'slug' => $center?->slug,
                ],
                'pdf_path' => null,
                'issued_by_user_id' => $issuerId,
            ]);
        });
    }

    //numero correlativo por centro y año: FAC-{año}-{NNNN}
    private function nextInvoiceNumber(int $centerId): string
    {
        $year = (int) now()->format('Y');

        $count = Invoice::query()
            ->forCenter($centerId)
            ->whereYear('issued_date', $year)
            ->lockForUpdate()
            ->count();

        return sprintf('FAC-%d-%04d', $year, $count + 1);
    }
}
