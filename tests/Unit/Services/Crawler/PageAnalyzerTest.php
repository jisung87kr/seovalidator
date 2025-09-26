<?php

namespace Tests\Unit\Services\Crawler;

use Tests\TestCase;
use App\Services\Crawler\PageAnalyzer;
use App\Services\Crawler\DomExtractor;
use App\Services\Analysis\PerformanceAnalyzer;
use App\Services\Quality\ContentQualityAssessor;
use App\Services\Analysis\CompetitiveAnalysis;
use Mockery;

class PageAnalyzerTest extends TestCase
{
    private PageAnalyzer $pageAnalyzer;
    private $domExtractor;
    private $performanceAnalyzer;
    private $contentQualityAssessor;
    private $competitiveAnalysis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->domExtractor = Mockery::mock(DomExtractor::class);
        $this->performanceAnalyzer = Mockery::mock(PerformanceAnalyzer::class);
        $this->contentQualityAssessor = Mockery::mock(ContentQualityAssessor::class);
        $this->competitiveAnalysis = Mockery::mock(CompetitiveAnalysis::class);

        $this->pageAnalyzer = new PageAnalyzer(
            $this->domExtractor,
            $this->performanceAnalyzer,
            $this->contentQualityAssessor,
            $this->competitiveAnalysis
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_perform_basic_page_analysis_without_competitive_analysis()
    {
        $html = '<html><head><title>Test Page</title></head><body><h1>Hello</h1><p>Content here</p></body></html>';
        $url = 'https://example.com';
        $parsedData = ['meta' => ['title' => 'Test Page'], 'content' => ['word_count' => 100]];
        $options = ['include_competitive' => false];

        // Mock DOM extractor responses
        $this->domExtractor->shouldReceive('initialize')
            ->once()
            ->with($html, $url);

        $mockDomData = [
            'tables' => ['total_count' => 0],
            'forms' => ['total_count' => 0],
            'multimedia' => ['images' => ['total_count' => 0]],
            'navigation' => ['nav_elements' => ['total_count' => 1]],
            'accessibility' => [
                'aria_labels' => ['aria_label' => 2],
                'landmarks' => ['landmarks' => ['main' => 1, 'navigation' => 1]],
                'heading_structure' => ['has_h1' => true, 'multiple_h1' => false, 'proper_hierarchy' => true],
                'focus_management' => ['tabindex_positive' => 0, 'focusable_elements' => 5],
                'keyboard_navigation' => ['skip_links_count' => 1]
            ],
            'performance_elements' => [
                'lazy_loading' => ['img_lazy' => 0],
                'preload_hints' => ['preload' => 1],
                'resource_hints' => ['preconnect' => 1],
                'third_party_scripts' => ['total_count' => 2]
            ],
            'security' => [
                'integrity_attributes' => ['script_integrity' => 1, 'link_integrity' => 0],
                'external_links_security' => ['without_noopener' => 0],
                'form_security' => ['forms_without_csrf' => 0]
            ],
            'semantic' => ['semantic_score' => 75],
            'custom_data' => ['data_attributes' => ['total_data_attributes' => 3]]
        ];

        // Mock performance analyzer
        $this->performanceAnalyzer->shouldReceive('analyze')
            ->once()
            ->with($html, $url, $mockDomData, $options)
            ->andReturn([
                'overall_score' => 85.0,
                'recommendations' => [
                    ['type' => 'suggestion', 'category' => 'performance', 'message' => 'Consider lazy loading']
                ]
            ]);

        // Mock content quality assessor
        $this->contentQualityAssessor->shouldReceive('assess')
            ->once()
            ->andReturn([
                'overall_score' => 78.5,
                'recommendations' => [
                    ['type' => 'warning', 'category' => 'content', 'message' => 'Improve readability']
                ]
            ]);

        // Mock competitive analysis (should not be called)
        $this->competitiveAnalysis->shouldNotReceive('analyze');

        // Set up DOM extractor method expectations
        $this->domExtractor->shouldReceive('extractTables')->once()->andReturn($mockDomData['tables']);
        $this->domExtractor->shouldReceive('extractForms')->once()->andReturn($mockDomData['forms']);
        $this->domExtractor->shouldReceive('extractMultimedia')->once()->andReturn($mockDomData['multimedia']);
        $this->domExtractor->shouldReceive('extractNavigation')->once()->andReturn($mockDomData['navigation']);
        $this->domExtractor->shouldReceive('extractAccessibilityFeatures')->once()->andReturn($mockDomData['accessibility']);
        $this->domExtractor->shouldReceive('extractPerformanceElements')->once()->andReturn($mockDomData['performance_elements']);
        $this->domExtractor->shouldReceive('extractSecurityElements')->once()->andReturn($mockDomData['security']);
        $this->domExtractor->shouldReceive('extractSemanticElements')->once()->andReturn($mockDomData['semantic']);
        $this->domExtractor->shouldReceive('extractCustomData')->once()->andReturn($mockDomData['custom_data']);

        $result = $this->pageAnalyzer->analyze($html, $url, $parsedData, $options);

        $this->assertIsArray($result);
        $this->assertEquals($url, $result['url']);
        $this->assertArrayHasKey('analyzed_at', $result);
        $this->assertArrayHasKey('analysis_duration_ms', $result);
        $this->assertArrayHasKey('quality_score', $result);
        $this->assertArrayHasKey('dom_analysis', $result);
        $this->assertArrayHasKey('performance_analysis', $result);
        $this->assertArrayHasKey('content_quality', $result);
        $this->assertArrayHasKey('competitive_analysis', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('summary', $result);

        // Verify competitive analysis is empty when not requested
        $this->assertEmpty($result['competitive_analysis']);

        // Verify recommendations are combined
        $this->assertIsArray($result['recommendations']);
        $this->assertGreaterThan(0, count($result['recommendations']));

        // Verify quality score structure
        $this->assertIsArray($result['quality_score']);
        $this->assertArrayHasKey('overall', $result['quality_score']);
        $this->assertArrayHasKey('components', $result['quality_score']);
        $this->assertArrayHasKey('grade', $result['quality_score']);

        // Verify score is numeric
        $this->assertIsNumeric($result['quality_score']['overall']);
        $this->assertGreaterThanOrEqual(0, $result['quality_score']['overall']);
        $this->assertLessThanOrEqual(100, $result['quality_score']['overall']);
    }

    /** @test */
    public function it_can_perform_comprehensive_analysis_with_competitive_data()
    {
        $html = '<html><head><title>Test Page</title></head><body><h1>Hello</h1><p>Content here</p></body></html>';
        $url = 'https://example.com';
        $parsedData = ['meta' => ['title' => 'Test Page'], 'content' => ['word_count' => 100]];
        $options = ['include_competitive' => true, 'competitors' => ['competitor1.com', 'competitor2.com']];

        // Set up basic mocks (simplified for this test)
        $this->domExtractor->shouldReceive('initialize')->once();
        $this->domExtractor->shouldReceive('extractTables')->once()->andReturn(['total_count' => 0]);
        $this->domExtractor->shouldReceive('extractForms')->once()->andReturn(['total_count' => 0]);
        $this->domExtractor->shouldReceive('extractMultimedia')->once()->andReturn(['images' => ['total_count' => 0]]);
        $this->domExtractor->shouldReceive('extractNavigation')->once()->andReturn(['nav_elements' => ['total_count' => 1]]);
        $this->domExtractor->shouldReceive('extractAccessibilityFeatures')->once()->andReturn(['aria_labels' => ['aria_label' => 2]]);
        $this->domExtractor->shouldReceive('extractPerformanceElements')->once()->andReturn(['lazy_loading' => ['img_lazy' => 0]]);
        $this->domExtractor->shouldReceive('extractSecurityElements')->once()->andReturn(['integrity_attributes' => ['script_integrity' => 1]]);
        $this->domExtractor->shouldReceive('extractSemanticElements')->once()->andReturn(['semantic_score' => 75]);
        $this->domExtractor->shouldReceive('extractCustomData')->once()->andReturn(['data_attributes' => ['total_data_attributes' => 3]]);

        $this->performanceAnalyzer->shouldReceive('analyze')
            ->once()
            ->andReturn(['overall_score' => 85.0, 'recommendations' => []]);

        $this->contentQualityAssessor->shouldReceive('assess')
            ->once()
            ->andReturn(['overall_score' => 78.5, 'recommendations' => []]);

        // Mock competitive analysis
        $mockCompetitiveData = [
            'competitors_analyzed' => 2,
            'benchmarks' => ['seo_benchmarks' => ['title_length' => ['average' => 45]]],
            'competitive_position' => ['overall' => ['position' => 2]],
            'opportunities' => [['description' => 'Improve title length']],
            'insights' => ['market_analysis' => ['competitive_landscape' => 'moderate']],
            'recommendations' => [
                ['type' => 'competitive', 'message' => 'Benchmark against top competitor']
            ]
        ];

        $this->competitiveAnalysis->shouldReceive('analyze')
            ->once()
            ->with($url, Mockery::any(), $options)
            ->andReturn($mockCompetitiveData);

        $result = $this->pageAnalyzer->analyze($html, $url, $parsedData, $options);

        // Verify competitive analysis is included
        $this->assertNotEmpty($result['competitive_analysis']);
        $this->assertEquals(2, $result['competitive_analysis']['competitors_analyzed']);
        $this->assertArrayHasKey('benchmarks', $result['competitive_analysis']);
        $this->assertArrayHasKey('opportunities', $result['competitive_analysis']);
    }

    /** @test */
    public function it_calculates_quality_score_properly()
    {
        $html = '<html><head><title>Test</title></head><body><h1>Test</h1></body></html>';
        $url = 'https://example.com';
        $parsedData = [];
        $options = [];

        // Mock perfect scores for testing calculation
        $mockDomData = [
            'accessibility' => [
                'aria_labels' => ['aria_label' => 5, 'aria_labelledby' => 3, 'aria_describedby' => 2],
                'landmarks' => ['landmarks' => ['main' => 1, 'navigation' => 1, 'banner' => 1], 'total_landmarks' => 3],
                'heading_structure' => ['has_h1' => true, 'multiple_h1' => false, 'proper_hierarchy' => true],
                'focus_management' => ['tabindex_positive' => 0, 'focusable_elements' => 10],
                'keyboard_navigation' => ['skip_links_count' => 1, 'access_keys_count' => 0]
            ],
            'performance_elements' => [
                'lazy_loading' => ['img_lazy' => 5, 'iframe_lazy' => 2],
                'preload_hints' => ['preload' => 3, 'prefetch' => 1],
                'resource_hints' => ['preconnect' => 2, 'dns_prefetch' => 3],
                'third_party_scripts' => ['total_count' => 2]
            ],
            'security' => [
                'integrity_attributes' => ['script_integrity' => 5, 'link_integrity' => 3],
                'external_links_security' => ['without_noopener' => 0, 'without_noreferrer' => 0],
                'form_security' => ['forms_without_csrf' => 0]
            ],
            'semantic' => ['semantic_score' => 90]
        ];

        $this->setUpBasicMocks($html, $url, $mockDomData);

        // Mock high-scoring performance analysis
        $this->performanceAnalyzer->shouldReceive('analyze')
            ->once()
            ->andReturn(['overall_score' => 92.0, 'recommendations' => []]);

        // Mock high-scoring content quality
        $this->contentQualityAssessor->shouldReceive('assess')
            ->once()
            ->andReturn(['overall_score' => 88.0, 'recommendations' => []]);

        $result = $this->pageAnalyzer->analyze($html, $url, $parsedData, $options);

        // With high component scores, overall should be high
        $this->assertGreaterThan(80, $result['quality_score']['overall']);
        $this->assertArrayHasKey('components', $result['quality_score']);
        $this->assertEquals('A', $result['quality_score']['grade']);

        // Verify all component scores are present
        $components = $result['quality_score']['components'];
        $this->assertArrayHasKey('performance', $components);
        $this->assertArrayHasKey('content', $components);
        $this->assertArrayHasKey('accessibility', $components);
        $this->assertArrayHasKey('technical', $components);
        $this->assertArrayHasKey('semantic', $components);
    }

    /** @test */
    public function it_handles_low_quality_scores_appropriately()
    {
        $html = '<html><head></head><body>Short content</body></html>';
        $url = 'https://example.com';
        $parsedData = [];
        $options = [];

        // Mock low-quality DOM data
        $mockDomData = [
            'accessibility' => [
                'aria_labels' => ['aria_label' => 0],
                'landmarks' => ['landmarks' => [], 'total_landmarks' => 0],
                'heading_structure' => ['has_h1' => false, 'multiple_h1' => false, 'proper_hierarchy' => false],
                'focus_management' => ['tabindex_positive' => 3, 'focusable_elements' => 2],
                'keyboard_navigation' => ['skip_links_count' => 0]
            ],
            'performance_elements' => [
                'lazy_loading' => ['img_lazy' => 0],
                'preload_hints' => ['preload' => 0],
                'resource_hints' => ['preconnect' => 0],
                'third_party_scripts' => ['total_count' => 15]
            ],
            'security' => [
                'integrity_attributes' => ['script_integrity' => 0, 'link_integrity' => 0],
                'external_links_security' => ['without_noopener' => 5],
                'form_security' => ['forms_without_csrf' => 3]
            ],
            'semantic' => ['semantic_score' => 20]
        ];

        $this->setUpBasicMocks($html, $url, $mockDomData);

        // Mock low-scoring analyses
        $this->performanceAnalyzer->shouldReceive('analyze')
            ->once()
            ->andReturn(['overall_score' => 35.0, 'recommendations' => []]);

        $this->contentQualityAssessor->shouldReceive('assess')
            ->once()
            ->andReturn(['overall_score' => 25.0, 'recommendations' => []]);

        $result = $this->pageAnalyzer->analyze($html, $url, $parsedData, $options);

        // With low component scores, overall should be low
        $this->assertLessThan(50, $result['quality_score']['overall']);
        $this->assertIn($result['quality_score']['grade'], ['D', 'F']);

        // Should have recommendations for improvement
        $this->assertGreaterThan(0, count($result['recommendations']));
    }

    /** @test */
    public function it_generates_appropriate_summary_and_insights()
    {
        $html = '<html><head><title>Test</title></head><body><h1>Test</h1><p>Good content here with proper structure.</p></body></html>';
        $url = 'https://example.com';
        $parsedData = [];
        $options = [];

        // Mock moderate quality data
        $mockDomData = $this->getBasicDomData();

        $this->setUpBasicMocks($html, $url, $mockDomData);

        $this->performanceAnalyzer->shouldReceive('analyze')
            ->once()
            ->andReturn(['overall_score' => 75.0, 'recommendations' => []]);

        $this->contentQualityAssessor->shouldReceive('assess')
            ->once()
            ->andReturn(['overall_score' => 70.0, 'recommendations' => []]);

        $result = $this->pageAnalyzer->analyze($html, $url, $parsedData, $options);

        $this->assertArrayHasKey('summary', $result);
        $summary = $result['summary'];

        $this->assertArrayHasKey('overall_assessment', $summary);
        $this->assertArrayHasKey('strengths', $summary);
        $this->assertArrayHasKey('weaknesses', $summary);
        $this->assertArrayHasKey('priority_actions', $summary);
        $this->assertArrayHasKey('key_metrics', $summary);

        // Verify summary content makes sense
        $this->assertIsString($summary['overall_assessment']);
        $this->assertIsArray($summary['strengths']);
        $this->assertIsArray($summary['weaknesses']);
        $this->assertIsArray($summary['priority_actions']);
        $this->assertLessThanOrEqual(3, count($summary['priority_actions'])); // Should limit to top 3
    }

    /**
     * Helper method to set up basic mocks
     */
    private function setUpBasicMocks(string $html, string $url, array $mockDomData): void
    {
        $this->domExtractor->shouldReceive('initialize')->once()->with($html, $url);
        $this->domExtractor->shouldReceive('extractTables')->once()->andReturn($mockDomData['tables'] ?? ['total_count' => 0]);
        $this->domExtractor->shouldReceive('extractForms')->once()->andReturn($mockDomData['forms'] ?? ['total_count' => 0]);
        $this->domExtractor->shouldReceive('extractMultimedia')->once()->andReturn($mockDomData['multimedia'] ?? ['images' => ['total_count' => 0]]);
        $this->domExtractor->shouldReceive('extractNavigation')->once()->andReturn($mockDomData['navigation'] ?? ['nav_elements' => ['total_count' => 1]]);
        $this->domExtractor->shouldReceive('extractAccessibilityFeatures')->once()->andReturn($mockDomData['accessibility']);
        $this->domExtractor->shouldReceive('extractPerformanceElements')->once()->andReturn($mockDomData['performance_elements']);
        $this->domExtractor->shouldReceive('extractSecurityElements')->once()->andReturn($mockDomData['security']);
        $this->domExtractor->shouldReceive('extractSemanticElements')->once()->andReturn($mockDomData['semantic']);
        $this->domExtractor->shouldReceive('extractCustomData')->once()->andReturn($mockDomData['custom_data'] ?? ['data_attributes' => ['total_data_attributes' => 0]]);
    }

    /**
     * Helper method to get basic DOM data for testing
     */
    private function getBasicDomData(): array
    {
        return [
            'accessibility' => [
                'aria_labels' => ['aria_label' => 2],
                'landmarks' => ['landmarks' => ['main' => 1], 'total_landmarks' => 1],
                'heading_structure' => ['has_h1' => true, 'multiple_h1' => false, 'proper_hierarchy' => true],
                'focus_management' => ['tabindex_positive' => 0, 'focusable_elements' => 5],
                'keyboard_navigation' => ['skip_links_count' => 0]
            ],
            'performance_elements' => [
                'lazy_loading' => ['img_lazy' => 2],
                'preload_hints' => ['preload' => 1],
                'resource_hints' => ['preconnect' => 1],
                'third_party_scripts' => ['total_count' => 3]
            ],
            'security' => [
                'integrity_attributes' => ['script_integrity' => 1, 'link_integrity' => 0],
                'external_links_security' => ['without_noopener' => 1],
                'form_security' => ['forms_without_csrf' => 0]
            ],
            'semantic' => ['semantic_score' => 65]
        ];
    }
}