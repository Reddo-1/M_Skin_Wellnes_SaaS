<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdjustProductStockRequest;
use App\Http\Resources\ProductStockResource;
use App\Models\ProductStock;
use App\Services\StockMovementService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductStockController extends Controller
{
    public function __construct(private readonly StockMovementService $movements)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ProductStock::class);

        $centerId = (int) $request->attributes->get('center_id');

        $query = ProductStock::query()
            ->forCenter($centerId)
            ->with('product')
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->integer('product_id')))
            ->when($request->boolean('below_minimum'), fn ($q) => $q->belowMinimum())
            ->orderBy('product_id');

        return ProductStockResource::collection($query->paginate(50));
    }

    public function show(ProductStock $productStock): ProductStockResource
    {
        $this->authorize('view', $productStock);

        return ProductStockResource::make($productStock->load('product'));
    }

    public function adjust(AdjustProductStockRequest $request, ProductStock $productStock): ProductStockResource
    {
        $data = $request->validated();

        $delta = (float) $data['new_quantity'] - (float) $productStock->current_quantity;

        $this->movements->register(
            centerId: $productStock->center_id,
            actorId: $request->user()->id,
            productId: $productStock->product_id,
            typeId: (int) config('lookups.stock_movement_types.ajuste_manual'),
            quantity: $delta,
            reason: $data['reason'],
        );

        return ProductStockResource::make($productStock->refresh()->load('product'));
    }
}
