<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\SubscriptionInvoiceMailer;
use Laravel\Cashier\Events\WebhookReceived;

class SendSubscriptionInvoice
{
    public function __construct(private readonly SubscriptionInvoiceMailer $mailer)
    {
    }

    public function handle(WebhookReceived $event): void
    {
        if (($event->payload['type'] ?? null) !== 'invoice.payment_succeeded') {
            return;
        }

        $invoice = $event->payload['data']['object'] ?? [];

        //la factura del alta la manda HandleStripeCheckoutCompleted (el usuario aún puede no existir al recibir este webhook); aquí solo las renovaciones
        if (($invoice['billing_reason'] ?? null) === 'subscription_create') {
            return;
        }

        $customerId = $invoice['customer'] ?? null;
        $invoiceId = $invoice['id'] ?? null;

        if ($customerId === null || $invoiceId === null) {
            return;
        }

        $user = User::query()->where('stripe_id', $customerId)->first();

        if ($user === null) {
            return;
        }

        $this->mailer->send($user, $invoiceId);
    }
}
