<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateTreatmentConsentRequest;
use App\Http\Resources\TreatmentConsentResource;
use App\Models\TreatmentConsent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TreatmentConsentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', TreatmentConsent::class);

        $centerId = (int) $request->attributes->get('center_id');
        $actor = $request->user();
        //un usuario que solo es cliente ve unicamente sus consents, ignorando cualquier filtro que pida
        $restrictToSelf = $actor->hasRole('cliente') && $actor->roles->count() === 1;

        $query = TreatmentConsent::query()
            ->forCenter($centerId)
            ->with(['client', 'treatment', 'reviewer'])
            ->when(
                $restrictToSelf,
                fn ($q) => $q->where('user_id', $actor->id),
                fn ($q) => $q->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id'))),
            )
            ->when($request->filled('treatment_id'), fn ($q) => $q->where('treatment_id', $request->integer('treatment_id')))
            ->when($request->filled('reviewer_id'), fn ($q) => $q->where('reviewed_by_user_id', $request->integer('reviewer_id')))
            ->when($request->boolean('only_active'), fn ($q) => $q->active())
            ->when($request->filled('from'), fn ($q) => $q->where('review_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->where('review_date', '<=', $request->date('to')))
            ->orderByDesc('review_date')
            ->orderByDesc('id');

        return TreatmentConsentResource::collection($query->paginate(10));
    }

    //el diagnosticador fija la aptitud desde la ficha; NO toca treatment_consent (el acto firmado por el cliente se respeta)
    public function update(UpdateTreatmentConsentRequest $request, TreatmentConsent $treatmentConsent): TreatmentConsentResource
    {
        $data = $request->validated();
        $isSuitable = (bool) $data['is_suitable'];

        $treatmentConsent->update([
            'is_suitable' => $isSuitable,
            'unsuitability_reason' => $isSuitable ? null : ($data['unsuitability_reason'] ?? null),
            'notes' => $data['notes'] ?? null,
            'reviewed_by_user_id' => $request->user()->id,
            'review_date' => now()->toDateString(),
        ]);

        return TreatmentConsentResource::make(
            $treatmentConsent->load(['client', 'treatment', 'reviewer'])
        );
    }
}
