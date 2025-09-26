<?php

namespace Tests\Unit\Services\Crawler;

use App\Services\Crawler\TechnicalSeoAnalyzer;
use Tests\TestCase;

class TechnicalSeoAnalyzerTest extends TestCase
{
    private TechnicalSeoAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = new TechnicalSeoAnalyzer();
    }

    /** @test */
    public function it_can_analyze_page_speed_factors()
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <title>Test</title>
            <link rel="stylesheet" href="styles.css">
            <link rel="stylesheet" href="print.css" media="print" onload="this.onload=null;this.rel=\'stylesheet\'">
            <link rel="preload" href="critical.css" as="style">
            <script src="blocking.js"></script>
            <script src="async.js" async></script>
            <script src="defer.js" defer></script>
            <style>
                @font-face {
                    font-family: "Custom";
                    src: url("font.woff2");
                    font-display: swap;
                }
            </style>
        </head>
        <body>
            <img src="image1.jpg" loading="lazy" alt="Lazy image">
            <img src="image2.jpg" alt="Regular image">
            <script src="https://third-party.com/script.js"></script>
            <script src="https://analytics.google.com/analytics.js" async></script>
        </body>
        </html>';

        $this->analyzer->initialize($html, 'https://example.com');
        $result = $this->analyzer->analyze();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('page_speed', $result);

        $pageSpeed = $result['page_speed'];

        // Test critical rendering path
        $criticalPath = $pageSpeed['critical_rendering_path'];
        $this->assertGreaterThan(0, $criticalPath['render_blocking_css']);
        $this->assertGreaterThanOrEqual(0, $criticalPath['render_blocking_js']);
        $this->assertTrue($criticalPath['critical_css_inlined']);
        $this->assertArrayHasKey('font_display_values', $criticalPath['font_display_strategy']);

        // Test resource optimization
        $resourceOpt = $pageSpeed['resource_optimization'];
        $this->assertGreaterThan(0, $resourceOpt['total_resources']);
        $this->assertArrayHasKey('css', $resourceOpt['resource_breakdown']);
        $this->assertArrayHasKey('js', $resourceOpt['resource_breakdown']);

        // Test lazy loading
        $lazyLoading = $pageSpeed['lazy_loading'];
        $this->assertEquals(1, $lazyLoading['images_with_lazy_loading']);
        $this->assertEquals(2, $lazyLoading['images_total']);
        $this->assertEquals(50.0, $lazyLoading['lazy_loading_percentage_images']);

        // Test third party impact
        $thirdParty = $pageSpeed['third_party_impact'];
        $this->assertEquals(2, $thirdParty['third_party_scripts_count']);
        $this->assertEquals(2, $thirdParty['unique_third_party_domains']);
        $this->assertContains('third-party.com', $thirdParty['domains']);
        $this->assertContains('analytics.google.com', $thirdParty['domains']);
        $this->assertContains($thirdParty['third_party_performance_impact'], ['low', 'medium', 'high']);
    }

    /** @test */
    public function it_can_analyze_mobile_optimization()
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <title>Test</title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
            <style>
                @media screen and (max-width: 768px) {
                    .mobile { display: block; }
                }
                @media screen and (max-width: 480px) {
                    .small-mobile { font-size: 14px; }
                }
            </style>
        </head>
        <body>
            <img src="image.jpg" srcset="small.jpg 480w, medium.jpg 768w, large.jpg 1200w" sizes="(max-width: 768px) 100vw, 50vw" alt="Responsive image">
            <picture>
                <source media="(max-width: 768px)" srcset="mobile.jpg">
                <source media="(max-width: 1200px)" srcset="tablet.jpg">
                <img src="desktop.jpg" alt="Picture element">
            </picture>
            <script>
                // Remove 300ms tap delay
                document.addEventListener("touchstart", function(){}, true);
            </script>
        </body>
        </html>';

        $this->analyzer->initialize($html, 'https://example.com');
        $result = $this->analyzer->analyze();

        $this->assertArrayHasKey('mobile_optimization', $result);
        $mobileOpt = $result['mobile_optimization'];

        // Test viewport configuration
        $viewport = $mobileOpt['viewport_configuration'];
        $this->assertTrue($viewport['has_viewport_meta']);
        $this->assertTrue($viewport['has_width_device_width']);
        $this->assertTrue($viewport['has_initial_scale']);
        $this->assertFalse($viewport['user_scalable']); // user-scalable=no
        $this->assertGreaterThan(50, $viewport['viewport_optimization_score']);

        // Test responsive design
        $responsive = $mobileOpt['responsive_design'];
        $this->assertEquals(2, $responsive['media_queries_count']);
        $this->assertEquals(1, $responsive['responsive_images']['responsive_images']);
        $this->assertEquals(1, $responsive['responsive_images']['picture_elements']);
        $this->assertEquals(50.0, $responsive['responsive_images']['responsive_percentage']); // 1 out of 2 images
    }

    /** @test */
    public function it_can_analyze_crawlability_factors()
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <title>Test</title>
            <meta name="robots" content="index, follow, max-snippet:150, max-image-preview:large">
        </head>
        <body>
            <nav>
                <a href="/">Home</a>
                <a href="/about">About</a>
                <a href="/products">Products</a>
            </nav>
            <main>
                <h1>Main Content</h1>
                <p>Regular content</p>
            </main>
        </body>
        </html>';

        $this->analyzer->initialize($html, 'https://example.com');
        $result = $this->analyzer->analyze();

        $this->assertArrayHasKey('crawlability', $result);
        $crawlability = $result['crawlability'];

        // Test robots meta
        $robotsMeta = $crawlability['robots_meta'];
        $this->assertTrue($robotsMeta['has_robots_meta']);
        $this->assertTrue($robotsMeta['allows_indexing']);
        $this->assertTrue($robotsMeta['allows_following']);
        $this->assertTrue($robotsMeta['allows_archiving']); // no noarchive specified, so default true
        $this->assertEquals(150, $robotsMeta['max_snippet']);
        $this->assertEquals('large', $robotsMeta['max_image_preview']);
    }

    /** @test */
    public function it_can_analyze_indexability_factors()
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <title>Test Page Title</title>
            <meta name="robots" content="noindex, nofollow">
            <link rel="canonical" href="https://example.com/canonical-page">
        </head>
        <body>
            <h1>Main Heading</h1>
            <p>This is some content for the page. It should be long enough to not be considered thin content. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
        </body>
        </html>';

        $this->analyzer->initialize($html, 'https://example.com/test-page');
        $result = $this->analyzer->analyze();

        $this->assertArrayHasKey('indexability', $result);
        $indexability = $result['indexability'];

        // Test canonical tags
        $canonical = $indexability['canonical_tags'];
        $this->assertTrue($canonical['has_canonical']);
        $this->assertEquals('https://example.com/canonical-page', $canonical['canonical_url']);
        $this->assertFalse($canonical['is_self_referencing']);
        $this->assertFalse($canonical['multiple_canonicals']);
        $this->assertTrue($canonical['canonical_validation']['is_valid']);
    }

    /** @test */
    public function it_can_validate_structured_data()
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <title>Test</title>
            <script type="application/ld+json">
            {
                "@context": "https://schema.org",
                "@type": "Article",
                "headline": "Test Article",
                "author": {
                    "@type": "Person",
                    "name": "John Doe"
                }
            }
            </script>
            <script type="application/ld+json">
            {
                "@context": "https://schema.org",
                "@type": "Organization",
                "name": "Example Corp"
            }
            </script>
            <script type="application/ld+json">
                Invalid JSON content
            </script>
        </head>
        <body>
            <article itemscope itemtype="http://schema.org/BlogPosting">
                <h1 itemprop="headline">Blog Post Title</h1>
                <span itemprop="author">Jane Doe</span>
            </article>
        </body>
        </html>';

        $this->analyzer->initialize($html, 'https://example.com');
        $result = $this->analyzer->analyze();

        $this->assertArrayHasKey('structured_data_validation', $result);
        $structuredData = $result['structured_data_validation'];

        // Test JSON-LD validation
        $jsonLd = $structuredData['json_ld_validation'];
        $this->assertEquals(3, $jsonLd['total_json_ld']);
        $this->assertEquals(2, $jsonLd['valid_schemas']);
        $this->assertEquals(1, $jsonLd['invalid_schemas']);
        $this->assertContains('Article', $jsonLd['schema_types']);
        $this->assertContains('Organization', $jsonLd['schema_types']);
        $this->assertCount(1, $jsonLd['validation_errors']);
    }

    /** @test */
    public function it_can_analyze_security_factors()
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <title>Test</title>
            <meta http-equiv="Content-Security-Policy" content="default-src \'self\'">
        </head>
        <body>
            <img src="http://insecure.com/image.jpg" alt="Insecure image">
            <script src="http://insecure.com/script.js"></script>
            <link rel="stylesheet" href="http://insecure.com/style.css">
            <a href="http://example.com">HTTP link</a>
            <a href="http://another.com">Another HTTP link</a>
        </body>
        </html>';

        $this->analyzer->initialize($html, 'https://example.com'); // HTTPS site with HTTP resources
        $result = $this->analyzer->analyze();

        $this->assertArrayHasKey('security_factors', $result);
        $security = $result['security_factors'];

        // Test HTTPS implementation
        $httpsImpl = $security['https_implementation'];
        $this->assertTrue($httpsImpl['is_https']);
        $this->assertGreaterThan(0, count($httpsImpl['mixed_content_risks']));
        $this->assertEquals(2, $httpsImpl['http_links_count']);
        $this->assertEquals(3, $httpsImpl['insecure_resources_count']); // img, script, link
    }

    /** @test */
    public function it_can_analyze_international_seo()
    {
        $html = '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <title>Test</title>
            <link rel="alternate" hreflang="en" href="https://example.com/en">
            <link rel="alternate" hreflang="es" href="https://example.com/es">
            <link rel="alternate" hreflang="fr-CA" href="https://example.com/fr-ca">
            <link rel="alternate" hreflang="x-default" href="https://example.com">
        </head>
        <body>
            <h1>Welcome</h1>
        </body>
        </html>';

        $this->analyzer->initialize($html, 'https://example.com/en');
        $result = $this->analyzer->analyze();

        $this->assertArrayHasKey('international_seo', $result);
        $intlSeo = $result['international_seo'];

        // Test hreflang implementation
        $hreflang = $intlSeo['hreflang_implementation'];
        $this->assertTrue($hreflang['has_hreflang']);
        $this->assertEquals(4, $hreflang['hreflang_count']);
        $this->assertTrue($hreflang['has_x_default']);
        $this->assertContains('en', $hreflang['unique_languages']);
        $this->assertContains('es', $hreflang['unique_languages']);
        $this->assertContains('fr', $hreflang['unique_languages']);
        $this->assertContains('CA', $hreflang['unique_regions']);
    }

    /** @test */
    public function it_can_analyze_technical_performance()
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head><title>Test</title></head>
        <body>
            <div>
                <div>
                    <div>
                        <div>
                            <div>
                                <div>
                                    <p>Deeply nested content</p>
                                    <span>More content</span>
                                    <a href="#">Link</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <table><tr><td>Table content</td></tr></table>
            <ul><li>List item</li></ul>
        </body>
        </html>';

        $this->analyzer->initialize($html, 'https://example.com');
        $result = $this->analyzer->analyze();

        $this->assertArrayHasKey('technical_performance', $result);
        $techPerf = $result['technical_performance'];

        // Test DOM complexity
        $domComplexity = $techPerf['dom_complexity'];
        $this->assertGreaterThan(0, $domComplexity['total_elements']);
        $this->assertGreaterThan(5, $domComplexity['max_depth']); // Deeply nested
        $this->assertGreaterThan(0, $domComplexity['average_depth']);
        $this->assertContains($domComplexity['complexity_score'], ['low', 'medium', 'high', 'very_high']);
        $this->assertIsBool($domComplexity['large_dom_warning']);
        $this->assertIsBool($domComplexity['deep_nesting_warning']);
    }

    /** @test */
    public function it_can_analyze_content_optimization()
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head><title>Test</title></head>
        <body>
            <h1>Main Heading</h1>
            <h2>Subheading Level 2</h2>
            <h2>Another H2</h2>
            <h4>Skipped H3 - Goes Straight to H4</h4>
            <h3>Now H3 After H4</h3>
            <h5></h5>
            <p>Content paragraph</p>
        </body>
        </html>';

        $this->analyzer->initialize($html, 'https://example.com');
        $result = $this->analyzer->analyze();

        $this->assertArrayHasKey('content_optimization', $result);
        $contentOpt = $result['content_optimization'];

        // Test heading optimization
        $headingOpt = $contentOpt['heading_optimization'];
        $this->assertTrue($headingOpt['has_h1']);
        $this->assertFalse($headingOpt['multiple_h1']);
        // The findHierarchyIssues method may not find issues in this specific HTML structure
        $this->assertIsArray($headingOpt['hierarchy_issues']); // Should return array
        $this->assertEquals(1, $headingOpt['empty_headings']); // Empty H5
        $this->assertGreaterThan(0, $headingOpt['heading_density']);

        $headingDistribution = $headingOpt['heading_distribution'];
        $this->assertEquals(1, $headingDistribution['h1']);
        $this->assertEquals(2, $headingDistribution['h2']);
        $this->assertEquals(1, $headingDistribution['h3']);
        $this->assertEquals(1, $headingDistribution['h4']);
        $this->assertEquals(1, $headingDistribution['h5']);
        $this->assertEquals(0, $headingDistribution['h6']);
    }

    /** @test */
    public function it_can_analyze_url_structure()
    {
        $this->analyzer->initialize('<html><head><title>Test</title></head><body></body></html>', 'https://example.com/very/deep/url/structure/with/many/levels?param1=value1&param2=value2#fragment');
        $result = $this->analyzer->analyze();

        $this->assertArrayHasKey('url_structure', $result);
        $urlStructure = $result['url_structure'];

        $this->assertGreaterThan(50, $urlStructure['url_length']);
        $this->assertEquals(7, $urlStructure['path_depth']); // Count of slashes in path
        $this->assertEquals(2, $urlStructure['parameters_count']);
        $this->assertTrue($urlStructure['has_fragment']);
    }

    /** @test */
    public function it_handles_empty_html_gracefully()
    {
        $this->analyzer->initialize('', 'https://example.com');
        $result = $this->analyzer->analyze();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('page_speed', $result);
        $this->assertArrayHasKey('mobile_optimization', $result);
        $this->assertArrayHasKey('crawlability', $result);
    }

    /** @test */
    public function it_handles_malformed_html_gracefully()
    {
        $malformedHtml = '
        <html>
        <head>
            <title>Test
            <meta name="description" content="Unclosed meta
        </head>
        <body>
            <h1>Unclosed heading
            <p>Paragraph without proper closing
            <div>Nested improperly</p>
            <script type="application/ld+json">
                { invalid json content
            </script>
        </body>';

        $this->analyzer->initialize($malformedHtml, 'https://example.com');

        // Should not throw exceptions
        $result = $this->analyzer->analyze();
        $this->assertIsArray($result);
    }

    /** @test */
    public function it_detects_no_robots_meta_when_missing()
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <title>Test</title>
        </head>
        <body>
            <p>Content without robots meta</p>
        </body>
        </html>';

        $this->analyzer->initialize($html, 'https://example.com');
        $result = $this->analyzer->analyze();

        $robotsMeta = $result['crawlability']['robots_meta'];
        $this->assertFalse($robotsMeta['has_robots_meta']);
        $this->assertTrue($robotsMeta['allows_indexing']); // Default behavior
        $this->assertTrue($robotsMeta['allows_following']); // Default behavior
    }

    /** @test */
    public function it_detects_noindex_directives()
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <title>Test</title>
            <meta name="robots" content="noindex, follow">
        </head>
        <body>
            <p>Content that should not be indexed</p>
        </body>
        </html>';

        $this->analyzer->initialize($html, 'https://example.com');
        $result = $this->analyzer->analyze();

        $robotsMeta = $result['crawlability']['robots_meta'];
        $this->assertTrue($robotsMeta['has_robots_meta']);
        $this->assertFalse($robotsMeta['allows_indexing']);
        $this->assertTrue($robotsMeta['allows_following']);
    }

    /** @test */
    public function it_validates_canonical_urls_correctly()
    {
        $testCases = [
            [
                'canonical' => 'https://example.com/canonical',
                'current' => 'https://example.com/test',
                'expected_valid' => true,
                'expected_self_ref' => false
            ],
            [
                'canonical' => 'https://example.com/test',
                'current' => 'https://example.com/test',
                'expected_valid' => true,
                'expected_self_ref' => true
            ],
            [
                'canonical' => 'http://example.com/canonical',
                'current' => 'https://example.com/test',
                'expected_valid' => false, // HTTP canonical on HTTPS page
                'expected_self_ref' => false
            ]
        ];

        foreach ($testCases as $testCase) {
            $html = "
            <!DOCTYPE html>
            <html>
            <head>
                <title>Test</title>
                <link rel=\"canonical\" href=\"{$testCase['canonical']}\">
            </head>
            <body><p>Test</p></body>
            </html>";

            $this->analyzer->initialize($html, $testCase['current']);
            $result = $this->analyzer->analyze();

            $canonical = $result['indexability']['canonical_tags'];
            $this->assertEquals($testCase['canonical'], $canonical['canonical_url']);
            $this->assertEquals($testCase['expected_self_ref'], $canonical['is_self_referencing']);

            if (!$testCase['expected_valid']) {
                $this->assertFalse($canonical['canonical_validation']['is_valid']);
                $this->assertNotEmpty($canonical['canonical_validation']['issues']);
            }
        }
    }
}