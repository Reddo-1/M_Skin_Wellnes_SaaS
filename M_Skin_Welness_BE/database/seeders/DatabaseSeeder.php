<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            SessionStatusSeeder::class,
            AbsenceTypeSeeder::class,
            PaymentMethodSeeder::class,
            PaymentStatusSeeder::class,
            SaleStatusSeeder::class,
            StockMovementTypeSeeder::class,
            SkinTypeSeeder::class,
            VariationSeeder::class,
            PlanSeeder::class,
        ]);
    }
}
