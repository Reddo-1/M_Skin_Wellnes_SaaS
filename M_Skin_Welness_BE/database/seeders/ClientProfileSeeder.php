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

        $notes = [
            'Cliente habitual. Sin alergias conocidas.',
            'Piel sensible. Evitar productos con alcohol.',
            'Alergia al níquel.',
            'Sin alteraciones permanentes.',
            'Reactiva a fragancias fuertes.',
        ];

        $clients = User::role('cliente')->where('center_id', $centerId)->orderBy('id')->take(10)->get();

        foreach ($clients as $index => $client) {
            DB::table('client_profiles')->updateOrInsert(
                ['center_id' => $centerId, 'user_id' => $client->id, 'body_type' => 'facial'],
                [
                    'general_notes' => $notes[$index % count($notes)],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            if ($index % 2 === 0) {
                DB::table('client_profiles')->updateOrInsert(
                    ['center_id' => $centerId, 'user_id' => $client->id, 'body_type' => 'corporal'],
                    [
                        'general_notes' => 'Seguimiento corporal.',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}
