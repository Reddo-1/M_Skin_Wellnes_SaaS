<?php

namespace Database\Seeders;

use App\Models\{Center, Treatment, User};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConsentSeeder extends Seeder
{
    public function run(): void
    {
        $centerId = Center::query()->where('slug', 'demo')->value('id');

        if ($centerId === null) {
            return;
        }

        $diagnoId = User::role('diagnosticador')->where('center_id', $centerId)->value('id');

        if ($diagnoId === null) {
            return;
        }

        $treatments = Treatment::query()
            ->where('center_id', $centerId)
            ->where('is_active', true)
            ->pluck('id');

        $clients = User::role('cliente')->where('center_id', $centerId)->orderBy('id')->get();

        $signedAt = now()->subDays(6);

        foreach ($clients as $index => $client) {
            DB::table('client_consents')->updateOrInsert(
                ['center_id' => $centerId, 'user_id' => $client->id],
                [
                    'reviewed_by_user_id' => $diagnoId,
                    'clinical_photos_consent' => true,
                    'marketing_data_consent' => $index % 2 === 0,
                    'commercial_images_consent' => $index % 3 === 0,
                    'signature_user_file_id' => null,
                    'signed_at' => $signedAt,
                    'notes' => null,
                    'is_active' => true,
                    'created_at' => $signedAt,
                    'updated_at' => $signedAt,
                ]
            );

            foreach ($treatments as $treatmentId) {
                DB::table('treatment_consents')->updateOrInsert(
                    ['center_id' => $centerId, 'user_id' => $client->id, 'treatment_id' => $treatmentId],
                    [
                        'reviewed_by_user_id' => $diagnoId,
                        'review_date' => $signedAt->toDateString(),
                        'is_suitable' => true,
                        'unsuitability_reason' => null,
                        'treatment_consent' => true,
                        'notes' => null,
                        'is_active' => true,
                        'created_at' => $signedAt,
                        'updated_at' => $signedAt,
                    ]
                );
            }
        }
    }
}
