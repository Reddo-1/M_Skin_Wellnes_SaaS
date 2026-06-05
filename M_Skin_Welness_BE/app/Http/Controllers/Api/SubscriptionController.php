<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlanResource;
use App\Services\SubscriptionInvoiceMailer;
use Carbon\Carbon;
use Illuminate\Http\{JsonResponse, Request};
use Symfony\Component\HttpFoundation\Response;
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
        $firstItem = $stripeSubscription->items->data[0] ?? null;

        return response()->json([
            'has_subscription' => true,
            'id' => $subscription->stripe_id,
            'status' => $subscription->stripe_status,
            'on_trial' => $subscription->onTrial(),
            'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
            'ends_at' => $subscription->ends_at?->toIso8601String(),
            'cancel_at_period_end' => (bool) $stripeSubscription->cancel_at_period_end,
            'current_period_start' => $firstItem?->current_period_start
                ? Carbon::createFromTimestamp($firstItem->current_period_start)->toIso8601String()
                : null,
            'current_period_end' => $firstItem?->current_period_end
                ? Carbon::createFromTimestamp($firstItem->current_period_end)->toIso8601String()
                : null,
            'plan' => PlanResource::make($user->center->plan),
        ]);
    }

    public function invoices(Request $request): JsonResponse
    {
        $user = $this->resolveBillingUser($request);

        if (! $user->can('subscriptions.view')) {
            throw new HttpException(403, 'No tienes permiso para ver las facturas de la suscripción.');
        }

        if (! $user->hasStripeId()) {
            return response()->json(['data' => []]);
        }

        $invoices = $user->invoices()->map(fn ($invoice) => [
            'id' => $invoice->id,
            'number' => $invoice->number,
            'date' => $invoice->date()->toIso8601String(),
            'total' => $invoice->total(),
            'status' => $invoice->status,
        ])->values();

        return response()->json(['data' => $invoices]);
    }

    public function invoicePdf(Request $request, string $invoiceId): Response
    {
        $user = $this->resolveBillingUser($request);

        if (! $user->can('subscriptions.view')) {
            throw new HttpException(403, 'No tienes permiso para descargar las facturas de la suscripción.');
        }

        $invoice = $user->hasStripeId() ? $user->findInvoice($invoiceId) : null;

        if ($invoice === null) {
            throw new HttpException(404, 'La factura no existe.');
        }

        return $invoice->download(SubscriptionInvoiceMailer::issuerData());
    }

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
