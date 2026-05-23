<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\{StoreProductRequest, UpdateProductRequest};
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Product::class);

        $centerId = (int) $request->attributes->get('center_id');

        $query = Product::query()
            ->forCenter($centerId)
            ->with('stock')
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->filled('is_sellable'), fn ($q) => $q->where('is_sellable', $request->boolean('is_sellable')))
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'ilike', '%'.$request->string('search').'%'))
            ->orderBy('name');

        return ProductResource::collection($query->paginate(50));
    }

    public function store(StoreProductRequest $request): ProductResource
    {
        $product = $this->service->create(
            centerId: (int) $request->attributes->get('center_id'),
            data: $request->validated(),
        );

        return ProductResource::make($product);
    }

    public function show(Product $product): ProductResource
    {
        $this->authorize('view', $product);

        return ProductResource::make($product->load('stock'));
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $product = $this->service->update($product, $request->validated());

        return ProductResource::make($product);
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->authorize('delete', $product);

        $this->service->delete($product);

        return response()->json(status: 204);
    }
}
