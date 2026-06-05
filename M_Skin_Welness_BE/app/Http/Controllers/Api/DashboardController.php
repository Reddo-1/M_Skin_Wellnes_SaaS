<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DashboardResource;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $service)
    {
    }

    public function index(Request $request): DashboardResource
    {
        if (!$request->user()->can('dashboard.view')) {
            throw new HttpException(403, 'No tienes permiso para ver el panel del centro.');
        }

        $centerId = (int) $request->attributes->get('center_id');

        return DashboardResource::make($this->service->summaryForCenter($centerId));
    }
}
