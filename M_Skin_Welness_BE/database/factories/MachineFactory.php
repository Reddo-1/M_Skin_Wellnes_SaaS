<?php

namespace Database\Factories;

use App\Models\Machine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Machine>
 */
class MachineFactory extends Factory
{
    public function definition(): array
    {
        return [
            'center_id' => 1,
            'name' => 'Máquina '.$this->faker->unique()->randomNumber(3),
            'is_mobile' => false,
            'fixed_room_id' => null,
            'is_active' => true,
        ];
    }

    public function mobile(): static
    {
        return $this->state(function () {
            return [
                'is_mobile' => true,
                'fixed_room_id' => null,
            ];
        });
    }
}
