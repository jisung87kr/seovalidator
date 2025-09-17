<?php

namespace Database\Seeders;

use App\Models\SeoAnalysis;
use App\Models\UrlAnalysis;
use App\Models\TechnicalSeoResult;
use App\Models\ContentSeoResult;
use App\Models\ApiKey;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SeoAnalysisSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create API Keys
        ApiKey::factory()->count(5)->create();

        // Create SEO Analyses with related data
        SeoAnalysis::factory()
            ->count(10)
            ->completed()
            ->create()
            ->each(function (SeoAnalysis $analysis) {
                // Create URL analysis
                UrlAnalysis::factory()->create([
                    'seo_analysis_id' => $analysis->id,
                    'url' => $analysis->url,
                ]);

                // Create technical SEO result
                TechnicalSeoResult::factory()->create([
                    'seo_analysis_id' => $analysis->id,
                ]);

                // Create content SEO result
                ContentSeoResult::factory()->create([
                    'seo_analysis_id' => $analysis->id,
                ]);
            });

        // Create some pending analyses
        SeoAnalysis::factory()
            ->count(5)
            ->pending()
            ->create()
            ->each(function (SeoAnalysis $analysis) {
                UrlAnalysis::factory()->create([
                    'seo_analysis_id' => $analysis->id,
                    'url' => $analysis->url,
                ]);
            });
    }
}
