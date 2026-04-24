<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['name' => 'pendiente'],
            ['name' => 'completado'],
            ['name' => 'fallido'],
            ['name' => 'reembolsado'],
            ['name' => 'cancelado'],
        ];

        DB::table('payment_statuses')->upsert($statuses, ['name']);
    }
}
