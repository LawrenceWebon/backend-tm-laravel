<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'title' => $this->faker->sentence(4),
            'status' => $this->faker->randomElement(['pending', 'completed']),
            'date' => $this->faker->dateTimeBetween('now', '+30 days'),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high']),
            'order' => $this->faker->numberBetween(1, 100),
        ];
    }
}
