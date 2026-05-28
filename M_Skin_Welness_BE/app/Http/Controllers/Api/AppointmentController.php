<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\{ChangeAppointmentStatusRequest, StoreAppointmentRequest, UpdateAppointmentRequest};
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Services\AppointmentService;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AppointmentController extends Controller
{
    public function __construct(private readonly AppointmentService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Appointment::class);

        $user = $request->user();
        $centerId = (int) $request->attributes->get('center_id');

        $query = Appointment::query()
            ->forCenter($centerId)
            ->with(['status', 'treatment', 'room', 'machine', 'client', 'worker'])
            ->when($request->filled('from'), fn ($q) => $q->where('starts_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->where('starts_at', '<', $request->date('to')))
            ->when($request->filled('worker_id'), fn ($q) => $q->where('worker_id', $request->integer('worker_id')))
            ->when($request->filled('room_id'), fn ($q) => $q->where('room_id', $request->integer('room_id')))
            ->when($request->filled('client_id'), fn ($q) => $q->where('client_id', $request->integer('client_id')))
            ->orderBy('starts_at');

        //un cliente solo ve sus propias citas
        if ($user->hasRole('cliente')) {
            $query->where('client_id', $user->id);
        }

        return AppointmentResource::collection($query->paginate(10));
    }

    public function store(StoreAppointmentRequest $request): AppointmentResource
    {
        $data = $request->validated();

        //si la cita la crea un cliente desde su portal, el origen siempre es 'online'
        if ($request->user()->hasRole('cliente')) {
            $data['booking_source'] = 'online';
        }

        $appointment = $this->service->create(
            centerId: (int) $request->attributes->get('center_id'),
            data: $data,
        );

        return AppointmentResource::make($appointment);
    }

    public function show(Appointment $appointment): AppointmentResource
    {
        $this->authorize('view', $appointment);

        return AppointmentResource::make(
            $appointment->load(['status', 'treatment', 'room', 'machine', 'client', 'worker', 'assistants'])
        );
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment): AppointmentResource
    {
        $appointment = $this->service->update($appointment, $request->validated());

        return AppointmentResource::make($appointment);
    }

    public function destroy(Appointment $appointment): JsonResponse
    {
        $this->authorize('delete', $appointment);

        $appointment->delete();

        return response()->json(status: 204);
    }

    //endpoint dedicado: el service dispara side effects (stock, validaciones) en cada transición
    public function changeStatus(ChangeAppointmentStatusRequest $request, Appointment $appointment): AppointmentResource
    {
        return AppointmentResource::make(
            $this->service->changeStatus($appointment, (int) $request->validated('status_id'), $request->user()->id)
        );
    }
}
