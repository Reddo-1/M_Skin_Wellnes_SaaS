<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    public function definition(): array
    {
        return [
            'center_id' => 1,
            'name' => 'Sala '.$this->faker->unique()->randomNumber(3),
            'grid_position' => ['x' => 0, 'y' => 0, 'w' => 4, 'h' => 3],
            'is_active' => true,
        ];
    }
}
