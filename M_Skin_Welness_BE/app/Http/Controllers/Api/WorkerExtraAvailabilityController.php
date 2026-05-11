<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkerExtraAvailabilityRequest;
use App\Http\Requests\UpdateWorkerExtraAvailabilityRequest;
use App\Http\Resources\WorkerExtraAvailabilityResource;
use App\Models\WorkerExtraAvailability;
use App\Services\WorkerExtraAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WorkerExtraAvailabilityController extends Controller
{
    //inyecta el service de disponibilidad extra
    public function __construct(private readonly WorkerExtraAvailabilityService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', WorkerExtraAvailability::class);

        $centerId = (int) $request->attributes->get('center_id');

        $query = WorkerExtraAvailability::query()
            //solo del centro actual
            ->forCenter($centerId)
            //carga el trabajador
            ->with(['worker'])
            //filtros opcionales
            ->when($request->filled('worker_id'), fn ($q) => $q->where('worker_id', $request->integer('worker_id')))
            ->when($request->filled('from'), fn ($q) => $q->where('date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->where('date', '<=', $request->date('to')))
            ->orderBy('date');

        return WorkerExtraAvailabilityResource::collection($query->paginate(100));
    }

    public function store(StoreWorkerExtraAvailabilityRequest $request): WorkerExtraAvailabilityResource
    {
        $extra = $this->service->create(
            centerId: (int) $request->attributes->get('center_id'),
            data: $request->validated(),
        );

        return WorkerExtraAvailabilityResource::make($extra->load(['worker']));
    }

    public function show(WorkerExtraAvailability $workerExtraAvailability): WorkerExtraAvailabilityResource
    {
        $this->authorize('view', $workerExtraAvailability);

        return WorkerExtraAvailabilityResource::make($workerExtraAvailability->load(['worker']));
    }

    public function update(UpdateWorkerExtraAvailabilityRequest $request, WorkerExtraAvailability $workerExtraAvailability): WorkerExtraAvailabilityResource
    {
        $workerExtraAvailability = $this->service->update($workerExtraAvailability, $request->validated());

        return WorkerExtraAvailabilityResource::make($workerExtraAvailability->load(['worker']));
    }

    public function destroy(WorkerExtraAvailability $workerExtraAvailability): JsonResponse
    {
        $this->authorize('delete', $workerExtraAvailability);

        $this->service->delete($workerExtraAvailability);

        return response()->json(status: 204);
    }
}
