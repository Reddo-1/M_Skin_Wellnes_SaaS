<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreConsentWizardRequest;
use App\Http\Resources\ClientConsentResource;
use App\Services\ConsentWizardService;

class ConsentWizardController extends Controller
{
    public function __construct(private readonly ConsentWizardService $service)
    {
    }

    public function store(StoreConsentWizardRequest $request): ClientConsentResource
    {
        $consent = $this->service->submit(
            centerId: (int) $request->attributes->get('center_id'),
            actorId: (int) $request->user()->id,
            data: $request->validated(),
        );

        return ClientConsentResource::make($consent);
    }
}
