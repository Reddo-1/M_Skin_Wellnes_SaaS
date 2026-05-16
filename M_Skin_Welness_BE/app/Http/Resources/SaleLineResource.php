<?php

namespace App\Http\Resources;

use App\Models\SaleLine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SaleLine */
class SaleLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sale_id' => $this->sale_id,
            'type' => $this->type,
            'reference_id' => $this->reference_id,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'line_discount' => $this->line_discount,
            'line_total' => $this->line_total,
        ];
    }
}
