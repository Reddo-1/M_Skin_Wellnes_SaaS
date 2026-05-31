<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\{StoreMachineRequest, UpdateMachineRequest};
use App\Http\Resources\MachineResource;
use App\Models\Machine;
use App\Services\MachineService;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MachineController extends Controller
{
    //inyecta el service de máquinas
    public function __construct(private readonly MachineService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Machine::class);

        $centerId = (int) $request->attributes->get('center_id');

        $query = Machine::query()
            //solo del centro actual
            ->forCenter($centerId)
            //carga sala fija y tratamientos compatibles
            ->with(['fixedRoom', 'treatments'])
            //filtros opcionales
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->filled('is_mobile'), fn ($q) => $q->where('is_mobile', $request->boolean('is_mobile')))
            ->orderBy('name');

        return MachineResource::collection($query->paginate($request->integer('per_page', 10)));
    }

    public function store(StoreMachineRequest $request): MachineResource
    {
        $machine = $this->service->create(
            centerId: (int) $request->attributes->get('center_id'),
            data: $request->validated(),
        );

        return MachineResource::make($machine->load(['fixedRoom', 'treatments']));
    }

    public function show(Machine $machine): MachineResource
    {
        $this->authorize('view', $machine);

        return MachineResource::make($machine->load(['fixedRoom', 'treatments']));
    }

    public function update(UpdateMachineRequest $request, Machine $machine): MachineResource
    {
        $machine = $this->service->update($machine, $request->validated());

        return MachineResource::make($machine->load(['fixedRoom', 'treatments']));
    }

    public function destroy(Machine $machine): JsonResponse
    {
        $this->authorize('delete', $machine);

        //borrar es libre: citas con esta máquina quedan con machine_id null
        $this->service->delete($machine);

        return response()->json(status: 204);
    }
}
