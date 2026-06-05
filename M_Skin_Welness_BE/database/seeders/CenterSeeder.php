<?php

namespace Database\Seeders;

use App\Models\Center;
use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CenterSeeder extends Seeder
{
    public function run(): void
    {
        $premiumPlanId = Plan::query()->where('code', 'premium')->value('id');

        Center::updateOrCreate(
            ['slug' => 'demo'],
            [
                'uuid' => Str::uuid(),
                'name' => 'Centro Demo',
                'plan_id' => $premiumPlanId,
                'is_active' => true,
            ]
        );
    }
}
