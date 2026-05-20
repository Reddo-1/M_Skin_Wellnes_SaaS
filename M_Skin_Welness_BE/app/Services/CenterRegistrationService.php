<?php

namespace App\Services;

use App\Models\Plan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Cashier\Cashier;
use Stripe\Checkout\Session;

class CenterRegistrationService
{
    public function startCheckout(array $admin, array $center, Plan $plan): Session
    {
        $frontend = rtrim((string) config('app.frontend_url'), '/');

        return Cashier::stripe()->checkout->sessions->create([
            'mode' => 'subscription',
            //Productos de linea-solo va a ser 1
            'line_items' => [[
                'price' => $plan->stripe_price_id,
                'quantity' => 1,
            ]],
            //vamos a poner el periodo de prueba si o si 14 dias.
            'subscription_data' => [
                'trial_period_days' => 14,
                'metadata' => [
                    'plan_id' => (string) $plan->id,
                    'plan_code' => $plan->code,
                ],
            ],
            'customer_email' => $admin['email'],
            'success_url' => $frontend.'/registro/exito',
            'cancel_url' => $frontend.'/registro/cancelado',
            'allow_promotion_codes' => true,
            'metadata' => [
                'pending_admin_name' => $admin['name'],
                'pending_admin_email' => $admin['email'],
                'pending_admin_password_hash' => Hash::make($admin['password']),
                'pending_center_name' => $center['name'],
                'pending_center_slug' => $center['slug'],
                'pending_center_uuid' => (string) Str::uuid(),
                'pending_plan_id' => (string) $plan->id,
            ],
        ]);
    }
}
