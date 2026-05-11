<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkerScheduleRequest;
use App\Http\Requests\UpdateWorkerScheduleRequest;
use App\Http\Resources\WorkerScheduleResource;
use App\Models\WorkerSchedule;
use App\Services\WorkerScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WorkerScheduleController extends Controller
{
    //inyecta el service de horarios
    public function __construct(private readonly WorkerScheduleService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', WorkerSchedule::class);

        $centerId = (int) $request->attributes->get('center_id');

        $query = WorkerSchedule::query()
            //solo del centro actual
            ->forCenter($centerId)
            //carga trabajador y franja
            ->with(['worker', 'timeSlot'])
            //filtros opcionales
            ->when($request->filled('worker_id'), fn ($q) => $q->where('worker_id', $request->integer('worker_id')))
            ->when($request->filled('weekday'), fn ($q) => $q->where('weekday', $request->integer('weekday')))
            ->orderBy('worker_id')
            ->orderBy('weekday');

        //100 por página (los horarios son muchos)
        return WorkerScheduleResource::collection($query->paginate(100));
    }

    public function store(StoreWorkerScheduleRequest $request): WorkerScheduleResource
    {
        $schedule = $this->service->create(
            centerId: (int) $request->attributes->get('center_id'),
            data: $request->validated(),
        );

        return WorkerScheduleResource::make($schedule->load(['worker', 'timeSlot']));
    }

    public function show(WorkerSchedule $workerSchedule): WorkerScheduleResource
    {
        $this->authorize('view', $workerSchedule);

        return WorkerScheduleResource::make($workerSchedule->load(['worker', 'timeSlot']));
    }

    public function update(UpdateWorkerScheduleRequest $request, WorkerSchedule $workerSchedule): WorkerScheduleResource
    {
        $workerSchedule = $this->service->update($workerSchedule, $request->validated());

        return WorkerScheduleResource::make($workerSchedule->load(['worker', 'timeSlot']));
    }

    public function destroy(WorkerSchedule $workerSchedule): JsonResponse
    {
        $this->authorize('delete', $workerSchedule);

        $this->service->delete($workerSchedule);

        return response()->json(status: 204);
    }
}
