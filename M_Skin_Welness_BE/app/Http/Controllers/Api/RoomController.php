<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\{StoreRoomRequest, UpdateRoomRequest};
use App\Http\Resources\RoomResource;
use App\Models\Room;
use App\Services\RoomService;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RoomController extends Controller
{
    //inyecta el service de salas
    public function __construct(private readonly RoomService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Room::class);

        $centerId = (int) $request->attributes->get('center_id');

        $query = Room::query()
            //solo del centro actual
            ->forCenter($centerId)
            //carga máquinas fijas de cada sala
            ->with(['machines'])
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('name');

        return RoomResource::collection($query->paginate(50));
    }

    public function store(StoreRoomRequest $request): RoomResource
    {
        $room = $this->service->create(
            centerId: (int) $request->attributes->get('center_id'),
            data: $request->validated(),
        );

        return RoomResource::make($room);
    }

    public function show(Room $room): RoomResource
    {
        $this->authorize('view', $room);

        return RoomResource::make($room->load(['machines']));
    }

    public function update(UpdateRoomRequest $request, Room $room): RoomResource
    {
        $room = $this->service->update($room, $request->validated());

        return RoomResource::make($room);
    }

    public function destroy(Room $room): JsonResponse
    {
        $this->authorize('delete', $room);

        //el service lanza 422 si la sala tiene citas asociadas
        $this->service->delete($room);

        return response()->json(status: 204);
    }
}
