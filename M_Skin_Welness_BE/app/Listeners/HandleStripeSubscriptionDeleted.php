<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\{DB, Log};
use Laravel\Cashier\Events\WebhookReceived;

class HandleStripeSubscriptionDeleted
{
    public function __construct(private readonly AuditLogService $auditLogs)
    {
    }

    public function handle(WebhookReceived $event): void
    {
        if (($event->payload['type'] ?? null) !== 'customer.subscription.deleted') {
            return;
        }

        $subscription = $event->payload['data']['object'] ?? [];
        $stripeCustomerId = $subscription['customer'] ?? null;

        if ($stripeCustomerId === null) {
            return;
        }

        $user = User::query()->where('stripe_id', $stripeCustomerId)->first();

        if ($user === null) {
            Log::warning('subscription.deleted recibido para customer sin user en BD', ['stripe_customer' => $stripeCustomerId]);
            return;
        }

        $center = $user->center;

        if ($center === null || ! $center->is_active) {
            return;
        }

        DB::transaction(function () use ($center, $subscription) {
            $center->is_active = false;
            $center->save();

            $this->auditLogs->record(
                action: 'center.deactivated',
                centerId: $center->id,
                metadata: [
                    'reason' => 'subscription_ended',
                    'stripe_subscription_id' => $subscription['id'] ?? null,
                ],
            );
        });
    }
}
