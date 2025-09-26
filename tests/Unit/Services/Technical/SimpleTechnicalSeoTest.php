<?php

namespace Tests\Unit\Services\Technical;

use Tests\TestCase;
use App\Services\Technical\TechnicalSeoService;
use App\Services\Technical\PageSpeedService;
use App\Services\Technical\SecurityService;
use App\Services\Technical\SitemapAnalyzerService;
use App\Services\Technical\CanonicalUrlService;

class SimpleTechnicalSeoTest extends TestCase
{
    public function test_page_speed_service_mobile_analysis_with_good_html()
    {
        $pageSpeedService = new PageSpeedService();

        $html = '
            <html>
                <head>
                    <meta name="viewport" content="width=device-width, initial-scale=1">
                    <style>
                        @media (max-width: 768px) { body { font-size: 14px; } }
                    </style>
                </head>
                <body>
                    <img src="test.jpg" srcset="test-small.jpg 400w, test-large.jpg 800w">
                </body>
            </html>
        ';

        $result = $pageSpeedService->analyzeMobile('https://example.com', $html);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('mobile_score', $result);
        $this->assertTrue($result['viewport_configuration']['has_viewport_meta']);
        $this->assertGreaterThan(0, $result['responsive_design']['media_queries']);
    }

    public function test_canonical_url_service_basic_analysis()
    {
        $canonicalService = new CanonicalUrlService();

        $html = '
            <html>
                <head>
                    <link rel="canonical" href="https://example.com/page">
                </head>
                <body>Content</body>
            </html>
        ';

        $result = $canonicalService->analyze('https://example.com/page', $html);

        $this->assertIsArray($result);
        $this->assertTrue($result['canonical_analysis']['has_canonical']);
        $this->assertEquals('https://example.com/page', $result['canonical_analysis']['canonical_url']);
        $this->assertArrayHasKey('canonical_score', $result);
    }

    public function test_technical_seo_service_structured_data_extraction()
    {
        $pageSpeedService = new PageSpeedService();
        $securityService = new SecurityService();
        $sitemapService = new SitemapAnalyzerService();
        $canonicalService = new CanonicalUrlService();

        $technicalSeoService = new TechnicalSeoService(
            $pageSpeedService,
            $securityService,
            $sitemapService,
            $canonicalService
        );

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
                <body>
                    <div itemscope itemtype="https://schema.org/LocalBusiness">
                        <span itemprop="name">Local Business</span>
                    </div>
                </body>
            </html>
        ';

        $options = [
            'include_page_speed' => false,
            'include_mobile_analysis' => false,
            'include_security_analysis' => false,
            'include_sitemap_analysis' => false,
            'include_canonical_analysis' => false,
            'include_structured_data' => true
        ];

        $result = $technicalSeoService->analyze('https://example.com', $html, $options);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('structured_data', $result);

        // Check JSON-LD extraction
        $this->assertNotEmpty($result['structured_data']['json_ld']);
        $this->assertEquals('Organization', $result['structured_data']['json_ld'][0]['@type']);

        // Check microdata extraction
        $this->assertNotEmpty($result['structured_data']['microdata']);
        $this->assertEquals('LocalBusiness', $result['structured_data']['microdata'][0]['schema_type']);
    }

    public function test_url_structure_analysis()
    {
        $canonicalService = new CanonicalUrlService();

        // Create simple HTML without redirects to test just the URL structure analysis
        $html = '<html><head></head><body>Content</body></html>';

        // Use reflection to test just the URL structure analysis method
        $reflection = new \ReflectionClass($canonicalService);
        $method = $reflection->getMethod('analyzeUrlStructure');
        $method->setAccessible(true);

        $result = $method->invoke($canonicalService, 'https://example.com/category/product-name?sort=price&filter=color');

        $this->assertEquals(2, $result['path_depth']);
        $this->assertTrue($result['has_query_parameters']);
        $this->assertEquals(2, $result['parameter_count']);
        $this->assertTrue($result['seo_friendly']); // Contains hyphens
    }
}