<?php

namespace App\Services;

use App\Models\Machine;
use Illuminate\Support\Facades\DB;

class MachineService
{
    public function create(int $centerId, array $data): Machine
    {
        return Machine::create([...$data, 'center_id' => $centerId])->refresh();
    }

    public function update(Machine $machine, array $data): Machine
    {
        return DB::transaction(function () use ($machine, $data) {
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
