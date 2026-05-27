<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
        //un usuario que solo tiene el rol cliente solo ve sus propios consents, ignore el filtro que pida
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

        return TreatmentConsentResource::collection($query->paginate(50));
    }
}
