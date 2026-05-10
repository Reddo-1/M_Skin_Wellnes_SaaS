<?php

namespace Database\Seeders;

use App\Models\Center;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClientProfileSeeder extends Seeder
{
    public function run(): void
    {
        $centerId = Center::query()->where('slug', 'demo')->value('id');

        if ($centerId === null) {
            return;
        }

        $diagnoId = User::query()->where('email', 'diagno@demo.test')->value('id');

        $skinTypes = DB::table('skin_types')->pluck('id', 'name')->all();

        $profiles = [
            ['email' => 'cliente1@demo.test', 'skin_type' => 'mixta',    'notes' => 'Cliente habitual, piel mixta con tendencia oleosa en zona T.'],
            ['email' => 'cliente2@demo.test', 'skin_type' => 'seca',     'notes' => 'Piel deshidratada. Recomendar hidratación regular.'],
            ['email' => 'cliente3@demo.test', 'skin_type' => 'sensible', 'notes' => 'Reactiva a productos con alcohol.'],
        ];

        foreach ($profiles as $p) {
            $userId = User::query()
                ->where('email', $p['email'])
                ->where('center_id', $centerId)
                ->value('id');

            $skinTypeId = $skinTypes[$p['skin_type']] ?? null;

            if ($userId === null || $skinTypeId === null || $diagnoId === null) {
                continue;
            }

            DB::table('client_profiles')->updateOrInsert(
                ['center_id' => $centerId, 'user_id' => $userId],
                [
                    'skin_type_id' => $skinTypeId,
                    'last_review_date' => now()->toDateString(),
                    'updated_by_user_id' => $diagnoId,
                    'general_notes' => $p['notes'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
