<?php

namespace App\Services;

use App\Models\Machine;
use Illuminate\Support\Facades\DB;

class MachineService
{
    public function create(int $centerId, array $data): Machine
    {
        return DB::transaction(function () use ($centerId, $data) {
            $isMobile = $data['is_mobile'] ?? false;

            return Machine::create([
                'center_id' => $centerId,
                'name' => $data['name'],
                'is_mobile' => $isMobile,
                'fixed_room_id' => $isMobile ? null : ($data['fixed_room_id'] ?? null),
                'is_active' => $data['is_active'] ?? true,
            ]);
        });
    }

    public function update(Machine $machine, array $data): Machine
    {
        return DB::transaction(function () use ($machine, $data) {
            //si pasa a móvil, se borra la sala fija
            if (array_key_exists('is_mobile', $data) && $data['is_mobile'] === true) {
                $data['fixed_room_id'] = null;
            }

            $machine->fill($data)->save();

            return $machine;
        });
    }

    public function delete(Machine $machine): void
    {
        DB::transaction(function () use ($machine) {
            $machine->delete();
        });
    }
}
