<?php

namespace Database\Factories;

use App\Enums\PortalStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class PortalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'destination_world' => $this->faker->word(),
            'energy_level' => $this->faker->numberBetween(20, 60),
            'stability' => $this->faker->randomFloat(2, 0.4, 0.9),
            'time_to_collapse' => $this->faker->numberBetween(30, 120),
            'creatures_count' => 0,
            'status' => PortalStatus::Active,
        ];
    }

    public function critical(): static
    {
        return $this->state(fn () => [
            'energy_level' => 95,
            'stability' => 0.1,
            'time_to_collapse' => 5,
        ]);
    }

    public function low(): static
    {
        return $this->state(fn () => [
            'energy_level' => 10,
            'stability' => 0.95,
            'time_to_collapse' => 90,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'status' => PortalStatus::Closed,
        ]);
    }

    public function withCreatures(int $count = 3): static
    {
        return $this->state(fn () => [
            'creatures_count' => $count,
        ]);
    }
}
