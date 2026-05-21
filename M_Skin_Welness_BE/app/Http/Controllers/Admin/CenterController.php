<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{AuditLog, Center, Plan};
use App\Services\AdminCenterService;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\View\View;

class CenterController extends Controller
{
    public function __construct(private readonly AdminCenterService $centers)
    {
    }

    //listado de todos los centros y filtro si los ponemos.
    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'plan_id', 'status']);
        //en el servicio hace toda la query entera
        $centers = $this->centers->list($filters);
        $plans = Plan::query()->orderBy('id')->get();

        return view('admin.centers.index', [
            'centers' => $centers,
            'plans' => $plans,
            'filters' => $filters,
        ]);
    }

    //Listado de todos los datos de un centro junto a su subscripción de stripe cojiendolo de su api
    public function show(Center $center): View
    {
        $center->load(['plan', 'billingUser']);
        $center->loadCount('users');

        $recentActivity = AuditLog::query()
            ->with('actor:id,name')
            ->where('center_id', $center->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $subscription = $this->centers->getSubscriptionSummary($center);

        return view('admin.centers.show', [
            'center' => $center,
            'recentActivity' => $recentActivity,
            'subscription' => $subscription,
        ]);
    }

    public function impersonate(Center $center, Request $request): RedirectResponse
    {
        $redirectUrl = $this->centers->startImpersonation($center, $request->user());

        return redirect()->away($redirectUrl);
    }
}
