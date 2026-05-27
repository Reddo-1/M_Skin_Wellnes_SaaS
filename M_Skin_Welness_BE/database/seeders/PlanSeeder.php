<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $plans = [
            [
                'code'                  => 'starter',
                'name'                  => 'Starter',
                'description'           => 'Plan inicial: gestión del centro y agenda local.',
                'monthly_price'         => 29,
                'max_workers'           => 3,
                'allows_online_clients' => false,
                'allows_emails'         => false,
                'allows_public_page'    => false,
                'is_active'             => true,
                'stripe_price_id'       => env('STRIPE_PRICE_STARTER'),
                'created_at'            => $now,
                'updated_at'            => $now,
            ],
            [
                'code'                  => 'professional',
                'name'                  => 'Professional',
                'description'           => 'Clientes online y emails automáticos del centro.',
                'monthly_price'         => 49,
                'max_workers'           => 10,
                'allows_online_clients' => true,
                'allows_emails'         => true,
                'allows_public_page'    => false,
                'is_active'             => true,
                'stripe_price_id'       => env('STRIPE_PRICE_PROFESSIONAL'),
                'created_at'            => $now,
                'updated_at'            => $now,
            ],
            [
                'code'                  => 'premium',
                'name'                  => 'Premium',
                'description'           => 'Todo Profesional más página pública del centro.',
                'monthly_price'         => 99,
                'max_workers'           => 50,
                'allows_online_clients' => true,
                'allows_emails'         => true,
                'allows_public_page'    => true,
                'is_active'             => true,
                'stripe_price_id'       => env('STRIPE_PRICE_PREMIUM'),
                'created_at'            => $now,
                'updated_at'            => $now,
            ],
        ];

        DB::table('plans')->upsert(
            $plans,
            ['code'],
            [
                'name',
                'description',
                'monthly_price',
                'max_workers',
                'allows_online_clients',
                'allows_emails',
                'allows_public_page',
                'is_active',
                'stripe_price_id',
                'updated_at',
            ]
        );
    }
}
