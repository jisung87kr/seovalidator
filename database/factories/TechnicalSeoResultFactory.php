<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TechnicalSeoResult>
 */
class TechnicalSeoResultFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $titleLength = $this->faker->numberBetween(20, 80);
        $descriptionLength = $this->faker->numberBetween(80, 200);
        
        return [
            'meta_tags' => [
                'title_length' => $titleLength,
                'description_length' => $descriptionLength,
                'og_tags' => [
                    'og:title' => $this->faker->boolean(80),
                    'og:description' => $this->faker->boolean(75),
                    'og:image' => $this->faker->boolean(60),
                    'og:url' => $this->faker->boolean(70),
                ],
            ],
            'page_speed' => $this->faker->numberBetween(10, 100),
            'mobile_friendly' => $this->faker->boolean(85),
            'ssl_enabled' => $this->faker->boolean(90),
            'security_headers' => [
                'X-Frame-Options' => $this->faker->boolean(70),
                'X-Content-Type-Options' => $this->faker->boolean(65),
                'X-XSS-Protection' => $this->faker->boolean(60),
                'Strict-Transport-Security' => $this->faker->boolean(55),
                'Content-Security-Policy' => $this->faker->boolean(40),
            ],
            'structured_data' => [
                'schema_org' => $this->faker->boolean(50),
                'json_ld' => $this->faker->boolean(45),
                'microdata' => $this->faker->boolean(30),
                'types' => $this->faker->randomElements([
                    'Organization', 'WebSite', 'Article', 'Person', 'Product', 'LocalBusiness'
                ], $this->faker->numberBetween(0, 3)),
            ],
        ];
    }
}
