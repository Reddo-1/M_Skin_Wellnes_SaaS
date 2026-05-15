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

        $profiles = [
            ['email' => 'cliente1@demo.test', 'body_type' => 'facial',   'notes' => 'Cliente habitual. Sin alergias conocidas.'],
            ['email' => 'cliente1@demo.test', 'body_type' => 'corporal', 'notes' => 'Sin alteraciones corporales permanentes.'],
            ['email' => 'cliente2@demo.test', 'body_type' => 'facial',   'notes' => 'Alergica al niquel. Embarazada hasta sep 2026.'],
            ['email' => 'cliente3@demo.test', 'body_type' => 'facial',   'notes' => 'Reactiva a productos con alcohol.'],
        ];

        foreach ($profiles as $p) {
            $userId = User::query()
                ->where('email', $p['email'])
                ->where('center_id', $centerId)
                ->value('id');

            if ($userId === null) {
                continue;
            }

            DB::table('client_profiles')->updateOrInsert(
                ['center_id' => $centerId, 'user_id' => $userId, 'body_type' => $p['body_type']],
                [
                    'general_notes' => $p['notes'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
