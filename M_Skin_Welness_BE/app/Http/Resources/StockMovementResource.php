<?php

namespace App\Http\Resources;

use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StockMovement */
class StockMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'center_id' => $this->center_id,
            'product_id' => $this->product_id,
            'movement_type_id' => $this->movement_type_id,
            'quantity' => $this->quantity,
            'previous_quantity' => $this->previous_quantity,
            'new_quantity' => $this->new_quantity,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'user_id' => $this->user_id,
            'reason' => $this->reason,
            'product' => $this->whenLoaded('product', function () {
                return [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                ];
            }),
            'type' => $this->whenLoaded('type', function () {
                return ['id' => $this->type->id, 'name' => $this->type->name];
            }),
            'user' => $this->whenLoaded('user', function () {
                return ['id' => $this->user->id, 'name' => $this->user->name];
            }),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
