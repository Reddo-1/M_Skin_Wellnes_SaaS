<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStockMovementRequest;
use App\Http\Resources\StockMovementResource;
use App\Models\StockMovement;
use App\Services\StockMovementService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StockMovementController extends Controller
{
    public function __construct(private readonly StockMovementService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', StockMovement::class);

        $centerId = (int) $request->attributes->get('center_id');

        $query = StockMovement::query()
            ->forCenter($centerId)
            ->with(['product', 'type', 'user'])
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->integer('product_id')))
            ->when($request->filled('movement_type_id'), fn ($q) => $q->where('movement_type_id', $request->integer('movement_type_id')))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
            ->when($request->filled('from'), fn ($q) => $q->where('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->where('created_at', '<=', $request->date('to')))
            ->orderByDesc('id');

        return StockMovementResource::collection($query->paginate(50));
    }

    public function store(StoreStockMovementRequest $request): StockMovementResource
    {
        $data = $request->validated();

        $movement = $this->service->registerByPackages(
            centerId: (int) $request->attributes->get('center_id'),
            actorId: $request->user()->id,
            productId: (int) $data['product_id'],
            typeName: $data['type'],
            packageQuantity: (float) $data['package_quantity'],
            reason: $data['reason'] ?? null,
        );

        return StockMovementResource::make($movement);
    }

    public function show(StockMovement $stockMovement): StockMovementResource
    {
        $this->authorize('view', $stockMovement);

        return StockMovementResource::make($stockMovement->load(['product', 'type', 'user']));
    }
}
