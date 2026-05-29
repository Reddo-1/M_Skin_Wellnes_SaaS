<?php

namespace App\Listeners;

use App\Models\{Center, Plan, User};
use App\Services\AuditLogService;
use Illuminate\Support\Facades\{DB, Log};
use Laravel\Cashier\Events\WebhookReceived;

class HandleStripeSubscriptionUpdated
{
    public function __construct(private readonly AuditLogService $auditLogs)
    {
    }

    public function handle(WebhookReceived $event): void
    {
        if (($event->payload['type'] ?? null) !== 'customer.subscription.updated') {
            return;
        }

        $subscription = $event->payload['data']['object'] ?? [];
        $stripeCustomerId = $subscription['customer'] ?? null;

        if ($stripeCustomerId === null) {
            return;
        }

        $user = User::query()->where('stripe_id', $stripeCustomerId)->first();

        if ($user === null) {
            Log::warning('subscription.updated recibido para customer sin user en BD', ['stripe_customer' => $stripeCustomerId]);
            return;
        }

        $center = $user->center;

        if ($center === null) {
            return;
        }

        $newPlan = $this->resolvePlanChange($center, $subscription);
        $status = $subscription['status'] ?? null;
        $deactivate = $center->is_active && in_array($status, ['past_due', 'unpaid', 'canceled', 'incomplete_expired'], true);
        $activate = ! $center->is_active && in_array($status, ['active', 'trialing'], true);

        if ($newPlan === null && ! $deactivate && ! $activate) {
            return;
        }

        DB::transaction(function () use ($center, $subscription, $newPlan, $deactivate, $activate, $status) {
            if ($newPlan !== null) {
                $previousPlanCode = $center->plan?->code;
                $center->plan_id = $newPlan->id;
                $center->save();

                $this->auditLogs->record(
                    action: 'center.plan_changed',
                    centerId: $center->id,
                    planId: $newPlan->id,
                    metadata: [
                        'from_plan_code' => $previousPlanCode,
                        'to_plan_code' => $newPlan->code,
                        'stripe_subscription_id' => $subscription['id'] ?? null,
                    ],
                );
            }

            if ($deactivate) {
                $center->is_active = false;
                $center->save();

                $this->auditLogs->record(
                    action: 'center.deactivated',
                    centerId: $center->id,
                    metadata: [
                        'reason' => 'subscription_'.$status,
                        'stripe_subscription_id' => $subscription['id'] ?? null,
                    ],
                );
            } elseif ($activate) {
                $center->is_active = true;
                $center->save();

                $this->auditLogs->record(
                    action: 'center.activated',
                    centerId: $center->id,
                    metadata: [
                        'reason' => 'subscription_'.$status,
                        'stripe_subscription_id' => $subscription['id'] ?? null,
                    ],
                );
            }
        });
    }

    private function resolvePlanChange(Center $center, array $subscription): ?Plan
    {
        $newPriceId = $subscription['items']['data'][0]['price']['id'] ?? null;

        if ($newPriceId === null) {
            return null;
        }

        $plan = Plan::query()->where('stripe_price_id', $newPriceId)->first();

        if ($plan === null) {
            Log::warning('subscription.updated con price fuera del catálogo', [
                'stripe_price' => $newPriceId,
                'stripe_subscription_id' => $subscription['id'] ?? null,
            ]);

            return null;
        }

        return $plan->id === $center->plan_id ? null : $plan;
    }
}
