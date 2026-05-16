<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'center_id' => $this->center_id,
            'name' => $this->name,
            'description' => $this->description,
            'sale_price' => $this->sale_price,
            'cost_price' => $this->cost_price,
            'doses_per_package' => $this->doses_per_package,
            'minimum_stock' => $this->minimum_stock,
            'is_sellable' => $this->is_sellable,
            'is_active' => $this->is_active,
            'stock' => $this->whenLoaded('stock', function () {
                return [
                    'id' => $this->stock->id,
                    'current_quantity' => $this->stock->current_quantity,
                ];
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
