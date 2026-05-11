<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkerAbsenceRequest;
use App\Http\Requests\UpdateWorkerAbsenceRequest;
use App\Http\Resources\WorkerAbsenceResource;
use App\Models\WorkerAbsence;
use App\Services\WorkerAbsenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WorkerAbsenceController extends Controller
{
    //inyecta el service de ausencias
    public function __construct(private readonly WorkerAbsenceService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', WorkerAbsence::class);

        $centerId = (int) $request->attributes->get('center_id');

        $query = WorkerAbsence::query()
            //solo del centro actual
            ->forCenter($centerId)
            //carga trabajador y tipo de ausencia
            ->with(['worker', 'absenceType'])
            //filtros opcionales
            ->when($request->filled('worker_id'), fn ($q) => $q->where('worker_id', $request->integer('worker_id')))
            ->when($request->filled('from'), fn ($q) => $q->where('date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->where('date', '<=', $request->date('to')))
            //orden descendente: más recientes primero
            ->orderByDesc('date');

        return WorkerAbsenceResource::collection($query->paginate(100));
    }

    //crea una ausencia por cada día del rango from..to
    public function store(StoreWorkerAbsenceRequest $request): AnonymousResourceCollection
    {
        $absences = $this->service->create(
            centerId: (int) $request->attributes->get('center_id'),
            data: $request->validated(),
        );

        //carga relaciones en cada registro para el Resource
        $absences->each(function ($absence) {
            $absence->load(['worker', 'absenceType']);
        });

        return WorkerAbsenceResource::collection($absences);
    }

    public function show(WorkerAbsence $workerAbsence): WorkerAbsenceResource
    {
        $this->authorize('view', $workerAbsence);

        return WorkerAbsenceResource::make($workerAbsence->load(['worker', 'absenceType']));
    }

    public function update(UpdateWorkerAbsenceRequest $request, WorkerAbsence $workerAbsence): WorkerAbsenceResource
    {
        $workerAbsence = $this->service->update($workerAbsence, $request->validated());

        return WorkerAbsenceResource::make($workerAbsence->load(['worker', 'absenceType']));
    }

    public function destroy(WorkerAbsence $workerAbsence): JsonResponse
    {
        $this->authorize('delete', $workerAbsence);

        $this->service->delete($workerAbsence);

        return response()->json(status: 204);
    }
}
