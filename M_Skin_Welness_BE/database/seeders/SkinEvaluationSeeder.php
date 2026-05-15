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

        $diagnoId = User::query()
            ->where('email', 'marc@gmail.com')
            ->value('id');

        if ($diagnoId === null) {
            return;
        }

        $skinTypeId = DB::table('skin_types')->where('name', 'mixta')->value('id');

        if ($skinTypeId === null) {
            return;
        }

        $profiles = DB::table('client_profiles')
            ->where('center_id', $centerId)
            ->get();

        foreach ($profiles as $profile) {
            $evaluationId = DB::table('skin_evaluations')->insertGetId([
                'center_id' => $centerId,
                'user_id' => $profile->user_id,
                'client_profile_id' => $profile->id,
                'skin_type_id' => $skinTypeId,
                'evaluation_date' => now()->subDays(7)->toDateString(),
                'professional_id' => $diagnoId,
                'general_notes' => 'Evaluacion inicial. Estado general adecuado.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            //marcamos esa evaluacion como vigente para el perfil
            DB::table('client_profiles')
                ->where('id', $profile->id)
                ->update(['current_skin_evaluation_id' => $evaluationId, 'updated_at' => now()]);
        }
    }
}
