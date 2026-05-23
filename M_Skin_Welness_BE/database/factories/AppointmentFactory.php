<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        $startsAt = Carbon::parse($this->faker->dateTimeBetween('-1 week', '+2 weeks'));
        $duration = $this->faker->randomElement([30, 45, 60, 90]);

        $endsAt = $startsAt->copy()->addMinutes($duration);

        return [
            'center_id' => 1,
            'treatment_id' => 1,
            'room_id' => 1,
            'client_id' => 1,
            'worker_id' => 1,
            'machine_id' => null,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'booking_source' => $this->faker->randomElement(['staff', 'online']),
            'status_id' => (int) config('lookups.session_statuses.pendiente'),
            'reserved_price' => $this->faker->randomFloat(2, 20, 150),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    public function confirmed(): static
    {
        return $this->state(function () {
            return [
                'status_id' => (int) config('lookups.session_statuses.confirmada'),
            ];
        });
    }

    public function inProgress(): static
    {
        return $this->state(function () {
            return [
                'status_id' => (int) config('lookups.session_statuses.en_curso'),
            ];
        });
    }

    public function done(): static
    {
        return $this->state(function () {
            return [
                'status_id' => (int) config('lookups.session_statuses.realizada'),
                'actual_duration_minutes' => $this->faker->numberBetween(25, 95),
            ];
        });
    }

    public function cancelled(): static
    {
        return $this->state(function () {
            return [
                'status_id' => (int) config('lookups.session_statuses.cancelada'),
                'cancelled_at' => now(),
            ];
        });
    }
}
