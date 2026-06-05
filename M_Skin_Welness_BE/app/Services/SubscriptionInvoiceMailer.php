<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\SubscriptionInvoiceNotification;
use Illuminate\Support\Facades\Log;
use Throwable;

class SubscriptionInvoiceMailer
{
    public function send(User $user, string $stripeInvoiceId): void
    {
        try {
            $invoice = $user->findInvoice($stripeInvoiceId);

            if ($invoice === null) {
                return;
            }

            $user->notify(new SubscriptionInvoiceNotification(
                $invoice->pdf(self::issuerData()),
                $invoice->number ?? $stripeInvoiceId,
                $invoice->date()->format('d/m/Y'),
            ));
        } catch (Throwable $e) {
            Log::error('No se pudo enviar la factura de la suscripción', [
                'user_id' => $user->id,
                'invoice' => $stripeInvoiceId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public static function issuerData(): array
    {
        return [
            'vendor' => config('billing.vendor'),
            'vendorVat' => config('billing.vendor_vat'),
            'street' => config('billing.street'),
            'location' => config('billing.location'),
            'country' => config('billing.country'),
            'email' => config('billing.email'),
            'product' => 'Suscripción '.config('billing.vendor'),
        ];
    }
}
