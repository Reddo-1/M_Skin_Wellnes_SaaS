<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            ['name' => 'tarjeta'],
            ['name' => 'efectivo'],
            ['name' => 'transferencia'],
            ['name' => 'otro'],
        ];

        DB::table('payment_methods')->upsert($methods, ['name']);
    }
}
