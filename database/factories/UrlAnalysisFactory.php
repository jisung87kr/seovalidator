<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UrlAnalysis>
 */
class UrlAnalysisFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'url' => $this->faker->url(),
            'title' => $this->faker->sentence($this->faker->numberBetween(3, 8)),
            'meta_description' => $this->faker->realText($this->faker->numberBetween(100, 160)),
            'status_code' => $this->faker->randomElement([200, 200, 200, 200, 301, 302, 404, 403, 500]),
        ];
    }
}
