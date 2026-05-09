<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\SessionStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Appointment>
 */
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
            'booking_source' => $this->faker->randomElement(Appointment::SOURCES),
            'status_id' => SessionStatus::idFor('pendiente'),
            'reserved_price' => $this->faker->randomFloat(2, 20, 150),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    public function confirmed(): static
    {
        return $this->state(function () {
            return [
                'status_id' => SessionStatus::idFor('confirmada'),
            ];
        });
    }

    public function inProgress(): static
    {
        return $this->state(function () {
            return [
                'status_id' => SessionStatus::idFor('en_curso'),
            ];
        });
    }

    public function done(): static
    {
        return $this->state(function () {
            return [
                'status_id' => SessionStatus::idFor('realizada'),
                'actual_duration_minutes' => $this->faker->numberBetween(25, 95),
            ];
        });
    }

    public function cancelled(): static
    {
        return $this->state(function () {
            return [
                'status_id' => SessionStatus::idFor('cancelada'),
                'cancelled_at' => now(),
            ];
        });
    }
}
