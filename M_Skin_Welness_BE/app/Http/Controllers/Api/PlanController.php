<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PlanController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Plan::class);

        $plans = Plan::query()->orderBy('max_workers')->get();

        return PlanResource::collection($plans);
    }

    public function show(Plan $plan): PlanResource
    {
        $this->authorize('view', $plan);

        return PlanResource::make($plan);
    }
}
