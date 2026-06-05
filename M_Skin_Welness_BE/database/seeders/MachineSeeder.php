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

        $cabina1Id = Room::query()
            ->where('center_id', $centerId)
            ->where('name', 'Cabina 1')
            ->value('id');

        $machines = [
            ['name' => 'Indiba', 'is_mobile' => true,  'fixed_room_id' => null],
            ['name' => 'Láser',  'is_mobile' => false, 'fixed_room_id' => $cabina1Id],
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
