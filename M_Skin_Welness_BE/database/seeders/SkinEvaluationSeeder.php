<?php

namespace Database\Seeders;

use App\Models\Center;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SkinEvaluationSeeder extends Seeder
{
    public function run(): void
    {
        $centerId = Center::query()->where('slug', 'demo')->value('id');

        if ($centerId === null) {
            return;
        }

        $diagnoId = User::query()->where('email', 'diagno@demo.test')->value('id');

        if ($diagnoId === null) {
            return;
        }

        $profiles = DB::table('client_profiles')
            ->where('center_id', $centerId)
            ->get();

        foreach ($profiles as $profile) {
            DB::table('skin_evaluations')->updateOrInsert(
                [
                    'center_id' => $centerId,
                    'client_profile_id' => $profile->id,
                    'evaluation_date' => now()->subDays(7)->toDateString(),
                ],
                [
                    'user_id' => $profile->user_id,
                    'skin_type_id' => $profile->skin_type_id,
                    'professional_id' => $diagnoId,
                    'general_notes' => 'Evaluación inicial. Se observa estado general adecuado.',
                    'created_at' => now(),
                ]
            );
        }
    }
}
