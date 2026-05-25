<?php

namespace App\Services;

use App\Models\{Center, Invoice, Sale, User};
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

            $invoice = Invoice::create([
                'center_id' => $sale->center_id,
                'sale_id' => $sale->id,
                'client_id' => $sale->client_id,
                //placeholder unico por sale_id; se reemplaza por FAC-{año}-{id} justo despues, ya con el id real
                'invoice_number' => 'TMP-'.$sale->id,
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

            $invoice->update([
                'invoice_number' => sprintf('FAC-%d-%d', now()->year, $invoice->id),
            ]);

            return $invoice;
        });
    }
}
