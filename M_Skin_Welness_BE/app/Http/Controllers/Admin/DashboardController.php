<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Center;
use App\Models\Plan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\View\View;

class DashboardController extends Controller
{
    //precio mensual en euros, fuente real en stripe; mirror local para UI sin pegar al API
    private const PLAN_PRICES_EUR = [
        'starter' => 49,
        'professional' => 49,
        'premium' => 99,
    ];

    public function index(): View
    {
        $now = CarbonImmutable::now();
        $startOfMonth = $now->startOfMonth();
        $startOfLastMonth = $startOfMonth->subMonth();

        $activeCenters = Center::query()->where('is_active', true)->count();
        $centersNewThisMonth = Center::query()
            ->where('is_active', true)
            ->where('created_at', '>=', $startOfMonth)
            ->count();
        $centersInOnboarding = Center::query()->where('is_active', false)->count();

        $totalUsers = User::query()->count();
        $usersLastMonth = User::query()->where('created_at', '<', $startOfMonth)->count();
        $usersChangePct = $usersLastMonth > 0
            ? round((($totalUsers - $usersLastMonth) / $usersLastMonth) * 100, 1)
            : null;

        $mrr = $this->computeMrr();
        $mrrLastMonth = $this->computeMrrAt($startOfLastMonth);
        $mrrChangePct = $mrrLastMonth > 0
            ? round((($mrr - $mrrLastMonth) / $mrrLastMonth) * 100, 1)
            : null;

        return view('admin.dashboard', [
            'metrics' => [
                'centers' => [
                    'value' => $activeCenters,
                    'newThisMonth' => $centersNewThisMonth,
                    'onboarding' => $centersInOnboarding,
                ],
                'users' => [
                    'value' => $totalUsers,
                    'changePct' => $usersChangePct,
                ],
                'mrr' => [
                    'value' => $mrr,
                    'changePct' => $mrrChangePct,
                ],
            ],
            'growth' => $this->buildGrowthSeries(),
            'centers' => $this->buildCentersList(),
            'planDistribution' => $this->buildPlanDistribution(),
            'recentActivity' => $this->buildRecentActivity(),
        ]);
    }

    private function computeMrr(): int
    {
        $byPlan = Center::query()
            ->where('is_active', true)
            ->selectRaw('plan_id, count(*) as c')
            ->groupBy('plan_id')
            ->pluck('c', 'plan_id');

        if ($byPlan->isEmpty()) {
            return 0;
        }

        $planCodes = Plan::query()
            ->whereIn('id', $byPlan->keys())
            ->pluck('code', 'id');

        $total = 0;
        foreach ($byPlan as $planId => $count) {
            $code = $planCodes[$planId] ?? null;
            $total += (self::PLAN_PRICES_EUR[$code] ?? 0) * $count;
        }

        return $total;
    }

    private function computeMrrAt(CarbonImmutable $at): int
    {
        $byPlan = Center::query()
            ->where('is_active', true)
            ->where('created_at', '<', $at)
            ->selectRaw('plan_id, count(*) as c')
            ->groupBy('plan_id')
            ->pluck('c', 'plan_id');

        if ($byPlan->isEmpty()) {
            return 0;
        }

        $planCodes = Plan::query()
            ->whereIn('id', $byPlan->keys())
            ->pluck('code', 'id');

        $total = 0;
        foreach ($byPlan as $planId => $count) {
            $code = $planCodes[$planId] ?? null;
            $total += (self::PLAN_PRICES_EUR[$code] ?? 0) * $count;
        }

        return $total;
    }

    //serie acumulada de centros activos en los ultimos 12 meses
    private function buildGrowthSeries(): array
    {
        $months = [];
        $base = CarbonImmutable::now()->subMonths(11)->startOfMonth();

        for ($i = 0; $i < 12; $i++) {
            $cutoff = $base->addMonths($i)->endOfMonth();
            $count = Center::query()
                ->where('is_active', true)
                ->where('created_at', '<=', $cutoff)
                ->count();

            $months[] = [
                'label' => mb_convert_case($cutoff->locale('es')->isoFormat('MMM'), MB_CASE_TITLE, 'UTF-8'),
                'value' => $count,
            ];
        }

        return $months;
    }

    private function buildCentersList(): array
    {
        return Center::query()
            ->with('plan')
            ->withCount('users')
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function (Center $center) {
                $planCode = $center->plan?->code;
                return [
                    'name' => $center->name,
                    'initials' => $this->initials($center->name),
                    'workers' => $center->users_count,
                    'plan' => [
                        'name' => $center->plan?->name ?? 'Sin plan',
                        'code' => $planCode,
                    ],
                    'mrr' => self::PLAN_PRICES_EUR[$planCode] ?? 0,
                    'active' => $center->is_active,
                ];
            })
            ->all();
    }

    private function buildPlanDistribution(): array
    {
        $countsByPlan = Center::query()
            ->where('is_active', true)
            ->selectRaw('plan_id, count(*) as c')
            ->groupBy('plan_id')
            ->pluck('c', 'plan_id');

        $total = $countsByPlan->sum();

        return Plan::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->map(function (Plan $plan) use ($countsByPlan, $total) {
                $count = (int) ($countsByPlan[$plan->id] ?? 0);
                return [
                    'name' => $plan->name,
                    'code' => $plan->code,
                    'count' => $count,
                    'percent' => $total > 0 ? round(($count / $total) * 100) : 0,
                ];
            })
            ->all();
    }

    private function buildRecentActivity(): array
    {
        return AuditLog::query()
            ->with(['center:id,name', 'actor:id,name'])
            ->whereIn('action', ['center.created', 'user.created', 'center.deactivated'])
            ->orderByDesc('created_at')
            ->limit(4)
            ->get()
            ->map(function (AuditLog $log) {
                return [
                    'action' => $log->action,
                    'center' => $log->center?->name,
                    'actor' => $log->actor?->name,
                    'when' => $log->created_at->locale('es')->diffForHumans(),
                ];
            })
            ->all();
    }

    //primeras dos letras significativas para usar como avatar del centro
    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $letters = '';
        foreach ($parts as $part) {
            if ($part !== '' && mb_strlen($letters) < 2) {
                $letters .= mb_strtoupper(mb_substr($part, 0, 1));
            }
        }
        return $letters === '' ? '·' : $letters;
    }
}
