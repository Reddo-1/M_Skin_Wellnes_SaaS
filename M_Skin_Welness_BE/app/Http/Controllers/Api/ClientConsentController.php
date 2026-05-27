<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClientConsentResource;
use App\Models\ClientConsent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClientConsentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ClientConsent::class);

        $centerId = (int) $request->attributes->get('center_id');
        $actor = $request->user();
        //un usuario que solo tiene el rol cliente solo ve sus propios consents, ignore el filtro que pida
        $restrictToSelf = $actor->hasRole('cliente') && $actor->roles->count() === 1;

        $query = ClientConsent::query()
            ->forCenter($centerId)
            ->with(['client', 'reviewer'])
            ->when(
                $restrictToSelf,
                fn ($q) => $q->where('user_id', $actor->id),
                fn ($q) => $q->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id'))),
            )
            ->when($request->boolean('only_active'), fn ($q) => $q->active())
            ->orderByDesc('signed_at')
            ->orderByDesc('id');

        return ClientConsentResource::collection($query->paginate(50));
    }
}
