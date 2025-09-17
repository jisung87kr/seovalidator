<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ContentSeoResult>
 */
class ContentSeoResultFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $internalLinksCount = $this->faker->numberBetween(0, 15);
        $externalLinksCount = $this->faker->numberBetween(0, 10);
        $totalImages = $this->faker->numberBetween(0, 20);
        $imagesWithAlt = $this->faker->numberBetween(0, $totalImages);
        
        // Generate keyword density data
        $keywords = [
            $this->faker->word() => $this->faker->randomFloat(2, 0.5, 5.0),
            $this->faker->word() => $this->faker->randomFloat(2, 0.2, 3.0),
            $this->faker->word() => $this->faker->randomFloat(2, 0.1, 2.0),
        ];
        
        // Generate internal links
        $internalLinks = [];
        for ($i = 0; $i < $internalLinksCount; $i++) {
            $internalLinks[] = $this->faker->url();
        }
        
        // Generate external links
        $externalLinks = [];
        for ($i = 0; $i < $externalLinksCount; $i++) {
            $externalLinks[] = $this->faker->url();
        }
        
        return [
            'keyword_density' => $keywords,
            'readability_score' => $this->faker->numberBetween(0, 100),
            'h_tags' => [
                'h1' => $this->faker->numberBetween(0, 3),
                'h2' => $this->faker->numberBetween(0, 8),
                'h3' => $this->faker->numberBetween(0, 15),
                'h4' => $this->faker->numberBetween(0, 10),
                'h5' => $this->faker->numberBetween(0, 5),
                'h6' => $this->faker->numberBetween(0, 3),
            ],
            'word_count' => $this->faker->numberBetween(100, 3000),
            'internal_links' => $internalLinks,
            'external_links' => $externalLinks,
            'image_analysis' => [
                'total' => $totalImages,
                'with_alt' => $imagesWithAlt,
                'without_alt' => $totalImages - $imagesWithAlt,
            ],
        ];
    }
}
