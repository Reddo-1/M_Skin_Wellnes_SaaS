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
                'max_workers'           => 3,
                'allows_online_clients' => false,
                'allows_emails'         => false,
                'allows_public_page'    => false,
                'allows_custom_domain'  => false,
                'is_active'             => true,
                'created_at'            => $now,
                'updated_at'            => $now,
            ],
            [
                'code'                  => 'professional',
                'name'                  => 'Professional',
                'description'           => 'Clientes online, emails automáticos y página pública del centro.',
                'max_workers'           => 10,
                'allows_online_clients' => true,
                'allows_emails'         => true,
                'allows_public_page'    => true,
                'allows_custom_domain'  => false,
                'is_active'             => true,
                'created_at'            => $now,
                'updated_at'            => $now,
            ],
            [
                'code'                  => 'premium',
                'name'                  => 'Premium',
                'description'           => 'Todo Profesional más dominio personalizado y límites ampliados.',
                'max_workers'           => 50,
                'allows_online_clients' => true,
                'allows_emails'         => true,
                'allows_public_page'    => true,
                'allows_custom_domain'  => true,
                'is_active'             => true,
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
                'max_workers',
                'allows_online_clients',
                'allows_emails',
                'allows_public_page',
                'allows_custom_domain',
                'is_active',
                'updated_at',
            ]
        );
    }
}
