<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SeoAnalysis>
 */
class SeoAnalysisFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = $this->faker->randomElement(['pending', 'running', 'completed', 'failed']);
        
        return [
            'url' => $this->faker->url(),
            'status' => $status,
            'score' => $status === 'completed' ? $this->faker->numberBetween(0, 100) : null,
            'started_at' => $status !== 'pending' ? $this->faker->dateTimeBetween('-1 week') : null,
            'completed_at' => $status === 'completed' ? $this->faker->dateTimeBetween('-1 day') : null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'score' => null,
            'started_at' => null,
            'completed_at' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'score' => $this->faker->numberBetween(50, 100),
            'started_at' => $this->faker->dateTimeBetween('-1 week'),
            'completed_at' => $this->faker->dateTimeBetween('-1 day'),
        ]);
    }
}
