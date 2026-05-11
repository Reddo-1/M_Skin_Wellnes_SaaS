<?php

namespace App\Services;

use App\Models\Room;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RoomService
{
    public function create(int $centerId, array $data): Room
    {
        return DB::transaction(function () use ($centerId, $data) {
            return Room::create([
                'center_id' => $centerId,
                'name' => $data['name'],
                'grid_position' => $data['grid_position'],
                'is_active' => $data['is_active'] ?? true,
            ]);
        });
    }

    public function update(Room $room, array $data): Room
    {
        return DB::transaction(function () use ($room, $data) {
            $room->fill($data)->save();

            return $room;
        });
    }

    public function delete(Room $room): void
    {
        if ($room->appointments()->exists()) {
            throw ValidationException::withMessages([
                'room' => ['No se puede borrar la sala porque tiene citas asociadas. Desactívala en su lugar.'],
            ]);
        }

        DB::transaction(function () use ($room) {
            //Las maquinas con sala fija se quedan sin ella
            $room->delete();
        });
    }
}
