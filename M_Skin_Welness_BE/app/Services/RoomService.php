<?php

namespace App\Services;

use App\Models\Room;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RoomService
{
    public function create(int $centerId, array $data): Room
    {
        return Room::create([...$data, 'center_id' => $centerId])->refresh();
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

        $room->delete();
    }
}
