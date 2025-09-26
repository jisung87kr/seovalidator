<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Webhook>
 */
class WebhookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'url' => $this->faker->url(),
            'events' => $this->faker->randomElements([
                'analysis.completed',
                'analysis.failed',
                'batch.completed',
                'batch.failed'
            ], $this->faker->numberBetween(1, 3)),
            'secret' => $this->faker->optional()->password(16, 32),
            'active' => $this->faker->boolean(80), // 80% chance of being active
            'total_deliveries' => $this->faker->numberBetween(0, 100),
            'successful_deliveries' => function (array $attributes) {
                return $this->faker->numberBetween(0, $attributes['total_deliveries']);
            },
            'failed_deliveries' => function (array $attributes) {
                return $attributes['total_deliveries'] - $attributes['successful_deliveries'];
            },
            'last_triggered_at' => $this->faker->optional()->dateTimeBetween('-1 month'),
            'last_delivery_at' => $this->faker->optional()->dateTimeBetween('-1 week'),
            'last_delivery_status_code' => $this->faker->optional()->randomElement([200, 201, 400, 404, 500]),
            'last_delivery_response_time_ms' => $this->faker->optional()->numberBetween(50, 5000),
            'last_delivery_success' => $this->faker->optional()->boolean(),
            'last_delivery_error_message' => $this->faker->optional()->sentence(),
        ];
    }
}
