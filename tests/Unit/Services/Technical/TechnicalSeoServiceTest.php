<?php

namespace Tests\Unit\Services\Technical;

use Tests\TestCase;
use App\Services\Technical\TechnicalSeoService;
use App\Services\Technical\PageSpeedService;
use App\Services\Technical\SecurityService;
use App\Services\Technical\SitemapAnalyzerService;
use App\Services\Technical\CanonicalUrlService;
use Illuminate\Support\Facades\Log;
use Mockery;

class TechnicalSeoServiceTest extends TestCase
{
    private TechnicalSeoService $technicalSeoService;
    private PageSpeedService $pageSpeedService;
    private SecurityService $securityService;
    private SitemapAnalyzerService $sitemapAnalyzerService;
    private CanonicalUrlService $canonicalUrlService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock services
        $this->pageSpeedService = Mockery::mock(PageSpeedService::class);
        $this->securityService = Mockery::mock(SecurityService::class);
        $this->sitemapAnalyzerService = Mockery::mock(SitemapAnalyzerService::class);
        $this->canonicalUrlService = Mockery::mock(CanonicalUrlService::class);

        // Create service with mocked dependencies
        $this->technicalSeoService = new TechnicalSeoService(
            $this->pageSpeedService,
            $this->securityService,
            $this->sitemapAnalyzerService,
            $this->canonicalUrlService
        );
    }

    public function test_analyze_returns_comprehensive_technical_seo_data()
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body>Content</body></html>';
        $options = ['include_page_speed' => true];

        // Mock page speed service
        $this->pageSpeedService->shouldReceive('analyze')
            ->once()
            ->with($url, $options)
            ->andReturn([
                'performance_score' => 85,
                'core_web_vitals' => [],
                'recommendations' => []
            ]);

        $this->pageSpeedService->shouldReceive('analyzeMobile')
            ->once()
            ->with($url, $html, $options)
            ->andReturn([
                'mobile_score' => 90,
                'mobile_friendly' => true,
                'recommendations' => []
            ]);

        // Mock security service
        $this->securityService->shouldReceive('analyze')
            ->once()
            ->with($url, $html, $options)
            ->andReturn([
                'security_score' => 75,
                'https_analysis' => ['is_https' => true],
                'recommendations' => []
            ]);

        // Mock sitemap analyzer service
        $this->sitemapAnalyzerService->shouldReceive('analyze')
            ->once()
            ->with($url, $options)
            ->andReturn([
                'sitemap_score' => 80,
                'sitemaps' => [],
                'recommendations' => []
            ]);

        // Mock canonical URL service
        $this->canonicalUrlService->shouldReceive('analyze')
            ->once()
            ->with($url, $html, $options)
            ->andReturn([
                'canonical_score' => 70,
                'canonical_analysis' => [],
                'recommendations' => []
            ]);

        $result = $this->technicalSeoService->analyze($url, $html, $options);

        $this->assertIsArray($result);
        $this->assertEquals($url, $result['url']);
        $this->assertArrayHasKey('analyzed_at', $result);
        $this->assertArrayHasKey('technical_score', $result);
        $this->assertArrayHasKey('page_speed', $result);
        $this->assertArrayHasKey('mobile_optimization', $result);
        $this->assertArrayHasKey('security', $result);
        $this->assertArrayHasKey('sitemap_analysis', $result);
        $this->assertArrayHasKey('canonical_urls', $result);
        $this->assertArrayHasKey('structured_data', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('errors', $result);
    }

    public function test_analyze_handles_service_exceptions_gracefully()
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body>Content</body></html>';

        // Mock page speed service to throw exception
        $this->pageSpeedService->shouldReceive('analyze')
            ->once()
            ->andThrow(new \Exception('Page speed API failed'));

        $this->pageSpeedService->shouldReceive('analyzeMobile')
            ->once()
            ->andReturn([
                'mobile_score' => 90,
                'recommendations' => []
            ]);

        // Mock other services to return normal results
        $this->securityService->shouldReceive('analyze')
            ->once()
            ->andReturn(['security_score' => 75, 'recommendations' => []]);

        $this->sitemapAnalyzerService->shouldReceive('analyze')
            ->once()
            ->andReturn(['sitemap_score' => 80, 'recommendations' => []]);

        $this->canonicalUrlService->shouldReceive('analyze')
            ->once()
            ->andReturn(['canonical_score' => 70, 'recommendations' => []]);

        $result = $this->technicalSeoService->analyze($url, $html);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result['errors']);
        $this->assertContains('Page speed analysis failed: Page speed API failed',
                              array_column($result['errors'], 'message'));
    }

    public function test_analyze_with_disabled_options()
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body>Content</body></html>';
        $options = [
            'include_page_speed' => false,
            'include_mobile_analysis' => false,
            'include_security_analysis' => false,
            'include_sitemap_analysis' => false,
            'include_canonical_analysis' => false,
            'include_structured_data' => false
        ];

        $result = $this->technicalSeoService->analyze($url, $html, $options);

        $this->assertIsArray($result);
        $this->assertNull($result['page_speed']);
        $this->assertNull($result['mobile_optimization']);
        $this->assertNull($result['security']);
        $this->assertNull($result['sitemap_analysis']);
        $this->assertNull($result['canonical_urls']);
        $this->assertNull($result['structured_data']);
    }

    public function test_analyze_structured_data_extracts_json_ld()
    {
        $url = 'https://example.com';
        $html = '
            <html>
                <head>
                    <script type="application/ld+json">
                    {
                        "@context": "https://schema.org",
                        "@type": "Organization",
                        "name": "Test Company"
                    }
                    </script>
                </head>
                <body>Content</body>
            </html>
        ';

        // Mock all other services
        $this->pageSpeedService->shouldReceive('analyze')->andReturn(['performance_score' => 85, 'recommendations' => []]);
        $this->pageSpeedService->shouldReceive('analyzeMobile')->andReturn(['mobile_score' => 90, 'recommendations' => []]);
        $this->securityService->shouldReceive('analyze')->andReturn(['security_score' => 75, 'recommendations' => []]);
        $this->sitemapAnalyzerService->shouldReceive('analyze')->andReturn(['sitemap_score' => 80, 'recommendations' => []]);
        $this->canonicalUrlService->shouldReceive('analyze')->andReturn(['canonical_score' => 70, 'recommendations' => []]);

        $result = $this->technicalSeoService->analyze($url, $html);

        $this->assertIsArray($result['structured_data']);
        $this->assertNotEmpty($result['structured_data']['json_ld']);
        $this->assertEquals('Organization', $result['structured_data']['json_ld'][0]['@type']);
        $this->assertEquals('Test Company', $result['structured_data']['json_ld'][0]['name']);
    }

    public function test_analyze_structured_data_extracts_microdata()
    {
        $url = 'https://example.com';
        $html = '
            <html>
                <head></head>
                <body>
                    <div itemscope itemtype="https://schema.org/LocalBusiness">
                        <span itemprop="name">Local Business</span>
                    </div>
                </body>
            </html>
        ';

        // Mock all other services
        $this->pageSpeedService->shouldReceive('analyze')->andReturn(['performance_score' => 85, 'recommendations' => []]);
        $this->pageSpeedService->shouldReceive('analyzeMobile')->andReturn(['mobile_score' => 90, 'recommendations' => []]);
        $this->securityService->shouldReceive('analyze')->andReturn(['security_score' => 75, 'recommendations' => []]);
        $this->sitemapAnalyzerService->shouldReceive('analyze')->andReturn(['sitemap_score' => 80, 'recommendations' => []]);
        $this->canonicalUrlService->shouldReceive('analyze')->andReturn(['canonical_score' => 70, 'recommendations' => []]);

        $result = $this->technicalSeoService->analyze($url, $html);

        $this->assertIsArray($result['structured_data']);
        $this->assertNotEmpty($result['structured_data']['microdata']);
        $this->assertEquals('https://schema.org/LocalBusiness', $result['structured_data']['microdata'][0]['itemtype']);
        $this->assertEquals('LocalBusiness', $result['structured_data']['microdata'][0]['schema_type']);
    }

    public function test_calculate_technical_score_with_all_services()
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body>Content</body></html>';

        // Mock all services with high scores
        $this->pageSpeedService->shouldReceive('analyze')
            ->andReturn(['performance_score' => 90, 'recommendations' => []]);
        $this->pageSpeedService->shouldReceive('analyzeMobile')
            ->andReturn(['mobile_score' => 85, 'recommendations' => []]);
        $this->securityService->shouldReceive('analyze')
            ->andReturn(['security_score' => 80, 'recommendations' => []]);
        $this->sitemapAnalyzerService->shouldReceive('analyze')
            ->andReturn(['sitemap_score' => 75, 'recommendations' => []]);
        $this->canonicalUrlService->shouldReceive('analyze')
            ->andReturn(['canonical_score' => 70, 'recommendations' => []]);

        $result = $this->technicalSeoService->analyze($url, $html);

        $this->assertGreaterThan(70, $result['technical_score']);
        $this->assertLessThanOrEqual(100, $result['technical_score']);
    }

    public function test_generate_recommendations_includes_all_service_recommendations()
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body>Content</body></html>';

        // Mock services with recommendations
        $this->pageSpeedService->shouldReceive('analyze')
            ->andReturn([
                'performance_score' => 50,
                'recommendations' => [
                    ['type' => 'warning', 'message' => 'Optimize images']
                ]
            ]);
        $this->pageSpeedService->shouldReceive('analyzeMobile')
            ->andReturn([
                'mobile_score' => 60,
                'recommendations' => [
                    ['type' => 'info', 'message' => 'Add viewport meta tag']
                ]
            ]);
        $this->securityService->shouldReceive('analyze')
            ->andReturn([
                'security_score' => 40,
                'recommendations' => [
                    ['type' => 'error', 'message' => 'Enable HTTPS']
                ]
            ]);
        $this->sitemapAnalyzerService->shouldReceive('analyze')
            ->andReturn([
                'sitemap_score' => 30,
                'recommendations' => [
                    ['type' => 'warning', 'message' => 'Create sitemap']
                ]
            ]);
        $this->canonicalUrlService->shouldReceive('analyze')
            ->andReturn([
                'canonical_score' => 20,
                'recommendations' => [
                    ['type' => 'info', 'message' => 'Add canonical URLs']
                ]
            ]);

        $result = $this->technicalSeoService->analyze($url, $html);

        $this->assertNotEmpty($result['recommendations']);
        $this->assertGreaterThanOrEqual(5, count($result['recommendations']));
    }

    public function test_json_ld_validation_detects_missing_context()
    {
        $url = 'https://example.com';
        $html = '
            <html>
                <head>
                    <script type="application/ld+json">
                    {
                        "@type": "Organization",
                        "name": "Test Company"
                    }
                    </script>
                </head>
                <body>Content</body>
            </html>
        ';

        // Mock all other services
        $this->pageSpeedService->shouldReceive('analyze')->andReturn(['performance_score' => 85, 'recommendations' => []]);
        $this->pageSpeedService->shouldReceive('analyzeMobile')->andReturn(['mobile_score' => 90, 'recommendations' => []]);
        $this->securityService->shouldReceive('analyze')->andReturn(['security_score' => 75, 'recommendations' => []]);
        $this->sitemapAnalyzerService->shouldReceive('analyze')->andReturn(['sitemap_score' => 80, 'recommendations' => []]);
        $this->canonicalUrlService->shouldReceive('analyze')->andReturn(['canonical_score' => 70, 'recommendations' => []]);

        $result = $this->technicalSeoService->analyze($url, $html);

        $this->assertNotEmpty($result['structured_data']['schema_validation']);
        $validation = $result['structured_data']['schema_validation'][0];
        $this->assertFalse($validation['is_valid']);
        $this->assertContains('Missing @context property', $validation['errors']);
    }

    public function test_rich_snippets_eligibility_detection()
    {
        $url = 'https://example.com';
        $html = '
            <html>
                <head>
                    <script type="application/ld+json">
                    {
                        "@context": "https://schema.org",
                        "@type": "Article",
                        "headline": "Test Article",
                        "author": "Test Author"
                    }
                    </script>
                </head>
                <body>Content</body>
            </html>
        ';

        // Mock all other services
        $this->pageSpeedService->shouldReceive('analyze')->andReturn(['performance_score' => 85, 'recommendations' => []]);
        $this->pageSpeedService->shouldReceive('analyzeMobile')->andReturn(['mobile_score' => 90, 'recommendations' => []]);
        $this->securityService->shouldReceive('analyze')->andReturn(['security_score' => 75, 'recommendations' => []]);
        $this->sitemapAnalyzerService->shouldReceive('analyze')->andReturn(['sitemap_score' => 80, 'recommendations' => []]);
        $this->canonicalUrlService->shouldReceive('analyze')->andReturn(['canonical_score' => 70, 'recommendations' => []]);

        $result = $this->technicalSeoService->analyze($url, $html);

        $this->assertTrue($result['structured_data']['rich_snippets_eligible']);
    }

    public function test_invalid_json_ld_is_handled_gracefully()
    {
        $url = 'https://example.com';
        $html = '
            <html>
                <head>
                    <script type="application/ld+json">
                    { invalid json }
                    </script>
                </head>
                <body>Content</body>
            </html>
        ';

        // Mock all other services
        $this->pageSpeedService->shouldReceive('analyze')->andReturn(['performance_score' => 85, 'recommendations' => []]);
        $this->pageSpeedService->shouldReceive('analyzeMobile')->andReturn(['mobile_score' => 90, 'recommendations' => []]);
        $this->securityService->shouldReceive('analyze')->andReturn(['security_score' => 75, 'recommendations' => []]);
        $this->sitemapAnalyzerService->shouldReceive('analyze')->andReturn(['sitemap_score' => 80, 'recommendations' => []]);
        $this->canonicalUrlService->shouldReceive('analyze')->andReturn(['canonical_score' => 70, 'recommendations' => []]);

        $result = $this->technicalSeoService->analyze($url, $html);

        // Should not crash and return empty JSON-LD array
        $this->assertEmpty($result['structured_data']['json_ld']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}