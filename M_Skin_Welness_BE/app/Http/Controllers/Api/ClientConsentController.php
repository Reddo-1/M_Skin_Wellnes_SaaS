<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\{StoreClientConsentRequest, UpdateClientConsentRequest};
use App\Http\Resources\ClientConsentResource;
use App\Models\ClientConsent;
use App\Services\ClientConsentService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClientConsentController extends Controller
{
    public function __construct(private readonly ClientConsentService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ClientConsent::class);

        $centerId = (int) $request->attributes->get('center_id');

        $query = ClientConsent::query()
            ->forCenter($centerId)
            ->with(['client', 'reviewer'])
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
            ->when($request->boolean('only_active'), fn ($q) => $q->active())
            ->orderByDesc('signed_at')
            ->orderByDesc('id');

        return ClientConsentResource::collection($query->paginate(50));
    }

    public function store(StoreClientConsentRequest $request): ClientConsentResource
    {
        $consent = $this->service->create(
            centerId: (int) $request->attributes->get('center_id'),
            actorId: $request->user()->id,
            data: $request->validated(),
            signature: $request->file('signature_file'),
        );

        return ClientConsentResource::make($consent);
    }

    public function show(ClientConsent $clientConsent): ClientConsentResource
    {
        $this->authorize('view', $clientConsent);

        return ClientConsentResource::make(
            $clientConsent->load(['client', 'reviewer', 'signatureFile'])
        );
    }

    public function update(UpdateClientConsentRequest $request, ClientConsent $clientConsent): ClientConsentResource
    {
        $consent = $this->service->update($clientConsent, $request->validated());

        return ClientConsentResource::make($consent);
    }
}
