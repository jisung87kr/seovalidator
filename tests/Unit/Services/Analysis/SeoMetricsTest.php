<?php

namespace Tests\Unit\Services\Analysis;

use Tests\TestCase;
use App\Services\Analysis\SeoMetrics;

class SeoMetricsTest extends TestCase
{
    private SeoMetrics $seoMetrics;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seoMetrics = new SeoMetrics();
    }

    public function test_get_primary_weights_returns_correct_structure()
    {
        // Act
        $weights = $this->seoMetrics->getPrimaryWeights();

        // Assert
        $this->assertIsArray($weights);
        $this->assertArrayHasKey('title', $weights);
        $this->assertArrayHasKey('meta_description', $weights);
        $this->assertArrayHasKey('headings', $weights);
        $this->assertArrayHasKey('content', $weights);
        $this->assertArrayHasKey('images', $weights);
        $this->assertArrayHasKey('links', $weights);
        $this->assertArrayHasKey('technical', $weights);
        $this->assertArrayHasKey('social_media', $weights);
        $this->assertArrayHasKey('structured_data', $weights);

        // Weights should sum to 100
        $this->assertEquals(100, array_sum($weights));
    }

    public function test_get_industry_weights_for_ecommerce()
    {
        // Act
        $weights = $this->seoMetrics->getIndustryWeights('e-commerce');

        // Assert
        $this->assertIsArray($weights);
        $this->assertGreaterThan(10, $weights['images']); // E-commerce should prioritize images
        $this->assertGreaterThan(5, $weights['structured_data']); // Product schema important
        $this->assertEquals(100, array_sum($weights)); // Should still sum to 100
    }

    public function test_get_industry_weights_for_blog()
    {
        // Act
        $weights = $this->seoMetrics->getIndustryWeights('blog');

        // Assert
        $this->assertIsArray($weights);
        $this->assertGreaterThan(25, $weights['content']); // Blogs prioritize content
        $this->assertGreaterThan(15, $weights['headings']); // Structure important for blogs
        $this->assertEquals(100, array_sum($weights));
    }

    public function test_get_industry_weights_returns_primary_for_unknown_industry()
    {
        // Act
        $weights = $this->seoMetrics->getIndustryWeights('unknown-industry');

        // Assert
        $this->assertEquals($this->seoMetrics->getPrimaryWeights(), $weights);
    }

    public function test_get_thresholds_returns_correct_data()
    {
        // Act
        $titleThresholds = $this->seoMetrics->getThresholds('title');

        // Assert
        $this->assertIsArray($titleThresholds);
        $this->assertArrayHasKey('optimal_length_min', $titleThresholds);
        $this->assertArrayHasKey('optimal_length_max', $titleThresholds);
        $this->assertArrayHasKey('acceptable_length_min', $titleThresholds);
        $this->assertArrayHasKey('acceptable_length_max', $titleThresholds);
        $this->assertEquals(30, $titleThresholds['optimal_length_min']);
        $this->assertEquals(60, $titleThresholds['optimal_length_max']);
    }

    public function test_get_competitive_difficulty_multiplier()
    {
        // Test medium/medium (baseline)
        $multiplier = $this->seoMetrics->getCompetitiveDifficultyMultiplier('medium', 'medium');
        $this->assertEquals(1.0, $multiplier);

        // Test high difficulty
        $multiplier = $this->seoMetrics->getCompetitiveDifficultyMultiplier('high', 'medium');
        $this->assertGreaterThan(1.0, $multiplier);

        // Test very high difficulty and volume
        $multiplier = $this->seoMetrics->getCompetitiveDifficultyMultiplier('very_high', 'very_high');
        $this->assertGreaterThan(1.5, $multiplier);
    }

    public function test_get_freshness_factor()
    {
        // Test news content (high decay)
        $factor = $this->seoMetrics->getFreshnessFactor('news', 1);
        $this->assertLessThan(1.0, $factor);

        // Test evergreen content (low decay)
        $factor = $this->seoMetrics->getFreshnessFactor('evergreen', 12);
        $this->assertGreaterThan(0.9, $factor);

        // Test fresh content should be 1.0
        $factor = $this->seoMetrics->getFreshnessFactor('blog', 0);
        $this->assertEquals(1.0, $factor);
    }

    public function test_calculate_advanced_title_score_with_optimal_title()
    {
        // Arrange
        $titleData = [
            'title' => 'Perfect SEO Title Between 30 and 60 Characters',
            'title_length' => 45
        ];
        $context = [
            'target_keywords' => ['SEO', 'title'],
            'industry' => 'general'
        ];

        // Act
        $result = $this->seoMetrics->calculateAdvancedTitleScore($titleData, $context);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('max_score', $result);
        $this->assertArrayHasKey('weight', $result);
        $this->assertArrayHasKey('issues', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('metrics', $result);

        $this->assertGreaterThan(80, $result['score']); // Should score well
        $this->assertEquals(100, $result['max_score']);
        $this->assertEmpty($result['issues']); // Should have no issues
    }

    public function test_calculate_advanced_title_score_with_missing_title()
    {
        // Arrange
        $titleData = [
            'title' => '',
            'title_length' => 0
        ];

        // Act
        $result = $this->seoMetrics->calculateAdvancedTitleScore($titleData);

        // Assert
        $this->assertEquals(0, $result['score']);
        $this->assertContains('Missing title tag', $result['issues']);
        $this->assertContains('Add a descriptive title tag to your page', $result['recommendations']);
    }

    public function test_calculate_advanced_content_score_with_good_content()
    {
        // Arrange
        $contentData = [
            'word_count' => 800,
            'text_to_html_ratio' => 30,
            'reading_time_minutes' => 4,
            'text' => str_repeat('Quality content with keywords about SEO and optimization. ', 100)
        ];
        $context = [
            'target_keywords' => ['SEO', 'optimization'],
            'content_type' => 'blog'
        ];

        // Act
        $result = $this->seoMetrics->calculateAdvancedContentScore($contentData, $context);

        // Assert
        $this->assertIsArray($result);
        $this->assertGreaterThan(70, $result['score']); // Should score well for good content
        $this->assertArrayHasKey('metrics', $result);
        $this->assertEquals(800, $result['metrics']['word_count']);
    }

    public function test_calculate_advanced_content_score_with_short_content()
    {
        // Arrange
        $contentData = [
            'word_count' => 100,
            'text_to_html_ratio' => 10,
            'reading_time_minutes' => 1,
            'text' => 'Very short content that lacks depth and quality.'
        ];

        // Act
        $result = $this->seoMetrics->calculateAdvancedContentScore($contentData);

        // Assert
        $this->assertLessThan(50, $result['score']);
        $this->assertContains('Content too short for optimal SEO', $result['issues']);
        $this->assertContains('Expand content to at least 300 words', $result['recommendations'][0]);
    }

    public function test_calculate_technical_performance_score()
    {
        // Arrange
        $technicalData = [
            'ssl_required' => true,
            'mobile_friendly' => true,
            'schema_markup_present' => true
        ];
        $performanceData = [
            'load_time' => 2.0,
            'core_web_vitals' => [
                'lcp' => 2.0,
                'fid' => 50,
                'cls' => 0.05
            ]
        ];

        // Act
        $result = $this->seoMetrics->calculateTechnicalPerformanceScore($technicalData, $performanceData);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('score', $result);
        $this->assertGreaterThan(60, $result['score']); // Should score well for good technical setup
        $this->assertEmpty($result['issues']); // Should have no major issues
    }

    public function test_calculate_technical_performance_score_with_issues()
    {
        // Arrange
        $technicalData = [
            'ssl_required' => false,
            'mobile_friendly' => false,
            'schema_markup_present' => false
        ];
        $performanceData = [
            'load_time' => 8.0 // Very slow
        ];

        // Act
        $result = $this->seoMetrics->calculateTechnicalPerformanceScore($technicalData, $performanceData);

        // Assert
        $this->assertLessThan(50, $result['score']);
        $this->assertNotEmpty($result['issues']);
        $this->assertContains('Site not using HTTPS', $result['issues']);
        $this->assertContains('Site not mobile-friendly', $result['issues']);
    }

    public function test_length_score_calculation_with_industry_adjustments()
    {
        // Test e-commerce title (can be slightly longer)
        $titleData = [
            'title' => 'Premium Wireless Bluetooth Headphones - Best Quality Sound',
            'title_length' => 65
        ];
        $context = ['industry' => 'e-commerce'];

        $result = $this->seoMetrics->calculateAdvancedTitleScore($titleData, $context);

        // E-commerce titles can be slightly longer, so should not be heavily penalized
        $this->assertGreaterThan(60, $result['score']);
    }

    public function test_keyword_density_analysis()
    {
        // Arrange
        $contentData = [
            'word_count' => 200,
            'text' => 'This is content about SEO optimization. SEO is important for websites. Good SEO practices help with optimization.',
            'text_to_html_ratio' => 25,
            'reading_time_minutes' => 2
        ];
        $context = ['target_keywords' => ['SEO', 'optimization']];

        // Act
        $result = $this->seoMetrics->calculateAdvancedContentScore($contentData, $context);

        // Assert
        $this->assertArrayHasKey('keyword_density', $result['metrics']);
        $this->assertGreaterThan(0, $result['metrics']['keyword_density']);
    }

    public function test_content_age_freshness_impact()
    {
        // Arrange - Old news content
        $contentData = [
            'word_count' => 500,
            'text_to_html_ratio' => 25,
            'reading_time_minutes' => 3,
            'text' => str_repeat('News content about recent events. ', 50)
        ];
        $context = [
            'content_type' => 'news',
            'content_age_months' => 6 // 6 months old
        ];

        // Act
        $result = $this->seoMetrics->calculateAdvancedContentScore($contentData, $context);

        // Assert
        $this->assertArrayHasKey('freshness_factor', $result['metrics']);
        $this->assertLessThan(1.0, $result['metrics']['freshness_factor']);
        $this->assertContains('Content is aging', $result['recommendations'][0]);
    }
}