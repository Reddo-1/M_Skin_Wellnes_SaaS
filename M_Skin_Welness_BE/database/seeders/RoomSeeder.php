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

        $rooms = [
            ['name' => 'Sala Diagnóstico', 'grid_position' => ['x' => 0, 'y' => 0, 'w' => 4, 'h' => 3]],
            ['name' => 'Sala Faciales',    'grid_position' => ['x' => 4, 'y' => 0, 'w' => 4, 'h' => 3]],
            ['name' => 'Sala Maquinaria',  'grid_position' => ['x' => 0, 'y' => 3, 'w' => 8, 'h' => 4]],
        ];

        foreach ($rooms as $r) {
            Room::updateOrCreate(
                ['center_id' => $centerId, 'name' => $r['name']],
                [
                    'is_active' => true,
                    'grid_position' => $r['grid_position'],
                ]
            );
        }
    }
}
