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
            ['name' => 'Sala Diagnóstico'],
            ['name' => 'Sala Faciales'],
            ['name' => 'Sala Maquinaria'],
        ];

        foreach ($rooms as $r) {
            Room::updateOrCreate(
                ['center_id' => $centerId, 'name' => $r['name']],
                [
                    'is_active' => true,
                    'grid_position' => null,
                ]
            );
        }
    }
}
