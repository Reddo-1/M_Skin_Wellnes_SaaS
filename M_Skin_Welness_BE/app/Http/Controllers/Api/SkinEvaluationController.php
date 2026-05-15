<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSkinEvaluationRequest;
use App\Http\Requests\UpdateSkinEvaluationRequest;
use App\Http\Resources\SkinEvaluationResource;
use App\Models\SkinEvaluation;
use App\Services\SkinEvaluationService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SkinEvaluationController extends Controller
{
    //inyecta el service de evaluaciones
    public function __construct(private readonly SkinEvaluationService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', SkinEvaluation::class);

        $centerId = (int) $request->attributes->get('center_id');

        $query = SkinEvaluation::query()
            //solo evaluaciones del centro actual
            ->forCenter($centerId)
            //carga relaciones para no hacer N+1
            ->with(['client', 'clientProfile', 'skinType', 'professional', 'variations'])
            //filtros opcionales
            ->when($request->filled('client_profile_id'), fn ($q) => $q->where('client_profile_id', $request->integer('client_profile_id')))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
            ->when($request->filled('professional_id'), fn ($q) => $q->where('professional_id', $request->integer('professional_id')))
            ->when($request->filled('from'), fn ($q) => $q->where('evaluation_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->where('evaluation_date', '<=', $request->date('to')))
            //historico ordenado por fecha de evaluacion descendente (mas reciente primero)
            ->orderByDesc('evaluation_date')
            ->orderByDesc('id');

        return SkinEvaluationResource::collection($query->paginate(50));
    }

    public function store(StoreSkinEvaluationRequest $request): SkinEvaluationResource
    {
        $evaluation = $this->service->create(
            centerId: (int) $request->attributes->get('center_id'),
            actorId: $request->user()->id,
            data: $request->validated(),
        );

        return SkinEvaluationResource::make($evaluation);
    }

    public function show(SkinEvaluation $skinEvaluation): SkinEvaluationResource
    {
        $this->authorize('view', $skinEvaluation);

        return SkinEvaluationResource::make(
            $skinEvaluation->load(['client', 'clientProfile', 'skinType', 'professional', 'variations'])
        );
    }

    public function update(UpdateSkinEvaluationRequest $request, SkinEvaluation $skinEvaluation): SkinEvaluationResource
    {
        $evaluation = $this->service->update($skinEvaluation, $request->validated());

        return SkinEvaluationResource::make($evaluation);
    }
}
