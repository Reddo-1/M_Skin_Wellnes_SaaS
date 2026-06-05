<?php

namespace Database\Seeders;

use App\Models\Center;
use App\Models\Machine;
use App\Models\Room;
use Illuminate\Database\Seeder;

class MachineSeeder extends Seeder
{
    public function run(): void
    {
        $centerId = Center::query()->where('slug', 'demo')->value('id');

        if ($centerId === null) {
            return;
        }

        $machineRoomId = Room::query()
            ->where('center_id', $centerId)
            ->where('name', 'Sala Maquinaria')
            ->value('id');

        $machines = [
            ['name' => 'Radiofrecuencia RF-100', 'is_mobile' => false, 'fixed_room_id' => $machineRoomId],
            ['name' => 'Láser diodo LD-200',     'is_mobile' => false, 'fixed_room_id' => $machineRoomId],
            ['name' => 'Presoterapia móvil',     'is_mobile' => true,  'fixed_room_id' => null],
        ];

        foreach ($machines as $m) {
            Machine::updateOrCreate(
                ['center_id' => $centerId, 'name' => $m['name']],
                [
                    'is_mobile' => $m['is_mobile'],
                    'fixed_room_id' => $m['fixed_room_id'],
                    'is_active' => true,
                ]
            );
        }
    }
}
