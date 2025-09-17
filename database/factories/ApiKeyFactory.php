<?php

namespace Database\Factories;

use App\Models\ApiKey;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApiKey>
 */
class ApiKeyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => ApiKey::generateKey(),
            'name' => $this->faker->company() . ' API Key',
            'rate_limit' => $this->faker->randomElement([100, 1000, 5000, 10000]),
            'expires_at' => $this->faker->boolean(70) ? null : $this->faker->dateTimeBetween('now', '+2 years'),
            'is_active' => $this->faker->boolean(90),
        ];
    }
}
