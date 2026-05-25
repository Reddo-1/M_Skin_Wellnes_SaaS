<?php

namespace App\Services;

use App\Models\{Appointment, ProductStock, Sale, User};
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class DashboardService
{
    public function summaryForCenter(int $centerId): array
    {
        $now = CarbonImmutable::now();
        $startOfDay = $now->startOfDay();
        $endOfDay = $now->endOfDay();
        $startOfMonth = $now->startOfMonth();
        $endOfMonth = $now->endOfMonth();
        $lastMonth = $now->subMonthNoOverflow();
        $startOfLastMonth = $lastMonth->startOfMonth();
        $endOfLastMonth = $lastMonth->endOfMonth();

        return [
            'appointments_today' => $this->appointmentsToday($centerId, $startOfDay, $endOfDay),
            'revenue' => $this->revenue(
                $centerId,
                $startOfDay,
                $endOfDay,
                $startOfMonth,
                $endOfMonth,
                $startOfLastMonth,
                $endOfLastMonth,
            ),
            'low_stock' => $this->lowStock($centerId),
            'monthly_metrics' => $this->monthlyMetrics($centerId, $startOfMonth, $endOfMonth),
        ];
    }

    private function appointmentsToday(
        int $centerId,
        CarbonImmutable $startOfDay,
        CarbonImmutable $endOfDay,
    ): array {
        $base = Appointment::query()
            ->forCenter($centerId)
            ->notCancelled()
            ->whereBetween('starts_at', [$startOfDay, $endOfDay]);

        $total = (clone $base)->count();

        $inProgress = (clone $base)
            ->where('status_id', (int) config('lookups.session_statuses.en_curso'))
            ->count();

        $list = (clone $base)
            ->with(['status', 'treatment', 'room', 'client', 'worker'])
            ->orderBy('starts_at')
            ->limit(10)
            ->get();

        return [
            'total' => $total,
            'in_progress' => $inProgress,
            'list' => $list,
        ];
    }

    private function revenue(
        int $centerId,
        CarbonImmutable $startOfDay,
        CarbonImmutable $endOfDay,
        CarbonImmutable $startOfMonth,
        CarbonImmutable $endOfMonth,
        CarbonImmutable $startOfLastMonth,
        CarbonImmutable $endOfLastMonth,
    ): array {
        $paidStatusId = (int) config('lookups.sale_statuses.pagada');

        return [
            'today' => $this->sumPaidSales($centerId, $paidStatusId, $startOfDay, $endOfDay),
            'this_month' => $this->sumPaidSales($centerId, $paidStatusId, $startOfMonth, $endOfMonth),
            'last_month' => $this->sumPaidSales($centerId, $paidStatusId, $startOfLastMonth, $endOfLastMonth),
        ];
    }

    private function sumPaidSales(
        int $centerId,
        int $paidStatusId,
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): float {
        return (float) Sale::query()
            ->forCenter($centerId)
            ->where('status_id', $paidStatusId)
            ->whereBetween('paid_at', [$start, $end])
            ->sum('total');
    }

    private function lowStock(int $centerId): Collection
    {
        return ProductStock::query()
            ->belowMinimum()
            ->where('product_stocks.center_id', $centerId)
            ->with('product')
            ->orderBy('product_stocks.current_quantity')
            ->limit(5)
            ->get();
    }

    private function monthlyMetrics(int $centerId, CarbonImmutable $startOfMonth, CarbonImmutable $endOfMonth): array
    {
        $newClients = User::query()
            ->forCenter($centerId)
            ->role('cliente')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();

        $completedSessions = Appointment::query()
            ->forCenter($centerId)
            ->where('status_id', (int) config('lookups.session_statuses.realizada'))
            ->whereBetween('ends_at', [$startOfMonth, $endOfMonth])
            ->count();

        return [
            'new_clients' => $newClients,
            'completed_sessions' => $completedSessions,
        ];
    }
}
