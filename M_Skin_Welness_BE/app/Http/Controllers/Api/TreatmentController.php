<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\{StoreTreatmentRequest, UpdateTreatmentRequest};
use App\Http\Resources\TreatmentResource;
use App\Models\Treatment;
use App\Services\TreatmentService;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TreatmentController extends Controller
{
    public function __construct(private readonly TreatmentService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Treatment::class);

        $centerId = (int) $request->attributes->get('center_id');

        $query = Treatment::query()
            ->forCenter($centerId)
            ->with(['machines', 'authorizedRoles'])
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('name');

        return TreatmentResource::collection($query->paginate(50));
    }

    public function store(StoreTreatmentRequest $request): TreatmentResource
    {
        $treatment = $this->service->create(
            centerId: (int) $request->attributes->get('center_id'),
            data: $request->validated(),
        );

        return TreatmentResource::make($treatment);
    }

    public function show(Treatment $treatment): TreatmentResource
    {
        $this->authorize('view', $treatment);

        return TreatmentResource::make(
            $treatment->load(['machines', 'authorizedRoles'])
        );
    }

    public function update(UpdateTreatmentRequest $request, Treatment $treatment): TreatmentResource
    {
        $treatment = $this->service->update($treatment, $request->validated());

        return TreatmentResource::make($treatment);
    }

    public function destroy(Treatment $treatment): JsonResponse
    {
        $this->authorize('delete', $treatment);

        //el service lanza 422 si tiene citas asociadas
        $this->service->delete($treatment);

        return response()->json(status: 204);
    }
}
