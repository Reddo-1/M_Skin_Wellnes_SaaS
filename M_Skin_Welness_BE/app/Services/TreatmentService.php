<?php

namespace App\Services;

use App\Models\Treatment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TreatmentService
{
    //Vincula las máquinas compatibles con el tratamiento
    private function syncMachines(Treatment $treatment, int $centerId, array $data): void
    {
        if (! array_key_exists('machine_ids', $data)) {
            return;
        }

        $pivotData = [];
        foreach ($data['machine_ids'] ?? [] as $id) {
            $pivotData[$id] = ['center_id' => $centerId];
        }
        $treatment->machines()->sync($pivotData);
    }

    //Vincula los roles autorizados a ejecutar el tratamiento
    private function syncAuthorizedRoles(Treatment $treatment, int $centerId, array $data): void
    {
        if (! array_key_exists('role_ids', $data)) {
            return;
        }

        $pivotData = [];
        foreach ($data['role_ids'] ?? [] as $id) {
            $pivotData[$id] = ['center_id' => $centerId];
        }
        $treatment->authorizedRoles()->sync($pivotData);
    }
    public function create(int $centerId, array $data): Treatment
    {
        return DB::transaction(function () use ($centerId, $data) {
            $treatment = Treatment::create([...$data, 'center_id' => $centerId]);

            //sincronizamos máquinas y roles autorizados si llegan en el body
            $this->syncMachines($treatment, $centerId, $data);
            $this->syncAuthorizedRoles($treatment, $centerId, $data);

            return $treatment->load(['machines', 'authorizedRoles']);
        });
    }

    public function update(Treatment $treatment, array $data): Treatment
    {
        return DB::transaction(function () use ($treatment, $data) {
            $treatment->fill($data)->save();

            $this->syncMachines($treatment, $treatment->center_id, $data);
            $this->syncAuthorizedRoles($treatment, $treatment->center_id, $data);

            return $treatment->load(['machines', 'authorizedRoles']);
        });
    }

    public function delete(Treatment $treatment): void
    {
        if ($treatment->appointments()->exists()) {
            throw ValidationException::withMessages([
                'treatment' => ['No se puede borrar el tratamiento porque tiene citas asociadas. Desactívalo en su lugar.'],
            ]);
        }

        if ($treatment->treatmentConsents()->exists()) {
            throw ValidationException::withMessages([
                'treatment' => ['No se puede eliminar un tratamiento que está ligado a consentimientos. Desactívelo en su lugar.'],
            ]);
        }

        DB::transaction(function () use ($treatment) {
            $treatment->machines()->detach();
            $treatment->authorizedRoles()->detach();
            $treatment->delete();
        });
    }
}
