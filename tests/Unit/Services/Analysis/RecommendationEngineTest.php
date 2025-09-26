<?php

namespace Tests\Unit\Services\Analysis;

use Tests\TestCase;
use App\Services\Analysis\RecommendationEngine;
use App\Services\Analysis\SeoMetrics;
use Mockery;

class RecommendationEngineTest extends TestCase
{
    private RecommendationEngine $recommendationEngine;
    private SeoMetrics $seoMetrics;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seoMetrics = Mockery::mock(SeoMetrics::class);
        $this->recommendationEngine = new RecommendationEngine($this->seoMetrics);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_generate_recommendations_returns_comprehensive_structure()
    {
        // Arrange
        $analysisResults = $this->getBasicAnalysisResults();
        $context = ['industry' => 'e-commerce'];

        // Act
        $result = $this->recommendationEngine->generateRecommendations($analysisResults, $context);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('grouped_recommendations', $result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('quick_wins', $result);
        $this->assertArrayHasKey('long_term_goals', $result);
        $this->assertArrayHasKey('generated_at', $result);
        $this->assertArrayHasKey('total_recommendations', $result);

        $this->assertIsArray($result['recommendations']);
        $this->assertIsArray($result['grouped_recommendations']);
    }

    public function test_generate_recommendations_for_missing_title()
    {
        // Arrange
        $analysisResults = [
            'overall_score' => 45,
            'category_scores' => [
                'title' => [
                    'score' => 0,
                    'issues' => ['Missing title tag'],
                    'recommendations' => ['Add a descriptive title tag'],
                    'metrics' => ['length' => 0, 'has_title' => false]
                ]
            ]
        ];

        // Act
        $result = $this->recommendationEngine->generateRecommendations($analysisResults);

        // Assert
        $recommendations = $result['recommendations'];
        $this->assertNotEmpty($recommendations);

        // Find title-related recommendation
        $titleRec = collect($recommendations)->first(function ($rec) {
            return str_contains($rec['title'], 'Title');
        });

        $this->assertNotNull($titleRec);
        $this->assertEquals('critical', $titleRec['priority']);
        $this->assertEquals('on_page', $titleRec['category']);
        $this->assertGreaterThan(50, $titleRec['impact_score']); // High impact
    }

    public function test_generate_recommendations_for_short_content()
    {
        // Arrange
        $analysisResults = [
            'overall_score' => 55,
            'category_scores' => [
                'content' => [
                    'score' => 30,
                    'issues' => ['Content too short'],
                    'recommendations' => ['Expand content to 300+ words'],
                    'metrics' => ['word_count' => 150]
                ]
            ]
        ];

        // Act
        $result = $this->recommendationEngine->generateRecommendations($analysisResults);

        // Assert
        $recommendations = $result['recommendations'];
        $contentRec = collect($recommendations)->first(function ($rec) {
            return str_contains($rec['title'], 'Content');
        });

        $this->assertNotNull($contentRec);
        $this->assertEquals('high', $contentRec['priority']);
        $this->assertEquals('content', $contentRec['category']);
        $this->assertArrayHasKey('estimated_time_minutes', $contentRec);
        $this->assertGreaterThan(60, $contentRec['estimated_time_minutes']); // Should be time-consuming
    }

    public function test_generate_recommendations_for_missing_alt_text()
    {
        // Arrange
        $analysisResults = [
            'overall_score' => 70,
            'category_scores' => [
                'images' => [
                    'score' => 60,
                    'issues' => ['3 images missing alt text'],
                    'recommendations' => ['Add alt text to all images'],
                    'metrics' => ['total_images' => 5, 'without_alt' => 3]
                ]
            ]
        ];

        // Act
        $result = $this->recommendationEngine->generateRecommendations($analysisResults);

        // Assert
        $recommendations = $result['recommendations'];
        $altTextRec = collect($recommendations)->first(function ($rec) {
            return str_contains($rec['title'], 'Alt Text');
        });

        $this->assertNotNull($altTextRec);
        $this->assertEquals('high', $altTextRec['priority']);
        $this->assertEquals('accessibility', $altTextRec['category']);
        $this->assertEquals(6, $altTextRec['estimated_time_minutes']); // 2 minutes per image
    }

    public function test_generate_recommendations_prioritization()
    {
        // Arrange
        $analysisResults = [
            'overall_score' => 40,
            'category_scores' => [
                'title' => [
                    'score' => 0,
                    'issues' => ['Missing title tag'],
                    'recommendations' => []
                ],
                'meta_description' => [
                    'score' => 0,
                    'issues' => ['Missing meta description'],
                    'recommendations' => []
                ],
                'images' => [
                    'score' => 80,
                    'issues' => ['1 image missing alt text'],
                    'recommendations' => []
                ]
            ]
        ];

        // Act
        $result = $this->recommendationEngine->generateRecommendations($analysisResults);

        // Assert
        $recommendations = $result['recommendations'];
        $this->assertGreaterThanOrEqual(2, count($recommendations));

        // Critical issues should come first
        $firstRec = $recommendations[0];
        $this->assertEquals('critical', $firstRec['priority']);
    }

    public function test_generate_recommendations_grouping()
    {
        // Arrange
        $analysisResults = $this->getBasicAnalysisResults();

        // Act
        $result = $this->recommendationEngine->generateRecommendations($analysisResults);

        // Assert
        $grouped = $result['grouped_recommendations'];

        $this->assertArrayHasKey('by_priority', $grouped);
        $this->assertArrayHasKey('by_category', $grouped);
        $this->assertArrayHasKey('by_difficulty', $grouped);

        // Check that priority grouping exists
        if (!empty($result['recommendations'])) {
            $firstPriority = $result['recommendations'][0]['priority'];
            $this->assertArrayHasKey($firstPriority, $grouped['by_priority']);
        }
    }

    public function test_identify_quick_wins()
    {
        // Arrange
        $analysisResults = [
            'overall_score' => 65,
            'category_scores' => [
                'title' => [
                    'score' => 50,
                    'issues' => ['Title too short'],
                    'recommendations' => ['Expand title length']
                ],
                'images' => [
                    'score' => 70,
                    'issues' => ['1 image missing alt text'],
                    'recommendations' => ['Add alt text']
                ]
            ]
        ];

        // Act
        $result = $this->recommendationEngine->generateRecommendations($analysisResults);

        // Assert
        $this->assertArrayHasKey('quick_wins', $result);
        $quickWins = $result['quick_wins'];

        // Quick wins should be high impact, easy, and fast
        foreach ($quickWins as $quickWin) {
            $this->assertGreaterThanOrEqual(30, $quickWin['impact_score']);
            $this->assertEquals('easy', $quickWin['difficulty']);
            $this->assertLessThanOrEqual(60, $quickWin['estimated_time_minutes']);
        }
    }

    public function test_identify_long_term_goals()
    {
        // Arrange
        $analysisResults = [
            'overall_score' => 45,
            'category_scores' => [
                'content' => [
                    'score' => 20,
                    'issues' => ['Content too short', 'Low text-to-HTML ratio'],
                    'recommendations' => ['Complete content overhaul needed']
                ]
            ]
        ];

        // Act
        $result = $this->recommendationEngine->generateRecommendations($analysisResults);

        // Assert
        $this->assertArrayHasKey('long_term_goals', $result);
        $longTermGoals = $result['long_term_goals'];

        // Long term goals should be time-consuming or difficult
        foreach ($longTermGoals as $goal) {
            $shouldBeLongTerm = $goal['estimated_time_minutes'] > 240 || $goal['difficulty'] === 'hard';
            $this->assertTrue($shouldBeLongTerm);
        }
    }

    public function test_generate_recommendation_summary()
    {
        // Arrange
        $analysisResults = $this->getBasicAnalysisResults();

        // Act
        $result = $this->recommendationEngine->generateRecommendations($analysisResults);

        // Assert
        $summary = $result['summary'];

        $this->assertArrayHasKey('total_recommendations', $summary);
        $this->assertArrayHasKey('by_priority', $summary);
        $this->assertArrayHasKey('by_category', $summary);
        $this->assertArrayHasKey('estimated_total_time_hours', $summary);
        $this->assertArrayHasKey('potential_impact_score', $summary);

        $this->assertEquals($result['total_recommendations'], $summary['total_recommendations']);
        $this->assertIsFloat($summary['estimated_total_time_hours']);
        $this->assertGreaterThanOrEqual(0, $summary['potential_impact_score']);
    }

    public function test_generate_competitive_recommendations()
    {
        // Arrange
        $siteAnalysis = [
            'overall_score' => 70,
            'category_scores' => [
                'title' => ['score' => 80],
                'content' => ['score' => 60]
            ]
        ];

        $competitorAnalysis = [
            'competitor1.com' => [
                'overall_score' => 85,
                'category_scores' => [
                    'title' => ['score' => 90],
                    'content' => ['score' => 85]
                ]
            ]
        ];

        // Act
        $result = $this->recommendationEngine->generateCompetitiveRecommendations($siteAnalysis, $competitorAnalysis);

        // Assert
        $this->assertArrayHasKey('competitive_recommendations', $result);
        $this->assertArrayHasKey('competitive_summary', $result);
        $this->assertArrayHasKey('opportunities', $result);

        $this->assertIsArray($result['competitive_recommendations']);
        $this->assertIsArray($result['competitive_summary']);
    }

    public function test_generate_industry_recommendations()
    {
        // Arrange
        $analysisResults = $this->getBasicAnalysisResults();
        $industry = 'e-commerce';

        $this->seoMetrics->shouldReceive('getIndustryWeights')
            ->with($industry)
            ->andReturn([
                'title' => 20,
                'images' => 18, // High weight for e-commerce
                'structured_data' => 10, // High weight for e-commerce
                'content' => 15
            ]);

        // Act
        $result = $this->recommendationEngine->generateIndustryRecommendations($analysisResults, $industry);

        // Assert
        $this->assertArrayHasKey('industry_recommendations', $result);
        $this->assertArrayHasKey('industry', $result);
        $this->assertArrayHasKey('focus_areas', $result);
        $this->assertArrayHasKey('industry_benchmarks', $result);

        $this->assertEquals($industry, $result['industry']);
        $this->assertContains('Product images', $result['focus_areas']);
        $this->assertContains('Schema markup', $result['focus_areas']);
    }

    public function test_recommendation_object_structure()
    {
        // Arrange
        $analysisResults = [
            'overall_score' => 50,
            'category_scores' => [
                'title' => [
                    'score' => 30,
                    'issues' => ['Title too short'],
                    'recommendations' => []
                ]
            ]
        ];

        // Act
        $result = $this->recommendationEngine->generateRecommendations($analysisResults);

        // Assert
        $this->assertNotEmpty($result['recommendations']);
        $recommendation = $result['recommendations'][0];

        // Check required fields
        $this->assertArrayHasKey('id', $recommendation);
        $this->assertArrayHasKey('title', $recommendation);
        $this->assertArrayHasKey('description', $recommendation);
        $this->assertArrayHasKey('priority', $recommendation);
        $this->assertArrayHasKey('priority_score', $recommendation);
        $this->assertArrayHasKey('category', $recommendation);
        $this->assertArrayHasKey('category_name', $recommendation);
        $this->assertArrayHasKey('impact_score', $recommendation);
        $this->assertArrayHasKey('difficulty', $recommendation);
        $this->assertArrayHasKey('estimated_time_minutes', $recommendation);
        $this->assertArrayHasKey('estimated_time_human', $recommendation);
        $this->assertArrayHasKey('details', $recommendation);
        $this->assertArrayHasKey('created_at', $recommendation);

        // Check implementation details added
        $this->assertArrayHasKey('implementation', $recommendation);
        $this->assertArrayHasKey('roi_estimate', $recommendation);
        $this->assertArrayHasKey('dependencies', $recommendation);

        // Validate field types
        $this->assertIsString($recommendation['id']);
        $this->assertIsString($recommendation['title']);
        $this->assertIsInt($recommendation['impact_score']);
        $this->assertIsInt($recommendation['estimated_time_minutes']);
        $this->assertContains($recommendation['priority'], ['critical', 'high', 'medium', 'low', 'optional']);
        $this->assertContains($recommendation['difficulty'], ['easy', 'medium', 'hard']);
    }

    public function test_comprehensive_audit_recommendation_for_low_score()
    {
        // Arrange
        $analysisResults = [
            'overall_score' => 35, // Very low score
            'category_scores' => [
                'title' => ['score' => 20],
                'content' => ['score' => 25],
                'technical' => ['score' => 40],
                'images' => ['score' => 30]
            ]
        ];

        // Act
        $result = $this->recommendationEngine->generateRecommendations($analysisResults);

        // Assert
        $auditRec = collect($result['recommendations'])->first(function ($rec) {
            return str_contains($rec['title'], 'Comprehensive') || str_contains($rec['title'], 'Audit');
        });

        $this->assertNotNull($auditRec);
        $this->assertEquals('critical', $auditRec['priority']);
        $this->assertGreaterThan(300, $auditRec['estimated_time_minutes']); // Should be time-consuming
    }

    private function getBasicAnalysisResults(): array
    {
        return [
            'overall_score' => 65,
            'category_scores' => [
                'title' => [
                    'score' => 70,
                    'issues' => ['Title could be improved'],
                    'recommendations' => ['Optimize title length'],
                    'metrics' => ['length' => 45]
                ],
                'meta_description' => [
                    'score' => 60,
                    'issues' => ['Description too short'],
                    'recommendations' => ['Expand meta description'],
                    'metrics' => ['length' => 100]
                ],
                'content' => [
                    'score' => 75,
                    'issues' => [],
                    'recommendations' => [],
                    'metrics' => ['word_count' => 500]
                ],
                'images' => [
                    'score' => 50,
                    'issues' => ['2 images missing alt text'],
                    'recommendations' => ['Add alt text to images'],
                    'metrics' => ['total_images' => 5, 'without_alt' => 2]
                ]
            ]
        ];
    }
}