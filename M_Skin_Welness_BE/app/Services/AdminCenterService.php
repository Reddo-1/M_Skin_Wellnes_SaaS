<?php

namespace App\Services;

use App\Models\{Center, User};
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Throwable;

class AdminCenterService
{
    public function list(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Center::query()
            ->with('plan')
            ->withCount('users');

        if (isset($filters['search']) && $filters['search'] !== '') {
            $term = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'ilike', $term)
                  ->orWhere('slug', 'ilike', $term);
            });
        }

        if (isset($filters['plan_id']) && $filters['plan_id'] !== '') {
            $query->where('plan_id', (int) $filters['plan_id']);
        }

        if (isset($filters['status']) && in_array($filters['status'], ['active', 'inactive'], true)) {
            $query->where('is_active', $filters['status'] === 'active');
        }

        return $query->orderByDesc('created_at')->paginate($perPage)->withQueryString();
    }

    public function startImpersonation(Center $center, User $superadmin): string
    {
        $expiresAt = now()->addMinutes(60);
        $name = 'impersonation:center:'.$center->id;

        $token = $superadmin->createToken($name, ['*'], $expiresAt)->plainTextToken;

        $base = rtrim((string) config('app.frontend_url'), '/');

        return $base.'/panel?token='.urlencode($token).'&center_id='.$center->id;
    }

    //Obtener todos los datos de la subscripción
    public function getSubscriptionSummary(Center $center): ?array
    {
        $billingUser = $center->billingUser;

        if ($billingUser === null) {
            return null;
        }

        $subscription = $billingUser->subscription('default');

        if ($subscription === null) {
            return null;
        }

        $summary = [
            'stripe_id' => $subscription->stripe_id,
            'status' => $subscription->stripe_status,
            'on_trial' => $subscription->onTrial(),
            'on_grace_period' => $subscription->onGracePeriod(),
            'trial_ends_at' => $subscription->trial_ends_at,
            'ends_at' => $subscription->ends_at,
            'current_period_end' => null,
            'cancel_at_period_end' => null,
            'card' => $billingUser->pm_last_four !== null
                ? ['brand' => $billingUser->pm_type, 'last_four' => $billingUser->pm_last_four]
                : null,
            'live_data_available' => true,
        ];

        //Aqui es donde coje los datos de stripe y los devuelve o no si esta caida la api y/o no tiene subscripción
        try {
            $stripeSubscription = $subscription->asStripeSubscription();
            $summary['current_period_end'] = Carbon::createFromTimestamp($stripeSubscription->current_period_end);
            $summary['cancel_at_period_end'] = (bool) $stripeSubscription->cancel_at_period_end;
        } catch (Throwable $e) {
            Log::warning('No se pudo leer la suscripción de Stripe para la ficha del centro', [
                'center_id' => $center->id,
                'subscription_id' => $subscription->stripe_id,
                'error' => $e->getMessage(),
            ]);
            $summary['live_data_available'] = false;
        }

        return $summary;
    }
}
