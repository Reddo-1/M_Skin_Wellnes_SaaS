<?php

namespace App\Http\Resources;

use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Sale */
class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'center_id' => $this->center_id,
            'client_id' => $this->client_id,
            'appointment_id' => $this->appointment_id,
            'created_by_user_id' => $this->created_by_user_id,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'total' => $this->total,
            'status_id' => $this->status_id,
            'payment_method_id' => $this->payment_method_id,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'notes' => $this->notes,
            'client' => $this->whenLoaded('client', function () {
                return [
                    'id' => $this->client->id,
                    'name' => $this->client->name,
                    'email' => $this->client->email,
                ];
            }),
            'status' => $this->whenLoaded('status', function () {
                return ['id' => $this->status->id, 'name' => $this->status->name];
            }),
            'payment_method' => $this->whenLoaded('paymentMethod', function () {
                return ['id' => $this->paymentMethod->id, 'name' => $this->paymentMethod->name];
            }),
            'creator' => $this->whenLoaded('creator', function () {
                return ['id' => $this->creator->id, 'name' => $this->creator->name];
            }),
            'lines' => SaleLineResource::collection($this->whenLoaded('lines')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
