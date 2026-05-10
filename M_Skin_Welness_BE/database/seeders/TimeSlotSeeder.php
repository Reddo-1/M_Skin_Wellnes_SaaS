<?php

namespace Database\Seeders;

use App\Models\Center;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TimeSlotSeeder extends Seeder
{
    public function run(): void
    {
        $centerId = Center::query()->where('slug', 'demo')->value('id');

        if ($centerId === null) {
            return;
        }

        $slots = [
            ['name' => 'Mañana',   'start_time' => '09:00:00', 'end_time' => '14:00:00'],
            ['name' => 'Tarde',    'start_time' => '16:00:00', 'end_time' => '20:00:00'],
            ['name' => 'Completo', 'start_time' => '09:00:00', 'end_time' => '20:00:00'],
        ];

        foreach ($slots as $s) {
            DB::table('time_slots')->updateOrInsert(
                [
                    'center_id' => $centerId,
                    'start_time' => $s['start_time'],
                    'end_time' => $s['end_time'],
                ],
                [
                    'name' => $s['name'],
                    'is_active' => true,
                ]
            );
        }
    }
}
