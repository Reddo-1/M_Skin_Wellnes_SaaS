<?php

namespace App\Services;

use App\Models\{Center, CenterFile, ClientConsent, Treatment, TreatmentConsent, User, UserFile};
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\{DB, Storage};

class ConsentWizardService
{
    public function __construct(private readonly UserFileService $userFiles)
    {
    }

    public function submit(int $centerId, int $actorId, array $data): ClientConsent
    {
        return DB::transaction(function () use ($centerId, $actorId, $data) {
            $clientId = (int) $data['user_id'];
            $client = User::query()->forCenter($centerId)->findOrFail($clientId);
            $reviewer = User::query()->forCenter($centerId)->findOrFail($actorId);
            $center = Center::query()->findOrFail($centerId);

            $signatureBytes = $this->decodeSignature((string) $data['signature_base64']);

            $signatureFile = $this->userFiles->storeBinary(
                centerId: $centerId,
                data: [
                    'user_id' => $clientId,
                    'category' => UserFile::CATEGORY_CONSENT_SIGNATURE,
                ],
                contents: $signatureBytes,
                extension: 'png',
            );

            //regla: una sola fila activa por (centro, cliente); al firmar uno nuevo desactivamos el vigente
            ClientConsent::query()
                ->forCenter($centerId)
                ->where('user_id', $clientId)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $clientConsent = ClientConsent::create([
                'center_id' => $centerId,
                'user_id' => $clientId,
                'reviewed_by_user_id' => $actorId,
                'clinical_photos_consent' => (bool) $data['rgpd']['clinical_photos_consent'],
                'marketing_data_consent' => (bool) $data['rgpd']['marketing_data_consent'],
                'commercial_images_consent' => (bool) $data['rgpd']['commercial_images_consent'],
                'signature_user_file_id' => $signatureFile->id,
                'signed_at' => now(),
                'notes' => $data['notes'] ?? null,
                'is_active' => true,
            ]);

            //necesitamos los nombres dentro del PDF y no podemos hacer N queries dentro del loop
            $treatmentIds = array_map(fn ($entry) => (int) $entry['treatment_id'], $data['treatments']);
            $treatments = Treatment::query()
                ->forCenter($centerId)
                ->whereIn('id', $treatmentIds)
                ->get()
                ->keyBy('id');

            $treatmentRows = [];

            foreach ($data['treatments'] as $entry) {
                $treatmentId = (int) $entry['treatment_id'];

                TreatmentConsent::query()
                    ->forCenter($centerId)
                    ->where('user_id', $clientId)
                    ->where('treatment_id', $treatmentId)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);

                TreatmentConsent::create([
                    'center_id' => $centerId,
                    'user_id' => $clientId,
                    'treatment_id' => $treatmentId,
                    'reviewed_by_user_id' => $actorId,
                    'review_date' => now()->toDateString(),
                    'is_suitable' => (bool) $entry['is_suitable'],
                    'unsuitability_reason' => $entry['unsuitability_reason'] ?? null,
                    'treatment_consent' => (bool) $entry['treatment_consent'],
                    'notes' => $entry['notes'] ?? null,
                    'is_active' => true,
                ]);

                $treatmentRows[] = [
                    'name' => $treatments[$treatmentId]->name ?? 'Tratamiento #'.$treatmentId,
                    'is_suitable' => (bool) $entry['is_suitable'],
                    'unsuitability_reason' => $entry['unsuitability_reason'] ?? null,
                    'treatment_consent' => (bool) $entry['treatment_consent'],
                    'notes' => $entry['notes'] ?? null,
                ];
            }

            $pdfBytes = Pdf::loadView('pdf.consent', [
                'center' => $center,
                'centerLogo' => $this->resolveCenterLogo($centerId),
                'client' => $client,
                'reviewer' => $reviewer,
                'signedAt' => $clientConsent->signed_at,
                'signatureBase64' => base64_encode($signatureBytes),
                'treatments' => $treatmentRows,
                'rgpd' => [
                    'clinical_photos_consent' => $clientConsent->clinical_photos_consent,
                    'marketing_data_consent' => $clientConsent->marketing_data_consent,
                    'commercial_images_consent' => $clientConsent->commercial_images_consent,
                ],
                'notes' => $clientConsent->notes,
            ])->output();

            $pdfFile = $this->userFiles->storeBinary(
                centerId: $centerId,
                data: [
                    'user_id' => $clientId,
                    'category' => UserFile::CATEGORY_CONSENT_PDF,
                    'notes' => 'Consentimiento firmado el '.$clientConsent->signed_at->format('d/m/Y H:i'),
                ],
                contents: $pdfBytes,
                extension: 'pdf',
            );

            $clientConsent->pdf_user_file_id = $pdfFile->id;
            $clientConsent->save();

            return $clientConsent->load(['client', 'reviewer', 'pdfFile']);
        });
    }

    //DomPDF no resuelve URLs firmadas; el logo debe ir embebido como dataURL base64 dentro del HTML
    private function resolveCenterLogo(int $centerId): ?string
    {
        $logo = CenterFile::query()
            ->forCenter($centerId)
            ->where('type', 'logo')
            ->latest('id')
            ->first();

        if ($logo === null) {
            return null;
        }

        $bytes = Storage::disk('local')->get($logo->path);

        if ($bytes === null) {
            return null;
        }

        return 'data:'.$logo->mime_type.';base64,'.base64_encode($bytes);
    }

    private function decodeSignature(string $dataUrl): string
    {
        //formato esperado: data:image/png;base64,iVBORw0KGgo...
        $commaPos = strpos($dataUrl, ',');
        $payload = $commaPos === false ? $dataUrl : substr($dataUrl, $commaPos + 1);

        $decoded = base64_decode($payload, true);

        if ($decoded === false) {
            abort(422, 'La firma no es una imagen valida.');
        }

        return $decoded;
    }
}
