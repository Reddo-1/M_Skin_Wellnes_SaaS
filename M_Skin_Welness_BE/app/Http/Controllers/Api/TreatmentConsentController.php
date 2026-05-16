<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTreatmentConsentRequest;
use App\Http\Requests\UpdateTreatmentConsentRequest;
use App\Http\Resources\TreatmentConsentResource;
use App\Models\TreatmentConsent;
use App\Services\TreatmentConsentService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TreatmentConsentController extends Controller
{
    public function __construct(private readonly TreatmentConsentService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', TreatmentConsent::class);

        $centerId = (int) $request->attributes->get('center_id');

        $query = TreatmentConsent::query()
            ->forCenter($centerId)
            ->with(['client', 'treatment', 'reviewer'])
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
            ->when($request->filled('treatment_id'), fn ($q) => $q->where('treatment_id', $request->integer('treatment_id')))
            ->when($request->filled('reviewer_id'), fn ($q) => $q->where('reviewed_by_user_id', $request->integer('reviewer_id')))
            ->when($request->boolean('only_active'), fn ($q) => $q->active())
            ->when($request->filled('from'), fn ($q) => $q->where('review_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->where('review_date', '<=', $request->date('to')))
            ->orderByDesc('review_date')
            ->orderByDesc('id');

        return TreatmentConsentResource::collection($query->paginate(50));
    }

    public function store(StoreTreatmentConsentRequest $request): TreatmentConsentResource
    {
        $consent = $this->service->create(
            centerId: (int) $request->attributes->get('center_id'),
            actorId: $request->user()->id,
            data: $request->validated(),
        );

        return TreatmentConsentResource::make($consent);
    }

    public function show(TreatmentConsent $treatmentConsent): TreatmentConsentResource
    {
        $this->authorize('view', $treatmentConsent);

        return TreatmentConsentResource::make(
            $treatmentConsent->load(['client', 'treatment', 'reviewer'])
        );
    }

    public function update(UpdateTreatmentConsentRequest $request, TreatmentConsent $treatmentConsent): TreatmentConsentResource
    {
        $consent = $this->service->update($treatmentConsent, $request->validated());

        return TreatmentConsentResource::make($consent);
    }
}
