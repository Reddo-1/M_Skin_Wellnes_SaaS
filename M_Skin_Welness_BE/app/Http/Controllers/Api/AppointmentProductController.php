<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAppointmentProductRequest;
use App\Http\Resources\AppointmentProductResource;
use App\Models\Appointment;
use App\Models\AppointmentProduct;
use App\Services\AppointmentProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AppointmentProductController extends Controller
{
    public function __construct(private readonly AppointmentProductService $service)
    {
    }

    public function index(Appointment $appointment): AnonymousResourceCollection
    {
        $this->authorize('view', $appointment);

        return AppointmentProductResource::collection(
            $appointment->products()->with('product')->get()
        );
    }

    public function store(StoreAppointmentProductRequest $request, Appointment $appointment): AppointmentProductResource
    {
        $data = $request->validated();

        $line = $this->service->attach(
            appointment: $appointment,
            productId: (int) $data['product_id'],
            quantity: (float) $data['quantity'],
        );

        return AppointmentProductResource::make($line);
    }

    public function destroy(Appointment $appointment, AppointmentProduct $product): JsonResponse
    {
        $this->authorize('attachProducts', $appointment);

        if ($product->appointment_id !== $appointment->id) {
            abort(404);
        }

        $this->service->detach($product);

        return response()->json(status: 204);
    }
}
