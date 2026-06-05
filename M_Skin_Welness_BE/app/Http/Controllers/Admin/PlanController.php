<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        $plans = Plan::query()
            ->withCount('centers')
            ->orderBy('monthly_price')
            ->get();

        return view('admin.plans.index', [
            'plans' => $plans,
        ]);
    }
}
