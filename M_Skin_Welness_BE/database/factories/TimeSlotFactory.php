<?php

namespace Database\Factories;

use App\Models\TimeSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimeSlot>
 */
class TimeSlotFactory extends Factory
{
    public function definition(): array
    {
        return [
            'center_id' => 1,
            'name' => 'Franja '.$this->faker->unique()->randomNumber(3),
            'start_time' => '09:00:00',
            'end_time' => '14:00:00',
            'is_active' => true,
        ];
    }
}
