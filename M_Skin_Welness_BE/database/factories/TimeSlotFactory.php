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
            'break_start' => null,
            'break_end' => null,
            'is_active' => true,
        ];
    }

    //jornada continua con descanso interno (ej. 09-20 con pausa 14-16)
    public function withBreak(string $start = '14:00:00', string $end = '16:00:00'): static
    {
        return $this->state(function () use ($start, $end) {
            return [
                'start_time' => '09:00:00',
                'end_time' => '20:00:00',
                'break_start' => $start,
                'break_end' => $end,
            ];
        });
    }
}
