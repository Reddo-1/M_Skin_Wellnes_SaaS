<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCenterRequest;
use App\Http\Resources\CenterResource;
use App\Models\Center;

class CenterController extends Controller
{
    public function show(Center $center): CenterResource
    {
        $this->authorize('view', $center);

        return CenterResource::make($center->load('plan'));
    }

    public function update(UpdateCenterRequest $request, Center $center): CenterResource
    {
        $center->fill($request->validated())->save();

        return CenterResource::make($center->load('plan'));
    }
}
