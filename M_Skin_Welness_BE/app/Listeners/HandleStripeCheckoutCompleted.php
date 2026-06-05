<?php

namespace App\Listeners;

use App\Models\{Center, Plan, User};
use App\Services\{AuditLogService, SubscriptionInvoiceMailer};
use Carbon\Carbon;
use Illuminate\Support\Facades\{DB, Log};
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookReceived;

class HandleStripeCheckoutCompleted
{
    public function __construct(
        private readonly AuditLogService $auditLogs,
        private readonly SubscriptionInvoiceMailer $invoiceMailer,
    ) {
    }

    public function handle(WebhookReceived $event): void
    {
        if (($event->payload['type'] ?? null) !== 'checkout.session.completed')return;

        $session = $event->payload['data']['object'] ?? [];
        $metadata = $session['metadata'] ?? [];

        if (!isset($metadata['pending_admin_email'], $metadata['pending_center_slug'])) return;

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
        $createdUser = null;
        $firstInvoiceId = null;

        DB::transaction(function () use ($metadata, $plan, $stripeCustomerId, $stripeSubscriptionId, &$createdUser, &$firstInvoiceId) {
            $center = Center::create([
                'uuid' => $metadata['pending_center_uuid'],
                'name' => $metadata['pending_center_name'],
                'slug' => $metadata['pending_center_slug'],
                'plan_id' => $plan->id,
                'is_active' => true,
            ]);

            $user = User::query()->create([
                'center_id' => $center->id,
                'name' => $metadata['pending_admin_name'],
                'email' => $metadata['pending_admin_email'],
                'password' => $metadata['pending_admin_password_hash'],
                'registration_source' => 'online',
                'is_active' => true,
            ]);

            //stripe_id no esta en $fillable (es columna añadida por Cashier); forceFill obligatorio
            $user->forceFill(['stripe_id' => $stripeCustomerId])->save();

            $center->billing_user_id = $user->id;
            $center->save();

            $user->assignRole('administrador');

            $firstInvoiceId = $this->syncSubscription($user, $stripeSubscriptionId);

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

            $user->sendEmailVerificationNotification();

            $createdUser = $user;
        });

        //la factura del alta se envía tras el commit (el usuario ya existe seguro; evita la carrera con el webhook invoice.payment_succeeded)
        if ($createdUser !== null && $firstInvoiceId !== null) {
            $this->invoiceMailer->send($createdUser, $firstInvoiceId);
        }
    }

    private function syncSubscription(User $user, string $stripeSubscriptionId): ?string
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

        return $stripeSubscription->latest_invoice;
    }
}
