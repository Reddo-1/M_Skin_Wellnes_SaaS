<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlanResource;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SubscriptionController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $this->resolveBillingUser($request);

        if (!$user->can('subscriptions.view')) {
            throw new HttpException(403, 'No tienes permiso para ver la suscripción del centro.');
        }

        $subscription = $user->subscription('default');

        if ($subscription === null) {
            return response()->json([
                'has_subscription' => false,
            ]);
        }

        $stripeSubscription = $subscription->asStripeSubscription();

        return response()->json([
            'has_subscription' => true,
            'id' => $subscription->stripe_id,
            'status' => $subscription->stripe_status,
            'on_trial' => $subscription->onTrial(),
            'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
            'ends_at' => $subscription->ends_at?->toIso8601String(),
            'cancel_at_period_end' => (bool) $stripeSubscription->cancel_at_period_end,
            'current_period_start' => Carbon::createFromTimestamp($stripeSubscription->current_period_start)->toIso8601String(),
            'current_period_end' => Carbon::createFromTimestamp($stripeSubscription->current_period_end)->toIso8601String(),
            'plan' => PlanResource::make($user->center->plan),
            'card' => $user->pm_last_four !== null
                ? ['brand' => $user->pm_type, 'last_four' => $user->pm_last_four]
                : null,
        ]);
    }

    //llevar al admin al portal suyo de stripe para gestionar la subscripción.
    public function portal(Request $request): JsonResponse
    {
        $user = $this->resolveBillingUser($request);

        if (! $user->can('subscriptions.view')) {
            throw new HttpException(403, 'No tienes permiso para gestionar la suscripción del centro.');
        }

        $returnUrl = rtrim((string) config('app.frontend_url'), '/').'/panel/subscripcion';

        return response()->json([
            'portal_url' => $user->billingPortalUrl($returnUrl),
        ]);
    }

    private function resolveBillingUser(Request $request)
    {
        $user = $request->user();
        $center = $user->center;

        if ($center === null) {
            throw new HttpException(404, 'No tienes un centro asignado.');
        }

        if ($center->billing_user_id !== $user->id) {
            throw new HttpException(403, 'Solo el responsable de facturación del centro puede gestionar la suscripción.');
        }

        return $user;
    }
}
