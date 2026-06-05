<?php

namespace Database\Seeders;

use App\Models\Center;
use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $centerId = Center::query()->where('slug', 'demo')->value('id');

        if ($centerId === null) {
            return;
        }

        $rooms = ['Cabina 1', 'Cabina 2', 'Cabina 3'];

        foreach ($rooms as $name) {
            Room::updateOrCreate(
                ['center_id' => $centerId, 'name' => $name],
                [
                    'is_active' => true,
                    'grid_position' => null,
                ]
            );
        }
    }
}
