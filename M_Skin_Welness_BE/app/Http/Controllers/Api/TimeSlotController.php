<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTimeSlotRequest;
use App\Http\Requests\UpdateTimeSlotRequest;
use App\Http\Resources\TimeSlotResource;
use App\Models\TimeSlot;
use App\Services\TimeSlotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TimeSlotController extends Controller
{
    //inyecta el service de franjas horarias
    public function __construct(private readonly TimeSlotService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', TimeSlot::class);

        $centerId = (int) $request->attributes->get('center_id');

        $query = TimeSlot::query()
            //solo del centro actual
            ->forCenter($centerId)
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            //orden por hora de inicio
            ->orderBy('start_time');

        return TimeSlotResource::collection($query->paginate(50));
    }

    public function store(StoreTimeSlotRequest $request): TimeSlotResource
    {
        $timeSlot = $this->service->create(
            centerId: (int) $request->attributes->get('center_id'),
            data: $request->validated(),
        );

        return TimeSlotResource::make($timeSlot);
    }

    public function show(TimeSlot $timeSlot): TimeSlotResource
    {
        $this->authorize('view', $timeSlot);

        return TimeSlotResource::make($timeSlot);
    }

    public function update(UpdateTimeSlotRequest $request, TimeSlot $timeSlot): TimeSlotResource
    {
        $timeSlot = $this->service->update($timeSlot, $request->validated());

        return TimeSlotResource::make($timeSlot);
    }

    public function destroy(TimeSlot $timeSlot): JsonResponse
    {
        $this->authorize('delete', $timeSlot);

        //el service lanza 422 si la franja está asignada a algún horario
        $this->service->delete($timeSlot);

        return response()->json(status: 204);
    }
}
