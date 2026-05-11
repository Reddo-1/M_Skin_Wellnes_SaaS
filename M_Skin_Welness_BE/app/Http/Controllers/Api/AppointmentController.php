<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeAppointmentStatusRequest;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Services\AppointmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AppointmentController extends Controller
{
    //inyecta el service de citas
    public function __construct(private readonly AppointmentService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        //comprueba permiso de ver citas
        $this->authorize('viewAny', Appointment::class);

        $user = $request->user();
        $centerId = (int) $request->attributes->get('center_id');

        $query = Appointment::query()
            //solo citas del centro actual
            ->forCenter($centerId)
            //carga relaciones para no hacer N+1
            ->with(['status', 'treatment', 'room', 'machine', 'client', 'worker'])
            //filtros opcionales por query string
            ->when($request->filled('from'), fn ($q) => $q->where('starts_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->where('starts_at', '<', $request->date('to')))
            ->when($request->filled('worker_id'), fn ($q) => $q->where('worker_id', $request->integer('worker_id')))
            ->when($request->filled('room_id'), fn ($q) => $q->where('room_id', $request->integer('room_id')))
            ->orderBy('starts_at');

        //un cliente solo ve sus propias citas
        if ($user->hasRole('cliente')) {
            $query->where('client_id', $user->id);
        }

        //50 resultados por página
        return AppointmentResource::collection($query->paginate(50));
    }

    public function store(StoreAppointmentRequest $request): AppointmentResource
    {
        //el FormRequest ya autorizó y validó
        $appointment = $this->service->create(
            centerId: (int) $request->attributes->get('center_id'),
            data: $request->validated(),
        );

        return AppointmentResource::make($appointment);
    }

    public function show(Appointment $appointment): AppointmentResource
    {
        //comprueba permiso de ver esta cita en concreto
        $this->authorize('view', $appointment);

        //carga relaciones del detalle (incluye ayudantes)
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

        //204 = sin contenido
        return response()->json(status: 204);
    }

    //endpoint específico para cambiar estado (dispara side effects en el service)
    public function changeStatus(ChangeAppointmentStatusRequest $request, Appointment $appointment): AppointmentResource
    {
        return AppointmentResource::make(
            $this->service->changeStatus($appointment, $request->validated('status'))
        );
    }
}
