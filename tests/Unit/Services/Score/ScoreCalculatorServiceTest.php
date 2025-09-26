<?php

namespace Tests\Unit\Services\Score;

use Tests\TestCase;
use App\Services\Score\ScoreCalculatorService;
use App\Services\Analysis\SeoMetrics;
use App\Services\Cache\AnalysisCache;
use App\Services\Analysis\RecommendationEngine;
use Mockery;

class ScoreCalculatorServiceTest extends TestCase
{
    private ScoreCalculatorService $scoreCalculator;
    private SeoMetrics $seoMetrics;
    private AnalysisCache $analysisCache;
    private RecommendationEngine $recommendationEngine;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->seoMetrics = Mockery::mock(SeoMetrics::class);
        $this->analysisCache = Mockery::mock(AnalysisCache::class);
        $this->recommendationEngine = Mockery::mock(RecommendationEngine::class);

        // Create service with mocked dependencies
        $this->scoreCalculator = new ScoreCalculatorService(
            $this->seoMetrics,
            $this->analysisCache,
            $this->recommendationEngine
        );

        // Setup default mock behavior
        $this->setupDefaultMocks();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function setupDefaultMocks(): void
    {
        // Default SeoMetrics behavior
        $this->seoMetrics->shouldReceive('getIndustryWeights')
            ->andReturn([
                'title' => 20,
                'meta_description' => 15,
                'headings' => 15,
                'content' => 20,
                'images' => 10,
                'links' => 8,
                'technical' => 7,
                'social_media' => 3,
                'structured_data' => 2
            ])
            ->byDefault();

        $this->seoMetrics->shouldReceive('getCompetitiveDifficultyMultiplier')
            ->andReturn(1.0)
            ->byDefault();

        // Default advanced scoring methods
        $this->seoMetrics->shouldReceive('calculateAdvancedTitleScore')
            ->andReturn([
                'score' => 85,
                'max_score' => 100,
                'weight' => 20,
                'issues' => [],
                'recommendations' => [],
                'metrics' => ['length' => 45, 'word_count' => 6]
            ])
            ->byDefault();

        $this->seoMetrics->shouldReceive('calculateAdvancedContentScore')
            ->andReturn([
                'score' => 80,
                'max_score' => 100,
                'weight' => 20,
                'issues' => [],
                'recommendations' => [],
                'metrics' => ['word_count' => 500, 'text_to_html_ratio' => 25]
            ])
            ->byDefault();

        $this->seoMetrics->shouldReceive('calculateTechnicalPerformanceScore')
            ->andReturn([
                'score' => 75,
                'max_score' => 100,
                'weight' => 7,
                'issues' => [],
                'recommendations' => [],
                'metrics' => ['ssl' => true, 'mobile_friendly' => true]
            ])
            ->byDefault();

        // Default cache behavior - no cached results
        $this->analysisCache->shouldReceive('getAnalysis')
            ->andReturn(null)
            ->byDefault();

        $this->analysisCache->shouldReceive('storeAnalysis')
            ->andReturn(true)
            ->byDefault();
    }

    public function test_calculate_returns_comprehensive_score_data()
    {
        // Arrange
        $parsedData = $this->getValidParsedData();

        // Act
        $result = $this->scoreCalculator->calculate($parsedData);

        // Assert - Basic structure
        $this->assertIsArray($result);
        $this->assertArrayHasKey('overall_score', $result);
        $this->assertArrayHasKey('grade', $result);
        $this->assertArrayHasKey('category_scores', $result);
        $this->assertArrayHasKey('breakdown', $result);
        $this->assertArrayHasKey('max_possible_score', $result);
        $this->assertArrayHasKey('scoring_version', $result);
        $this->assertArrayHasKey('calculated_at', $result);

        // Assert - New advanced fields
        $this->assertArrayHasKey('scoring_method', $result);
        $this->assertArrayHasKey('industry', $result);
        $this->assertArrayHasKey('weights_used', $result);
        $this->assertArrayHasKey('performance_metrics', $result);
        $this->assertArrayHasKey('competitive_factors', $result);

        // Assert - Score validity
        $this->assertIsInt($result['overall_score']);
        $this->assertGreaterThanOrEqual(0, $result['overall_score']);
        $this->assertLessThanOrEqual(100, $result['overall_score']);
        $this->assertEquals(100, $result['max_possible_score']);
        $this->assertContains($result['grade'], ['A', 'B', 'C', 'D', 'F']);

        // Assert - Advanced scoring version
        $this->assertEquals('2.0.0', $result['scoring_version']);
        $this->assertEquals('advanced_weighted_algorithm', $result['scoring_method']);
    }

    public function test_calculate_with_industry_context()
    {
        // Arrange
        $parsedData = $this->getValidParsedData();
        $context = ['industry' => 'e-commerce'];

        $ecommerceWeights = [
            'title' => 20,
            'meta_description' => 15,
            'headings' => 15,
            'content' => 15,
            'images' => 15,      // Higher for e-commerce
            'links' => 8,
            'technical' => 12,    // Higher for e-commerce
            'social_media' => 3,
            'structured_data' => 8 // Higher for e-commerce
        ];

        $this->seoMetrics->shouldReceive('getIndustryWeights')
            ->with('e-commerce')
            ->andReturn($ecommerceWeights)
            ->atLeast()->once();

        // Act
        $result = $this->scoreCalculator->calculate($parsedData, $context);

        // Assert
        $this->assertEquals('e-commerce', $result['industry']);
        $this->assertArrayHasKey('images', $result['weights_used']);
        $this->assertEquals(15, $result['weights_used']['images']); // E-commerce should have higher image weight
    }

    public function test_calculate_with_competitive_factors()
    {
        // Arrange
        $parsedData = $this->getValidParsedData();
        $context = [
            'keyword_difficulty' => 'high',
            'search_volume' => 'very_high',
            'competitor_count' => 50
        ];

        $this->seoMetrics->shouldReceive('getCompetitiveDifficultyMultiplier')
            ->with('high', 'very_high')
            ->andReturn(1.3)
            ->atLeast()->once();

        // Act
        $result = $this->scoreCalculator->calculate($parsedData, $context);

        // Assert
        $this->assertArrayHasKey('competitive_factors', $result);
        $competitiveFactors = $result['competitive_factors'];

        $this->assertEquals('high', $competitiveFactors['keyword_difficulty']);
        $this->assertEquals('very_high', $competitiveFactors['search_volume']);
        $this->assertEquals(50, $competitiveFactors['competitor_count']);
        $this->assertEquals(1.3, $competitiveFactors['difficulty_multiplier']);
    }

    public function test_calculate_with_caching_enabled()
    {
        // Arrange
        $parsedData = $this->getValidParsedData();
        $context = ['url' => 'https://example.com'];

        // Mock cache hit
        $cachedResult = ['cached' => true, 'overall_score' => 85];
        $this->analysisCache->shouldReceive('getAnalysis')
            ->with('https://example.com', ['type' => 'score_calculation'])
            ->andReturn($cachedResult)
            ->once();

        // Act
        $result = $this->scoreCalculator->calculate($parsedData, $context);

        // Assert
        $this->assertEquals($cachedResult, $result);
    }

    public function test_calculate_with_caching_disabled()
    {
        // Arrange
        $parsedData = $this->getValidParsedData();
        $context = ['disable_cache' => true];

        // Cache should not be called
        $this->analysisCache->shouldNotReceive('getAnalysis');

        // Act
        $result = $this->scoreCalculator->calculate($parsedData, $context);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('overall_score', $result);
    }

    public function test_calculate_performance_metrics()
    {
        // Arrange
        $parsedData = $this->getValidParsedData();

        // Act
        $result = $this->scoreCalculator->calculate($parsedData);

        // Assert
        $this->assertArrayHasKey('performance_metrics', $result);
        $metrics = $result['performance_metrics'];

        $this->assertArrayHasKey('calculation_time_ms', $metrics);
        $this->assertArrayHasKey('calculation_time_human', $metrics);
        $this->assertArrayHasKey('memory_usage_mb', $metrics);
        $this->assertArrayHasKey('peak_memory_mb', $metrics);
        $this->assertArrayHasKey('timestamp', $metrics);

        $this->assertIsFloat($metrics['calculation_time_ms']);
        $this->assertGreaterThan(0, $metrics['calculation_time_ms']);
    }

    public function test_calculate_scores_perfect_title()
    {
        // Arrange
        $parsedData = [
            'meta' => [
                'title' => 'Perfect SEO Title Between 30 and 60 Characters Long',
                'title_length' => 50,
                'description' => 'Great meta description that is between 120 and 160 characters long and provides good information about the page content.',
                'description_length' => 130
            ],
            'headings' => ['h1' => [['text' => 'Great H1 Heading', 'length' => 16]]],
            'content' => ['word_count' => 800, 'text_to_html_ratio' => 30],
            'images' => ['total_count' => 5, 'without_alt_count' => 0],
            'links' => ['total_count' => 10, 'internal_count' => 7, 'external_count' => 3, 'empty_anchor_count' => 0],
            'technical' => [
                'doctype' => '<!DOCTYPE html>',
                'lang_attribute' => 'en',
                'ssl_required' => true,
                'schema_markup_present' => true,
                'open_graph_present' => true,
                'inline_styles_count' => 0,
                'inline_scripts_count' => 0
            ],
            'social_media' => [
                'open_graph' => ['title' => 'OG Title', 'description' => 'OG Desc', 'image' => 'image.jpg', 'url' => 'url'],
                'twitter_cards' => ['card' => 'summary', 'title' => 'Twitter Title', 'description' => 'Twitter Desc']
            ],
            'structured_data' => [
                'json_ld' => ['Article' => ['@type' => 'Article']],
                'microdata' => [],
                'rdfa' => []
            ]
        ];

        // Act
        $result = $this->scoreCalculator->calculate($parsedData);

        // Assert - Should get high scores for all categories
        $this->assertGreaterThanOrEqual(80, $result['overall_score']);
        $this->assertContains($result['grade'], ['A', 'B']);

        // Check category scores
        $titleScore = $result['category_scores']['title'];
        $this->assertGreaterThanOrEqual(80, $titleScore['score']);
        $this->assertEquals(20, $titleScore['weight']);
    }

    public function test_calculate_penalizes_missing_title()
    {
        // Arrange
        $parsedData = $this->getBasicParsedData();
        $parsedData['meta']['title'] = '';
        $parsedData['meta']['title_length'] = 0;

        // Mock for missing title scenario
        $this->seoMetrics->shouldReceive('calculateAdvancedTitleScore')
            ->with(['title' => '', 'title_length' => 0], Mockery::any())
            ->andReturn([
                'score' => 0,
                'max_score' => 100,
                'weight' => 20,
                'issues' => ['Missing title tag'],
                'recommendations' => ['Add a descriptive title tag to your page'],
                'metrics' => ['length' => 0, 'has_title' => false]
            ]);

        // Act
        $result = $this->scoreCalculator->calculate($parsedData);

        // Assert
        $titleScore = $result['category_scores']['title'];
        $this->assertEquals(0, $titleScore['score']);
        $this->assertContains('Missing title tag', $titleScore['issues']);
        $this->assertContains('Add a descriptive title tag to your page', $titleScore['recommendations']);
    }

    public function test_calculate_penalizes_title_too_short()
    {
        // Arrange
        $parsedData = $this->getBasicParsedData();
        $parsedData['meta']['title'] = 'Short';
        $parsedData['meta']['title_length'] = 5;

        // Act
        $result = $this->scoreCalculator->calculate($parsedData);

        // Assert
        $titleScore = $result['category_scores']['title'];
        $this->assertLessThan(70, $titleScore['score']);
        $this->assertContains('Title too short', $titleScore['issues']);
    }

    public function test_calculate_penalizes_title_too_long()
    {
        // Arrange
        $parsedData = $this->getBasicParsedData();
        $parsedData['meta']['title'] = str_repeat('Very long title ', 10);
        $parsedData['meta']['title_length'] = 150;

        // Act
        $result = $this->scoreCalculator->calculate($parsedData);

        // Assert
        $titleScore = $result['category_scores']['title'];
        $this->assertContains('Title too long', $titleScore['issues']);
    }

    public function test_calculate_penalizes_missing_meta_description()
    {
        // Arrange
        $parsedData = $this->getBasicParsedData();
        $parsedData['meta']['description'] = '';
        $parsedData['meta']['description_length'] = 0;

        // Act
        $result = $this->scoreCalculator->calculate($parsedData);

        // Assert
        $metaScore = $result['category_scores']['meta_description'];
        $this->assertEquals(0, $metaScore['score']);
        $this->assertContains('Missing meta description', $metaScore['issues']);
    }

    public function test_calculate_scores_heading_structure()
    {
        // Test missing H1
        $parsedData = $this->getBasicParsedData();
        $parsedData['headings'] = ['h1' => [], 'h2' => [['text' => 'H2']]];

        $result = $this->scoreCalculator->calculate($parsedData);
        $headingScore = $result['category_scores']['headings'];

        $this->assertContains('Missing H1 tag', $headingScore['issues']);

        // Test multiple H1s
        $parsedData['headings'] = ['h1' => [['text' => 'H1 One'], ['text' => 'H1 Two']]];

        $result = $this->scoreCalculator->calculate($parsedData);
        $headingScore = $result['category_scores']['headings'];

        $this->assertContains('Multiple H1 tags found', $headingScore['issues']);
    }

    public function test_calculate_scores_content_quality()
    {
        // Test short content
        $parsedData = $this->getBasicParsedData();
        $parsedData['content'] = ['word_count' => 50, 'text_to_html_ratio' => 10];

        $result = $this->scoreCalculator->calculate($parsedData);
        $contentScore = $result['category_scores']['content'];

        $this->assertLessThan(50, $contentScore['score']);
        $this->assertContains('Content too short', $contentScore['issues']);

        // Test good content
        $parsedData['content'] = ['word_count' => 800, 'text_to_html_ratio' => 30, 'paragraphs' => 5];

        $result = $this->scoreCalculator->calculate($parsedData);
        $contentScore = $result['category_scores']['content'];

        $this->assertGreaterThan(70, $contentScore['score']);
    }

    public function test_calculate_scores_image_optimization()
    {
        // Test images without alt text
        $parsedData = $this->getBasicParsedData();
        $parsedData['images'] = [
            'total_count' => 5,
            'without_alt_count' => 3,
            'without_title_count' => 5
        ];

        $result = $this->scoreCalculator->calculate($parsedData);
        $imageScore = $result['category_scores']['images'];

        $this->assertLessThan(100, $imageScore['score']);
        $this->assertContains('3 images missing alt text', $imageScore['issues']);

        // Test perfect images
        $parsedData['images'] = [
            'total_count' => 3,
            'without_alt_count' => 0,
            'without_title_count' => 0
        ];

        $result = $this->scoreCalculator->calculate($parsedData);
        $imageScore = $result['category_scores']['images'];

        $this->assertEquals(100, $imageScore['score']);
    }

    public function test_calculate_scores_link_structure()
    {
        // Test good link structure
        $parsedData = $this->getBasicParsedData();
        $parsedData['links'] = [
            'total_count' => 10,
            'internal_count' => 7,
            'external_count' => 3,
            'empty_anchor_count' => 0
        ];

        $result = $this->scoreCalculator->calculate($parsedData);
        $linkScore = $result['category_scores']['links'];

        $this->assertGreaterThan(70, $linkScore['score']);

        // Test no links
        $parsedData['links'] = [
            'total_count' => 0,
            'internal_count' => 0,
            'external_count' => 0,
            'empty_anchor_count' => 0
        ];

        $result = $this->scoreCalculator->calculate($parsedData);
        $linkScore = $result['category_scores']['links'];

        $this->assertEquals(0, $linkScore['score']);
        $this->assertContains('No links found', $linkScore['issues']);
    }

    public function test_calculate_scores_technical_seo()
    {
        // Test perfect technical setup
        $parsedData = $this->getBasicParsedData();
        $parsedData['technical'] = [
            'doctype' => '<!DOCTYPE html>',
            'lang_attribute' => 'en',
            'ssl_required' => true,
            'schema_markup_present' => true,
            'open_graph_present' => true,
            'inline_styles_count' => 0,
            'inline_scripts_count' => 0
        ];

        $result = $this->scoreCalculator->calculate($parsedData);
        $techScore = $result['category_scores']['technical'];

        $this->assertGreaterThan(80, $techScore['score']);

        // Test missing elements
        $parsedData['technical'] = [
            'doctype' => '',
            'lang_attribute' => '',
            'ssl_required' => false,
            'schema_markup_present' => false,
            'open_graph_present' => false,
            'inline_styles_count' => 5,
            'inline_scripts_count' => 3
        ];

        $result = $this->scoreCalculator->calculate($parsedData);
        $techScore = $result['category_scores']['technical'];

        $this->assertLessThan(50, $techScore['score']);
        $this->assertContains('Missing or invalid DOCTYPE', $techScore['issues']);
    }

    public function test_calculate_assigns_correct_grades()
    {
        // Test A grade
        $result = ['overall_score' => 95];
        $grade = $this->invokePrivateMethod('calculateGrade', [$result['overall_score']]);
        $this->assertEquals('A', $grade);

        // Test B grade
        $result = ['overall_score' => 85];
        $grade = $this->invokePrivateMethod('calculateGrade', [$result['overall_score']]);
        $this->assertEquals('B', $grade);

        // Test F grade
        $result = ['overall_score' => 45];
        $grade = $this->invokePrivateMethod('calculateGrade', [$result['overall_score']]);
        $this->assertEquals('F', $grade);
    }

    public function test_calculate_generates_breakdown()
    {
        // Arrange
        $parsedData = $this->getValidParsedData();

        // Act
        $result = $this->scoreCalculator->calculate($parsedData);

        // Assert
        $this->assertArrayHasKey('breakdown', $result);

        foreach (['title', 'meta_description', 'headings', 'content'] as $category) {
            $this->assertArrayHasKey($category, $result['breakdown']);
            $this->assertArrayHasKey('score', $result['breakdown'][$category]);
            $this->assertArrayHasKey('weight_percentage', $result['breakdown'][$category]);
            $this->assertArrayHasKey('contribution_to_overall', $result['breakdown'][$category]);
            $this->assertArrayHasKey('status', $result['breakdown'][$category]);
        }
    }

    public function test_calculate_throws_exception_on_invalid_data()
    {
        $this->expectException(\Exception::class);

        // Pass invalid data that should cause an error
        $this->scoreCalculator->calculate([]);
    }

    private function getValidParsedData(): array
    {
        return [
            'meta' => [
                'title' => 'Good SEO Title for Testing Purposes',
                'title_length' => 38,
                'description' => 'This is a well-crafted meta description that provides good information about the page and is within the optimal length range.',
                'description_length' => 130
            ],
            'headings' => [
                'h1' => [['text' => 'Main Heading', 'length' => 12]],
                'h2' => [['text' => 'Subheading One', 'length' => 14], ['text' => 'Subheading Two', 'length' => 14]]
            ],
            'content' => [
                'word_count' => 500,
                'text_to_html_ratio' => 25,
                'reading_time_minutes' => 3,
                'paragraphs' => 4
            ],
            'images' => [
                'total_count' => 3,
                'without_alt_count' => 0,
                'without_title_count' => 1
            ],
            'links' => [
                'total_count' => 8,
                'internal_count' => 5,
                'external_count' => 3,
                'empty_anchor_count' => 0
            ],
            'technical' => [
                'doctype' => '<!DOCTYPE html>',
                'lang_attribute' => 'en',
                'ssl_required' => true,
                'schema_markup_present' => false,
                'open_graph_present' => true,
                'inline_styles_count' => 1,
                'inline_scripts_count' => 0
            ],
            'social_media' => [
                'open_graph' => [
                    'title' => 'OG Title',
                    'description' => 'OG Description',
                    'image' => 'og-image.jpg'
                ],
                'twitter_cards' => [
                    'card' => 'summary',
                    'title' => 'Twitter Title'
                ]
            ],
            'structured_data' => [
                'json_ld' => [],
                'microdata' => [],
                'rdfa' => []
            ]
        ];
    }

    private function getBasicParsedData(): array
    {
        return [
            'meta' => [
                'title' => 'Test Title',
                'title_length' => 10,
                'description' => 'Test description',
                'description_length' => 16
            ],
            'headings' => [
                'h1' => [['text' => 'H1 Heading', 'length' => 10]]
            ],
            'content' => [
                'word_count' => 200,
                'text_to_html_ratio' => 20,
                'paragraphs' => 2
            ],
            'images' => [
                'total_count' => 2,
                'without_alt_count' => 1
            ],
            'links' => [
                'total_count' => 5,
                'internal_count' => 3,
                'external_count' => 2,
                'empty_anchor_count' => 0
            ],
            'technical' => [
                'doctype' => '<!DOCTYPE html>',
                'lang_attribute' => 'en',
                'ssl_required' => true
            ],
            'social_media' => [
                'open_graph' => [],
                'twitter_cards' => []
            ],
            'structured_data' => [
                'json_ld' => [],
                'microdata' => [],
                'rdfa' => []
            ]
        ];
    }

    private function invokePrivateMethod(string $methodName, array $args = [])
    {
        $reflection = new \ReflectionClass($this->scoreCalculator);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($this->scoreCalculator, $args);
    }
}