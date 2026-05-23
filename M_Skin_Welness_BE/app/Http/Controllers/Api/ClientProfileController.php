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
    //inyecta el service de fichas clinicas
    public function __construct(private readonly ClientProfileService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ClientProfile::class);

        $centerId = (int) $request->attributes->get('center_id');

        $query = ClientProfile::query()
            //solo fichas del centro actual
            ->forCenter($centerId)
            //carga relaciones para no hacer N+1 (incluida la evaluacion actual con sus relaciones)
            ->with(['client', 'currentEvaluation.skinType', 'currentEvaluation.variations', 'currentEvaluation.professional'])
            //filtros opcionales
            ->when($request->filled('body_type'), fn ($q) => $q->where('body_type', $request->string('body_type')))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
            //busqueda libre sobre el nombre o email del cliente asociado
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->whereHas('client', function ($c) use ($term) {
                    $c->where('name', 'ilike', $term)->orWhere('email', 'ilike', $term);
                });
            })
            ->orderBy('id');

        return ClientProfileResource::collection($query->paginate(50));
    }

    public function store(StoreClientProfileRequest $request): ClientProfileResource
    {
        $profile = $this->service->create(
            centerId: (int) $request->attributes->get('center_id'),
            data: $request->validated(),
        );

        return ClientProfileResource::make($profile);
    }

    public function show(ClientProfile $clientProfile): ClientProfileResource
    {
        $this->authorize('view', $clientProfile);

        return ClientProfileResource::make(
            $clientProfile->load(['client', 'currentEvaluation.skinType', 'currentEvaluation.variations', 'currentEvaluation.professional'])
        );
    }

    public function update(UpdateClientProfileRequest $request, ClientProfile $clientProfile): ClientProfileResource
    {
        $profile = $this->service->update($clientProfile, $request->validated());

        return ClientProfileResource::make($profile);
    }
}
