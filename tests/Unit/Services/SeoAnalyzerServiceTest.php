<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\SeoAnalyzerService;
use App\Services\Crawler\CrawlerService;
use App\Services\Crawler\UrlValidator;
use App\Services\Parser\HtmlParserService;
use App\Services\Score\ScoreCalculatorService;
use Illuminate\Support\Facades\Redis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class SeoAnalyzerServiceTest extends TestCase
{
    use RefreshDatabase;

    private SeoAnalyzerService $seoAnalyzer;
    private $mockCrawlerService;
    private $mockUrlValidator;
    private $mockHtmlParserService;
    private $mockScoreCalculatorService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks
        $this->mockCrawlerService = Mockery::mock(CrawlerService::class);
        $this->mockUrlValidator = Mockery::mock(UrlValidator::class);
        $this->mockHtmlParserService = Mockery::mock(HtmlParserService::class);
        $this->mockScoreCalculatorService = Mockery::mock(ScoreCalculatorService::class);

        // Create service instance with mocks
        $this->seoAnalyzer = new SeoAnalyzerService(
            $this->mockCrawlerService,
            $this->mockUrlValidator,
            $this->mockHtmlParserService,
            $this->mockScoreCalculatorService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_analyze_performs_complete_seo_analysis()
    {
        // Arrange
        $url = 'https://example.com';
        $validatedUrl = 'https://example.com/';

        $crawlData = [
            'html' => '<html><head><title>Test Page</title></head><body><h1>Test</h1></body></html>',
            'status_code' => 200,
            'duration_ms' => 1500,
            'load_time_ms' => 800,
            'javascript_enabled' => true,
            'user_agent' => 'SEO Validator Bot/1.0',
            'final_url' => $validatedUrl
        ];

        $parsedData = [
            'meta' => [
                'title' => 'Test Page',
                'title_length' => 9,
                'description' => 'Test description',
                'description_length' => 16
            ],
            'headings' => ['h1' => ['Test']],
            'images' => ['total_count' => 0, 'without_alt_count' => 0],
            'links' => ['total_count' => 0, 'internal_count' => 0],
            'content' => ['word_count' => 50],
            'technical' => ['doctype' => '<!DOCTYPE html>'],
            'structured_data' => ['json_ld' => []],
            'social_media' => ['open_graph' => []],
            'seo_tags' => [],
            'performance' => []
        ];

        $scores = [
            'overall_score' => 75,
            'grade' => 'B',
            'category_scores' => [
                'title' => ['score' => 80, 'weight' => 20],
                'meta_description' => ['score' => 70, 'weight' => 15]
            ]
        ];

        // Set expectations
        $this->mockUrlValidator->shouldReceive('validate')
            ->with($url)
            ->once()
            ->andReturn($validatedUrl);

        $this->mockCrawlerService->shouldReceive('crawl')
            ->with($validatedUrl, [])
            ->once()
            ->andReturn($crawlData);

        $this->mockHtmlParserService->shouldReceive('parse')
            ->with($crawlData['html'], $validatedUrl)
            ->once()
            ->andReturn($parsedData);

        $this->mockScoreCalculatorService->shouldReceive('calculate')
            ->with($parsedData)
            ->once()
            ->andReturn($scores);

        // Mock Redis for caching
        Redis::shouldReceive('get')->once()->andReturn(null);
        Redis::shouldReceive('setex')->once();

        // Act
        $result = $this->seoAnalyzer->analyze($url);

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals($validatedUrl, $result['url']);
        $this->assertEquals($scores, $result['scores']);
        $this->assertArrayHasKey('analyzed_at', $result);
        $this->assertArrayHasKey('crawl_data', $result);
        $this->assertArrayHasKey('seo_elements', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertEquals(200, $result['status']['code']);
        $this->assertTrue($result['status']['success']);
    }

    public function test_analyze_returns_cached_result_when_available()
    {
        // Arrange
        $url = 'https://example.com';
        $cachedResult = [
            'url' => $url,
            'analyzed_at' => '2023-01-01T00:00:00Z',
            'scores' => ['overall_score' => 85]
        ];

        // Mock Redis to return cached result
        Redis::shouldReceive('get')
            ->once()
            ->andReturn(json_encode($cachedResult));

        // Services should not be called when cache is available
        $this->mockUrlValidator->shouldNotReceive('validate');
        $this->mockCrawlerService->shouldNotReceive('crawl');

        // Act
        $result = $this->seoAnalyzer->analyze($url);

        // Assert
        $this->assertEquals($cachedResult, $result);
    }

    public function test_analyze_bypasses_cache_when_force_refresh_is_true()
    {
        // Arrange
        $url = 'https://example.com';
        $validatedUrl = 'https://example.com/';
        $options = ['force_refresh' => true];

        $this->setupMockExpectations($url, $validatedUrl);

        // Mock Redis - cache should not be checked
        Redis::shouldReceive('get')->never();
        Redis::shouldReceive('setex')->once();

        // Act
        $result = $this->seoAnalyzer->analyze($url, $options);

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals($validatedUrl, $result['url']);
    }

    public function test_analyze_batch_processes_multiple_urls()
    {
        // Arrange
        $urls = ['https://example.com', 'https://test.com'];

        // Mock successful analysis for first URL
        $this->setupMockExpectations($urls[0], $urls[0]);
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('setex');

        // Mock failure for second URL
        $this->mockUrlValidator->shouldReceive('validate')
            ->with($urls[1])
            ->once()
            ->andThrow(new \Exception('Invalid URL'));

        // Act
        $result = $this->seoAnalyzer->analyzeBatch($urls);

        // Assert
        $this->assertArrayHasKey('results', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertCount(1, $result['results']);
        $this->assertCount(1, $result['errors']);
        $this->assertEquals(2, $result['summary']['total_urls']);
        $this->assertEquals(1, $result['summary']['successful']);
        $this->assertEquals(1, $result['summary']['failed']);
    }

    public function test_analyze_throws_exception_when_validation_fails()
    {
        // Arrange
        $url = 'invalid-url';

        $this->mockUrlValidator->shouldReceive('validate')
            ->with($url)
            ->once()
            ->andThrow(new \Exception('Invalid URL format'));

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid URL format');

        $this->seoAnalyzer->analyze($url);
    }

    public function test_analyze_throws_exception_when_crawling_fails()
    {
        // Arrange
        $url = 'https://example.com';
        $validatedUrl = 'https://example.com/';

        $this->mockUrlValidator->shouldReceive('validate')
            ->with($url)
            ->once()
            ->andReturn($validatedUrl);

        $this->mockCrawlerService->shouldReceive('crawl')
            ->with($validatedUrl, [])
            ->once()
            ->andThrow(new \Exception('Crawling failed'));

        Redis::shouldReceive('get')->andReturn(null);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Crawling failed');

        $this->seoAnalyzer->analyze($url);
    }

    public function test_analyze_generates_recommendations()
    {
        // Arrange
        $url = 'https://example.com';
        $validatedUrl = 'https://example.com/';

        $parsedData = [
            'meta' => [
                'title' => '',  // Missing title should generate recommendation
                'description' => 'Short desc'  // Short description should generate recommendation
            ],
            'headings' => ['h1' => []], // Missing H1 should generate recommendation
            'images' => ['total_count' => 2, 'without_alt_count' => 1], // Missing alt text
            'content' => ['word_count' => 100], // Short content
        ];

        $this->setupMockExpectationsWithParsedData($url, $validatedUrl, $parsedData);
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('setex')->once();

        // Act
        $result = $this->seoAnalyzer->analyze($url);

        // Assert
        $this->assertArrayHasKey('recommendations', $result);
        $recommendations = $result['recommendations'];

        // Should have recommendations for missing title, short description, missing H1, and missing alt text
        $this->assertGreaterThan(0, count($recommendations));

        // Check for specific recommendation types
        $recommendationMessages = array_column($recommendations, 'message');
        $this->assertContains('Missing page title tag', $recommendationMessages);
        $this->assertContains('Missing H1 heading', $recommendationMessages);
    }

    private function setupMockExpectations($url, $validatedUrl)
    {
        $crawlData = [
            'html' => '<html><head><title>Test</title></head><body></body></html>',
            'status_code' => 200,
            'duration_ms' => 1000
        ];

        $parsedData = [
            'meta' => ['title' => 'Test', 'description' => 'Test description'],
            'headings' => ['h1' => ['Test']],
            'images' => ['total_count' => 0],
            'links' => ['total_count' => 0],
            'content' => ['word_count' => 50],
            'technical' => [],
            'structured_data' => [],
            'social_media' => [],
            'seo_tags' => [],
            'performance' => []
        ];

        $scores = [
            'overall_score' => 75,
            'grade' => 'B',
            'category_scores' => []
        ];

        $this->mockUrlValidator->shouldReceive('validate')->with($url)->andReturn($validatedUrl);
        $this->mockCrawlerService->shouldReceive('crawl')->andReturn($crawlData);
        $this->mockHtmlParserService->shouldReceive('parse')->andReturn($parsedData);
        $this->mockScoreCalculatorService->shouldReceive('calculate')->andReturn($scores);
    }

    private function setupMockExpectationsWithParsedData($url, $validatedUrl, $parsedData)
    {
        $crawlData = [
            'html' => '<html><head><title>Test</title></head><body></body></html>',
            'status_code' => 200,
            'duration_ms' => 1000
        ];

        $scores = [
            'overall_score' => 60,
            'grade' => 'D',
            'category_scores' => []
        ];

        $this->mockUrlValidator->shouldReceive('validate')->with($url)->andReturn($validatedUrl);
        $this->mockCrawlerService->shouldReceive('crawl')->andReturn($crawlData);
        $this->mockHtmlParserService->shouldReceive('parse')->andReturn($parsedData);
        $this->mockScoreCalculatorService->shouldReceive('calculate')->andReturn($scores);
    }
}