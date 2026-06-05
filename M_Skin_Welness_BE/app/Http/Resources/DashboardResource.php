<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $appointmentsToday = $this->resource['appointments_today'];

        return [
            'appointments_today' => [
                'total' => $appointmentsToday['total'],
                'in_progress' => $appointmentsToday['in_progress'],
                'list' => $appointmentsToday['list']->map(function ($appointment) {
                    return [
                        'id' => $appointment->id,
                        'starts_at' => $appointment->starts_at?->toIso8601String(),
                        'ends_at' => $appointment->ends_at?->toIso8601String(),
                        'treatment_name' => $appointment->treatment?->name,
                        'room_name' => $appointment->room?->name,
                        'client_name' => $appointment->client?->name,
                        'worker_name' => $appointment->worker?->name,
                        'status_name' => $appointment->status?->name,
                    ];
                })->all(),
            ],
            'revenue' => [
                'today' => $this->resource['revenue']['today'],
                'this_month' => $this->resource['revenue']['this_month'],
                'last_month' => $this->resource['revenue']['last_month'],
            ],
            'low_stock' => $this->resource['low_stock']->map(function ($stock) {
                return [
                    'product_id' => $stock->product_id,
                    'product_name' => $stock->product?->name,
                    'current_quantity' => (float) $stock->current_quantity,
                    'minimum_stock' => (float) $stock->product?->minimum_stock,
                ];
            })->all(),
            'monthly_metrics' => [
                'new_clients' => $this->resource['monthly_metrics']['new_clients'],
                'completed_sessions' => $this->resource['monthly_metrics']['completed_sessions'],
            ],
        ];
    }
}
