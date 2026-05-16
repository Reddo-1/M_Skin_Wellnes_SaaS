<?php

namespace App\Http\Resources;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Invoice */
class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'center_id' => $this->center_id,
            'sale_id' => $this->sale_id,
            'client_id' => $this->client_id,
            'invoice_number' => $this->invoice_number,
            'issued_date' => $this->issued_date?->toDateString(),
            'subtotal' => $this->subtotal,
            'vat_percentage' => $this->vat_percentage,
            'vat_amount' => $this->vat_amount,
            'total' => $this->total,
            'client_snapshot' => $this->client_snapshot,
            'center_snapshot' => $this->center_snapshot,
            'pdf_path' => $this->pdf_path,
            'issued_by_user_id' => $this->issued_by_user_id,
            'client' => $this->whenLoaded('client', function () {
                return [
                    'id' => $this->client->id,
                    'name' => $this->client->name,
                    'email' => $this->client->email,
                ];
            }),
            'issuer' => $this->whenLoaded('issuer', function () {
                return ['id' => $this->issuer->id, 'name' => $this->issuer->name];
            }),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
