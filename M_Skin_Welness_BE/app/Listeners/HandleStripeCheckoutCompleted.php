<?php

namespace App\Listeners;

use App\Models\Center;
use App\Models\Plan;
use App\Models\User;
use App\Services\AuditLogService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookReceived;

class HandleStripeCheckoutCompleted
{
    public function __construct(private readonly AuditLogService $auditLogs)
    {
    }

    public function handle(WebhookReceived $event): void
    {
        if (($event->payload['type'] ?? null) !== 'checkout.session.completed') {
            return;
        }

        $session = $event->payload['data']['object'] ?? [];
        $metadata = $session['metadata'] ?? [];

        if (!isset($metadata['pending_admin_email'], $metadata['pending_center_slug'])) {
            return;
        }

        $stripeCustomerId = $session['customer'] ?? null;
        $stripeSubscriptionId = $session['subscription'] ?? null;

        if ($stripeCustomerId === null || $stripeSubscriptionId === null) {
            Log::warning('checkout.session.completed sin customer o subscription', ['session_id' => $session['id'] ?? null]);
            return;
        }

        if (User::query()->where('stripe_id', $stripeCustomerId)->exists()) {
            return;
        }

        $plan = Plan::query()->find((int) $metadata['pending_plan_id']);

        if ($plan === null) {
            Log::error('Plan no encontrado al completar checkout', ['plan_id' => $metadata['pending_plan_id'] ?? null]);
            return;
        }

        DB::transaction(function () use ($metadata, $plan, $stripeCustomerId, $stripeSubscriptionId) {
            $center = Center::create([
                'uuid' => $metadata['pending_center_uuid'],
                'name' => $metadata['pending_center_name'],
                'slug' => $metadata['pending_center_slug'],
                'plan_id' => $plan->id,
                'is_active' => true,
            ]);

            $user = User::create([
                'center_id' => $center->id,
                'name' => $metadata['pending_admin_name'],
                'email' => $metadata['pending_admin_email'],
                'password' => $metadata['pending_admin_password_hash'],
                'registration_source' => 'online',
                'is_active' => true,
            ]);

            $user->forceFill(['stripe_id' => $stripeCustomerId])->save();

            $center->billing_user_id = $user->id;
            $center->save();

            $user->assignRole('administrador');

            $this->syncSubscription($user, $stripeSubscriptionId);

            $this->auditLogs->record(
                action: 'center.created',
                centerId: $center->id,
                planId: $plan->id,
                metadata: [
                    'slug' => $center->slug,
                    'plan_code' => $plan->code,
                    'source' => 'stripe_checkout',
                ],
            );

            $this->auditLogs->record(
                action: 'user.created',
                centerId: $center->id,
                metadata: [
                    'user_id' => $user->id,
                    'role' => 'administrador',
                    'source' => 'stripe_checkout',
                ],
            );
        });
    }

    private function syncSubscription(User $user, string $stripeSubscriptionId): void
    {
        $stripeSubscription = Cashier::stripe()->subscriptions->retrieve($stripeSubscriptionId, [
            'expand' => ['items.data.price'],
        ]);

        $trialEndsAt = $stripeSubscription->trial_end !== null
            ? Carbon::createFromTimestamp($stripeSubscription->trial_end)
            : null;

        $endsAt = $stripeSubscription->ended_at !== null
            ? Carbon::createFromTimestamp($stripeSubscription->ended_at)
            : null;

        $firstItem = $stripeSubscription->items->data[0];

        $subscription = $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => $stripeSubscription->id,
            'stripe_status' => $stripeSubscription->status,
            'stripe_price' => $firstItem->price->id,
            'quantity' => $firstItem->quantity,
            'trial_ends_at' => $trialEndsAt,
            'ends_at' => $endsAt,
        ]);

        foreach ($stripeSubscription->items->data as $item) {
            $subscription->items()->create([
                'stripe_id' => $item->id,
                'stripe_product' => $item->price->product,
                'stripe_price' => $item->price->id,
                'quantity' => $item->quantity,
            ]);
        }

        if ($trialEndsAt !== null) {
            $user->trial_ends_at = $trialEndsAt;
            $user->save();
        }
    }
}
