<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\{StoreClientProfileRequest, UpdateClientProfileRequest};
use App\Http\Resources\ClientProfileResource;
use App\Models\ClientProfile;
use App\Services\ClientProfileService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClientProfileController extends Controller
{
    public function __construct(private readonly ClientProfileService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ClientProfile::class);

        $centerId = (int) $request->attributes->get('center_id');

        $query = ClientProfile::query()
            ->forCenter($centerId)
            ->with(['client', 'currentEvaluation.skinType', 'currentEvaluation.variations', 'currentEvaluation.professional', 'currentEvaluation.files'])
            ->when($request->filled('body_type'), fn ($q) => $q->where('body_type', $request->string('body_type')))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->whereHas('client', function ($c) use ($term) {
                    $c->whereRaw('unaccent(name) ILIKE unaccent(?)', [$term])->orWhere('email', 'ilike', $term);
                });
            })
            ->orderBy('id');

        return ClientProfileResource::collection($query->paginate(10));
    }

    public function store(StoreClientProfileRequest $request): ClientProfileResource
    {
        $profile = $this->service->create(
            centerId: (int) $request->attributes->get('center_id'),
            actorId: $request->user()->id,
            data: $request->validated(),
        );

        return ClientProfileResource::make($profile);
    }

    public function show(ClientProfile $clientProfile): ClientProfileResource
    {
        $this->authorize('view', $clientProfile);

        return ClientProfileResource::make(
            $clientProfile->load(['client', 'currentEvaluation.skinType', 'currentEvaluation.variations', 'currentEvaluation.professional', 'currentEvaluation.files'])
        );
    }

    public function update(UpdateClientProfileRequest $request, ClientProfile $clientProfile): ClientProfileResource
    {
        $profile = $this->service->update($clientProfile, $request->validated());

        return ClientProfileResource::make($profile);
    }
}
