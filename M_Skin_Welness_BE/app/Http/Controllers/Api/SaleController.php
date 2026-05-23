<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\{ChangeSaleStatusRequest, StoreSaleRequest};
use App\Http\Resources\SaleResource;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SaleController extends Controller
{
    public function __construct(private readonly SaleService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Sale::class);

        $centerId = (int) $request->attributes->get('center_id');

        $query = Sale::query()
            ->forCenter($centerId)
            ->with(['client', 'status', 'paymentMethod', 'creator'])
            ->when($request->filled('client_id'), fn ($q) => $q->where('client_id', $request->integer('client_id')))
            ->when($request->filled('appointment_id'), fn ($q) => $q->where('appointment_id', $request->integer('appointment_id')))
            ->when($request->filled('status_id'), fn ($q) => $q->where('status_id', $request->integer('status_id')))
            ->when($request->filled('from'), fn ($q) => $q->where('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->where('created_at', '<=', $request->date('to')))
            ->orderByDesc('id');

        return SaleResource::collection($query->paginate(50));
    }

    public function store(StoreSaleRequest $request): SaleResource
    {
        $sale = $this->service->create(
            centerId: (int) $request->attributes->get('center_id'),
            actorId: $request->user()->id,
            data: $request->validated(),
        );

        return SaleResource::make($sale);
    }

    public function show(Sale $sale): SaleResource
    {
        $this->authorize('view', $sale);

        return SaleResource::make(
            $sale->load(['client', 'status', 'paymentMethod', 'creator', 'lines'])
        );
    }

    public function changeStatus(ChangeSaleStatusRequest $request, Sale $sale): SaleResource
    {
        $data = $request->validated();

        $sale = $this->service->changeStatus(
            $sale,
            (int) $data['status_id'],
            isset($data['payment_method_id']) ? (int) $data['payment_method_id'] : null,
            $request->user()->id,
        );

        return SaleResource::make($sale);
    }
}
