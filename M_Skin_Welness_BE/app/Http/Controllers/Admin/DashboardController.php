<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{AuditLog, Center, Plan, User};
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
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
        $centersDeactivated = Center::query()->where('is_active', false)->count();

        $totalUsers = User::query()->count();
        $usersLastMonth = User::query()->where('created_at', '<', $startOfMonth)->count();
        $usersChangePct = $usersLastMonth > 0
            ? round((($totalUsers - $usersLastMonth) / $usersLastMonth) * 100, 1)
            : null;

        $monthlyRevenue = $this->computeMonthlyRevenue();
        $monthlyRevenueLastMonth = $this->computeMonthlyRevenue($startOfLastMonth);
        $monthlyRevenueChangePercentage = $monthlyRevenueLastMonth > 0
            ? round((($monthlyRevenue - $monthlyRevenueLastMonth) / $monthlyRevenueLastMonth) * 100, 1)
            : null;

        return view('admin.dashboard', [
            'metrics' => [
                'centers' => [
                    'value' => $activeCenters,
                    'newThisMonth' => $centersNewThisMonth,
                    'deactivated' => $centersDeactivated,
                ],
                'users' => [
                    'value' => $totalUsers,
                    'changePct' => $usersChangePct,
                ],
                'monthly_revenue' => [
                    'value' => $monthlyRevenue,
                    'changePct' => $monthlyRevenueChangePercentage,
                ],
                'online_users' => $this->countOnlineUsers($now),
            ],
            'growth' => $this->buildGrowthSeries(),
            'planDistribution' => $this->buildPlanDistribution(),
            'recentActivity' => $this->buildRecentActivity(),
        ]);
    }

    private function countOnlineUsers(CarbonImmutable $now): int
    {
        $threshold = $now->subMinutes(15);

        return DB::table('personal_access_tokens')
            ->where('tokenable_type', User::class)
            ->where('last_used_at', '>=', $threshold)
            ->distinct('tokenable_id')
            ->count('tokenable_id');
    }

    private function computeMonthlyRevenue(?CarbonImmutable $createdBefore = null): float
    {
        $query = Center::query()
            ->where('centers.is_active', true)
            ->join('plans', 'centers.plan_id', '=', 'plans.id');

        if ($createdBefore !== null) {
            $query->where('centers.created_at', '<', $createdBefore);
        }

        return (float) $query->sum('plans.monthly_price');
    }

    private function buildGrowthSeries(): array
    {
        $months = [];
        $base = CarbonImmutable::now()->subMonths(11)->startOfMonth();

        for ($i = 0; $i < 12; $i++) {
            $cutoff = $base->addMonths($i)->endOfMonth();
            $count = Center::query()
                ->where('created_at', '<=', $cutoff)
                ->count();

            $months[] = [
                'label' => mb_convert_case($cutoff->locale('es')->isoFormat('MMM'), MB_CASE_TITLE, 'UTF-8'),
                'value' => $count,
            ];
        }

        return $months;
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
            ->whereIn('action', ['center.created', 'center.plan_changed', 'center.deactivated', 'center.activated', 'user.created'])
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

}
