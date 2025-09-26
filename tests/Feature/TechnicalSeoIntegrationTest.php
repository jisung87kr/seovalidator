<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\Technical\TechnicalSeoService;
use App\Services\Technical\PageSpeedService;
use App\Services\Technical\SecurityService;
use App\Services\Technical\SitemapAnalyzerService;
use App\Services\Technical\CanonicalUrlService;

class TechnicalSeoIntegrationTest extends TestCase
{
    public function test_technical_seo_service_can_be_instantiated()
    {
        $service = app(TechnicalSeoService::class);
        $this->assertInstanceOf(TechnicalSeoService::class, $service);
    }

    public function test_page_speed_service_can_be_instantiated()
    {
        $service = app(PageSpeedService::class);
        $this->assertInstanceOf(PageSpeedService::class, $service);
    }

    public function test_security_service_can_be_instantiated()
    {
        $service = app(SecurityService::class);
        $this->assertInstanceOf(SecurityService::class, $service);
    }

    public function test_sitemap_analyzer_service_can_be_instantiated()
    {
        $service = app(SitemapAnalyzerService::class);
        $this->assertInstanceOf(SitemapAnalyzerService::class, $service);
    }

    public function test_canonical_url_service_can_be_instantiated()
    {
        $service = app(CanonicalUrlService::class);
        $this->assertInstanceOf(CanonicalUrlService::class, $service);
    }

    public function test_technical_seo_analysis_basic_functionality()
    {
        config(['services.google.pagespeed_api_key' => '']); // Disable API for test

        $service = app(TechnicalSeoService::class);

        $html = '
            <html>
                <head>
                    <title>Test Page</title>
                    <meta name="viewport" content="width=device-width, initial-scale=1">
                    <link rel="canonical" href="https://example.com/test">
                    <script type="application/ld+json">
                    {
                        "@context": "https://schema.org",
                        "@type": "Organization",
                        "name": "Test Company"
                    }
                    </script>
                </head>
                <body>
                    <h1>Test Content</h1>
                </body>
            </html>
        ';

        $options = [
            'include_page_speed' => true,
            'include_mobile_analysis' => true,
            'include_security_analysis' => false, // Disable to avoid network calls
            'include_sitemap_analysis' => false,  // Disable to avoid network calls
            'include_canonical_analysis' => false, // Disable to avoid network calls
            'include_structured_data' => true
        ];

        $result = $service->analyze('https://example.com/test', $html, $options);

        $this->assertIsArray($result);
        $this->assertEquals('https://example.com/test', $result['url']);
        $this->assertArrayHasKey('analyzed_at', $result);
        $this->assertArrayHasKey('technical_score', $result);
        $this->assertArrayHasKey('page_speed', $result);
        $this->assertArrayHasKey('mobile_optimization', $result);
        $this->assertArrayHasKey('structured_data', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('errors', $result);

        // Check structured data parsing
        $this->assertNotEmpty($result['structured_data']['json_ld']);
        $this->assertEquals('Organization', $result['structured_data']['json_ld'][0]['@type']);
        $this->assertEquals('Test Company', $result['structured_data']['json_ld'][0]['name']);

        // Check mobile analysis
        $this->assertArrayHasKey('mobile_score', $result['mobile_optimization']);
        $this->assertArrayHasKey('viewport_configuration', $result['mobile_optimization']);
        $this->assertTrue($result['mobile_optimization']['viewport_configuration']['has_viewport_meta']);

        // Check page speed (fallback mode)
        $this->assertArrayHasKey('performance_score', $result['page_speed']);
        $this->assertFalse($result['page_speed']['api_available']);
        $this->assertTrue($result['page_speed']['fallback_analysis']);
    }

    public function test_canonical_url_service_basic_functionality()
    {
        $service = app(CanonicalUrlService::class);

        $html = '
            <html>
                <head>
                    <link rel="canonical" href="https://example.com/test">
                </head>
                <body>Content</body>
            </html>
        ';

        $options = []; // No network calls for basic test

        $result = $service->analyze('https://example.com/test', $html, $options);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('canonical_analysis', $result);
        $this->assertArrayHasKey('url_structure', $result);
        $this->assertArrayHasKey('duplicate_content', $result);
        $this->assertArrayHasKey('canonical_score', $result);

        // Check canonical analysis
        $this->assertTrue($result['canonical_analysis']['has_canonical']);
        $this->assertEquals('https://example.com/test', $result['canonical_analysis']['canonical_url']);

        // Check URL structure
        $this->assertArrayHasKey('url_length', $result['url_structure']);
        $this->assertArrayHasKey('seo_friendly', $result['url_structure']);
        $this->assertArrayHasKey('readability_score', $result['url_structure']);
    }

    public function test_page_speed_service_mobile_analysis()
    {
        $service = app(PageSpeedService::class);

        $html = '
            <html>
                <head>
                    <meta name="viewport" content="width=device-width, initial-scale=1">
                    <style>
                        @media (max-width: 768px) {
                            body { font-size: 14px; }
                        }
                    </style>
                </head>
                <body>
                    <img src="test.jpg" srcset="test-small.jpg 400w, test-large.jpg 800w">
                    <button>Click me</button>
                </body>
            </html>
        ';

        $result = $service->analyzeMobile('https://example.com', $html);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('mobile_score', $result);
        $this->assertArrayHasKey('mobile_friendly', $result);
        $this->assertArrayHasKey('viewport_configuration', $result);
        $this->assertArrayHasKey('responsive_design', $result);

        // Check viewport configuration
        $this->assertTrue($result['viewport_configuration']['has_viewport_meta']);
        $this->assertTrue($result['viewport_configuration']['width_device_width']);

        // Check responsive design detection
        $this->assertGreaterThan(0, $result['responsive_design']['media_queries']);
        $this->assertGreaterThan(0, $result['responsive_design']['responsive_images']['total']);
    }

    public function test_structured_data_validation()
    {
        $service = app(TechnicalSeoService::class);

        $htmlWithValidSchema = '
            <html>
                <head>
                    <script type="application/ld+json">
                    {
                        "@context": "https://schema.org",
                        "@type": "Article",
                        "headline": "Test Article",
                        "author": "Test Author",
                        "datePublished": "2024-01-01"
                    }
                    </script>
                </head>
                <body>Content</body>
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

        $result = $service->analyze('https://example.com', $htmlWithValidSchema, $options);

        $structuredData = $result['structured_data'];
        $this->assertNotEmpty($structuredData['json_ld']);
        $this->assertEquals('Article', $structuredData['json_ld'][0]['@type']);
        $this->assertTrue($structuredData['rich_snippets_eligible']);

        // Check validation
        $this->assertNotEmpty($structuredData['schema_validation']);
        $validation = $structuredData['schema_validation'][0];
        $this->assertTrue($validation['is_valid']);
        $this->assertEmpty($validation['errors']);
    }

    public function test_structured_data_with_invalid_schema()
    {
        $service = app(TechnicalSeoService::class);

        $htmlWithInvalidSchema = '
            <html>
                <head>
                    <script type="application/ld+json">
                    {
                        "@type": "Organization"
                    }
                    </script>
                </head>
                <body>Content</body>
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

        $result = $service->analyze('https://example.com', $htmlWithInvalidSchema, $options);

        $structuredData = $result['structured_data'];
        $this->assertNotEmpty($structuredData['schema_validation']);
        $validation = $structuredData['schema_validation'][0];
        $this->assertFalse($validation['is_valid']);
        $this->assertContains('Missing @context property', $validation['errors']);
    }

    public function test_microdata_extraction()
    {
        $service = app(TechnicalSeoService::class);

        $htmlWithMicrodata = '
            <html>
                <head></head>
                <body>
                    <div itemscope itemtype="https://schema.org/LocalBusiness">
                        <span itemprop="name">Local Business Name</span>
                        <span itemprop="address">123 Main St</span>
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

        $result = $service->analyze('https://example.com', $htmlWithMicrodata, $options);

        $structuredData = $result['structured_data'];
        $this->assertNotEmpty($structuredData['microdata']);
        $this->assertEquals('https://schema.org/LocalBusiness', $structuredData['microdata'][0]['itemtype']);
        $this->assertEquals('LocalBusiness', $structuredData['microdata'][0]['schema_type']);
    }

    public function test_technical_seo_score_calculation()
    {
        config(['services.google.pagespeed_api_key' => '']); // Disable API for test

        $service = app(TechnicalSeoService::class);

        $goodHtml = '
            <html>
                <head>
                    <title>SEO Optimized Page</title>
                    <meta name="viewport" content="width=device-width, initial-scale=1">
                    <link rel="canonical" href="https://example.com/seo-page">
                    <script type="application/ld+json">
                    {
                        "@context": "https://schema.org",
                        "@type": "Organization",
                        "name": "Test Company",
                        "url": "https://example.com"
                    }
                    </script>
                </head>
                <body>
                    <h1>Great Content</h1>
                </body>
            </html>
        ';

        $options = [
            'include_page_speed' => true,
            'include_mobile_analysis' => true,
            'include_security_analysis' => false,
            'include_sitemap_analysis' => false,
            'include_canonical_analysis' => false,
            'include_structured_data' => true
        ];

        $result = $service->analyze('https://example.com/seo-page', $goodHtml, $options);

        $this->assertIsInt($result['technical_score']);
        $this->assertGreaterThanOrEqual(0, $result['technical_score']);
        $this->assertLessThanOrEqual(100, $result['technical_score']);

        // Should have relatively good score for good HTML structure
        $this->assertGreaterThan(40, $result['technical_score']);
    }
}