<?php

namespace Database\Factories;

use App\Models\Treatment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Treatment>
 */
class TreatmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'center_id' => 1,
            'name' => $this->faker->unique()->words(2, true),
            'duration_minutes' => $this->faker->numberBetween(30, 120),
            'price' => $this->faker->randomFloat(2, 20, 200),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(function () {
            return ['is_active' => false];
        });
    }
}
