<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            //lookups y catálogos globales
            RolesAndPermissionsSeeder::class,
            SessionStatusSeeder::class,
            AbsenceTypeSeeder::class,
            PaymentMethodSeeder::class,
            PaymentStatusSeeder::class,
            SaleStatusSeeder::class,
            StockMovementTypeSeeder::class,
            SkinTypeSeeder::class,
            VariationSeeder::class,
            PlanSeeder::class,

            //tenant
            CenterSeeder::class,
            UserSeeder::class,
            RoomSeeder::class,
            MachineSeeder::class,
            TimeSlotSeeder::class,
            TreatmentSeeder::class,
            WorkerScheduleSeeder::class,
            AppointmentSeeder::class,
            ProductSeeder::class,
            ProductStockSeeder::class,
            ClientProfileSeeder::class,
            SkinEvaluationSeeder::class,
            SaleSeeder::class,
        ]);
    }
}
