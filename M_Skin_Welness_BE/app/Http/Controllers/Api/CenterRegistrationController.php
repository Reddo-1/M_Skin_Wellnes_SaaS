<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterCenterRequest;
use App\Services\CenterRegistrationService;
use Illuminate\Http\JsonResponse;

class CenterRegistrationController extends Controller
{
    public function __construct(private readonly CenterRegistrationService $service)
    {
    }

    public function register(RegisterCenterRequest $request): JsonResponse
    {
        $session = $this->service->startCheckout(
            admin: $request->validated('admin'),
            center: $request->validated('center'),
            plan: $request->plan(),
        );

        return response()->json([
            'checkout_url' => $session->url,
        ], 201);
    }
}
