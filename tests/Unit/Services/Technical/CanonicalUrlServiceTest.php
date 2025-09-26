<?php

namespace Tests\Unit\Services\Technical;

use Tests\TestCase;
use App\Services\Technical\CanonicalUrlService;
use Illuminate\Support\Facades\Http;
use Mockery;

class CanonicalUrlServiceTest extends TestCase
{
    private CanonicalUrlService $canonicalUrlService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->canonicalUrlService = new CanonicalUrlService();
    }

    public function test_analyze_with_valid_self_referencing_canonical()
    {
        $url = 'https://example.com/page';
        $html = '<html><head><link rel="canonical" href="https://example.com/page"></head><body>Content</body></html>';

        Http::shouldReceive('timeout')->once()->andReturnSelf();
        Http::shouldReceive('head')->once()->andReturn(Http::response('', 200));

        $result = $this->canonicalUrlService->analyze($url, $html);

        $this->assertIsArray($result);
        $this->assertGreaterThan(70, $result['canonical_score']);
        $this->assertTrue($result['canonical_analysis']['has_canonical']);
        $this->assertTrue($result['canonical_analysis']['is_self_referencing']);
        $this->assertEmpty($result['canonical_analysis']['canonical_issues']);
    }

    public function test_analyze_with_missing_canonical_tag()
    {
        $url = 'https://example.com/page';
        $html = '<html><head><title>Page</title></head><body>Content</body></html>';

        $result = $this->canonicalUrlService->analyze($url, $html);

        $this->assertFalse($result['canonical_analysis']['has_canonical']);
        $this->assertContains(
            'Missing canonical tag',
            array_column($result['recommendations'], 'message')
        );
    }

    public function test_analyze_with_multiple_canonical_tags()
    {
        $url = 'https://example.com/page';
        $html = '
            <html>
                <head>
                    <link rel="canonical" href="https://example.com/page">
                    <link rel="canonical" href="https://example.com/other">
                </head>
                <body>Content</body>
            </html>
        ';

        Http::shouldReceive('timeout')->once()->andReturnSelf();
        Http::shouldReceive('head')->once()->andReturn(Http::response('', 200));

        $result = $this->canonicalUrlService->analyze($url, $html);

        $this->assertTrue($result['canonical_analysis']['multiple_canonicals']);
        $this->assertContains('Multiple canonical tags found (2)', $result['canonical_analysis']['canonical_issues']);
        $this->assertContains(
            'Multiple canonical tags found',
            array_column($result['recommendations'], 'message')
        );
    }

    public function test_analyze_with_relative_canonical_url()
    {
        $url = 'https://example.com/category/page';
        $html = '<html><head><link rel="canonical" href="/category/page"></head><body>Content</body></html>';

        Http::shouldReceive('timeout')->once()->andReturnSelf();
        Http::shouldReceive('head')->once()->andReturn(Http::response('', 200));

        $result = $this->canonicalUrlService->analyze($url, $html);

        $this->assertTrue($result['canonical_analysis']['relative_canonical']);
        $this->assertTrue($result['canonical_analysis']['is_self_referencing']);
    }

    public function test_analyze_with_invalid_canonical_url()
    {
        $url = 'https://example.com/page';
        $html = '<html><head><link rel="canonical" href="invalid-url"></head><body>Content</body></html>';

        $result = $this->canonicalUrlService->analyze($url, $html);

        $this->assertContains('Canonical URL is relative but not absolute path', $result['canonical_analysis']['canonical_issues']);
        $this->assertContains(
            'Canonical issue',
            array_column($result['recommendations'], 'message')[0]
        );
    }

    public function test_analyze_with_inaccessible_canonical_url()
    {
        $url = 'https://example.com/page';
        $html = '<html><head><link rel="canonical" href="https://example.com/not-found"></head><body>Content</body></html>';

        Http::shouldReceive('timeout')->once()->andReturnSelf();
        Http::shouldReceive('head')->once()->andReturn(Http::response('', 404));

        $result = $this->canonicalUrlService->analyze($url, $html);

        $this->assertFalse($result['canonical_analysis']['canonical_accessible']);
        $this->assertContains('Canonical URL returns HTTP 404', $result['canonical_analysis']['canonical_issues']);
    }

    public function test_analyze_with_protocol_mismatch()
    {
        $url = 'https://example.com/page';
        $html = '<html><head><link rel="canonical" href="http://example.com/page"></head><body>Content</body></html>';

        Http::shouldReceive('timeout')->once()->andReturnSelf();
        Http::shouldReceive('head')->once()->andReturn(Http::response('', 200));

        $result = $this->canonicalUrlService->analyze($url, $html);

        $this->assertTrue($result['canonical_analysis']['protocol_mismatch']);
        $this->assertContains('Canonical points to HTTP while current page is HTTPS', $result['canonical_analysis']['canonical_issues']);
    }

    public function test_analyze_with_domain_mismatch()
    {
        $url = 'https://example.com/page';
        $html = '<html><head><link rel="canonical" href="https://other-domain.com/page"></head><body>Content</body></html>';

        Http::shouldReceive('timeout')->once()->andReturnSelf();
        Http::shouldReceive('head')->once()->andReturn(Http::response('', 200));

        $result = $this->canonicalUrlService->analyze($url, $html);

        $this->assertTrue($result['canonical_analysis']['domain_mismatch']);
        $this->assertContains('Canonical URL points to different domain', $result['canonical_analysis']['canonical_issues']);
    }

    public function test_url_structure_analysis()
    {
        $url = 'https://example.com/category/sub-category/product-name?sort=price&filter=color#reviews';
        $html = '<html><head><link rel="canonical" href="https://example.com/category/sub-category/product-name"></head><body>Content</body></html>';

        Http::shouldReceive('timeout')->once()->andReturnSelf();
        Http::shouldReceive('head')->once()->andReturn(Http::response('', 200));

        $result = $this->canonicalUrlService->analyze($url, $html);

        $urlStructure = $result['url_structure'];
        $this->assertEquals(3, $urlStructure['path_depth']);
        $this->assertTrue($urlStructure['has_query_parameters']);
        $this->assertEquals(2, $urlStructure['parameter_count']);
        $this->assertTrue($urlStructure['has_fragment']);
        $this->assertEquals('reviews', $urlStructure['fragment']);
        $this->assertTrue($urlStructure['seo_friendly']); // Contains hyphens
    }

    public function test_url_structure_with_uppercase_and_special_chars()
    {
        $url = 'https://example.com/Category/Page With Spaces';
        $html = '<html><head></head><body>Content</body></html>';

        $result = $this->canonicalUrlService->analyze($url, $html);

        $urlStructure = $result['url_structure'];
        $this->assertTrue($urlStructure['contains_uppercase']);
        $this->assertTrue($urlStructure['contains_spaces']);
        $this->assertFalse($urlStructure['seo_friendly']);

        $this->assertContains(
            'URL contains uppercase letters',
            array_column($result['recommendations'], 'message')
        );
    }

    public function test_generate_url_variations()
    {
        $url = 'https://www.example.com/page';
        $html = '<html><head></head><body>Content</body></html>';

        $result = $this->canonicalUrlService->analyze($url, $html);

        $variations = $result['duplicate_content']['url_variations'];
        $this->assertArrayHasKey('without_www', $variations);
        $this->assertArrayHasKey('with_trailing_slash', $variations);
        $this->assertArrayHasKey('http_version', $variations);
        $this->assertEquals('https://example.com/page', $variations['without_www']);
        $this->assertEquals('https://www.example.com/page/', $variations['with_trailing_slash']);
        $this->assertEquals('http://www.example.com/page', $variations['http_version']);
    }

    public function test_parameter_duplication_analysis()
    {
        $url = 'https://example.com/search?q=test&sort=date&utm_source=google&utm_campaign=ads&session=abc123';
        $html = '<html><head></head><body>Content</body></html>';

        $result = $this->canonicalUrlService->analyze($url, $html);

        $paramAnalysis = $result['parameter_analysis']['parameter_analysis'];
        $this->assertEquals(5, $paramAnalysis['total_parameters']);
        $this->assertContains('q', $paramAnalysis['seo_parameters']);
        $this->assertContains('sort', $paramAnalysis['seo_parameters']);
        $this->assertContains('utm_source', $paramAnalysis['tracking_parameters']);
        $this->assertContains('utm_campaign', $paramAnalysis['tracking_parameters']);
        $this->assertContains('session', $paramAnalysis['session_parameters']);
        $this->assertEquals('high', $paramAnalysis['duplication_risk']);
    }

    public function test_redirect_chain_analysis()
    {
        $url = 'https://example.com/page';
        $html = '<html><head></head><body>Content</body></html>';

        // Mock redirect chain: 301 -> 302 -> 200
        Http::shouldReceive('timeout')->times(3)->andReturnSelf();
        Http::shouldReceive('withoutRedirecting')->times(3)->andReturnSelf();
        Http::shouldReceive('get')
            ->with($url)
            ->once()
            ->andReturn(Http::response('', 301, ['Location' => 'https://example.com/redirect1']));
        Http::shouldReceive('get')
            ->with('https://example.com/redirect1')
            ->once()
            ->andReturn(Http::response('', 302, ['Location' => 'https://example.com/final']));
        Http::shouldReceive('get')
            ->with('https://example.com/final')
            ->once()
            ->andReturn(Http::response('', 200));

        $result = $this->canonicalUrlService->analyze($url, $html);

        $redirectAnalysis = $result['redirect_analysis'];
        $this->assertTrue($redirectAnalysis['has_redirects']);
        $this->assertEquals(2, $redirectAnalysis['redirect_count']);
        $this->assertEquals('https://example.com/final', $redirectAnalysis['final_url']);
        $this->assertCount(3, $redirectAnalysis['redirect_chain']);
        $this->assertContains(301, $redirectAnalysis['redirect_types']);
        $this->assertContains(302, $redirectAnalysis['redirect_types']);
    }

    public function test_redirect_chain_with_relative_location()
    {
        $url = 'https://example.com/page';
        $html = '<html><head></head><body>Content</body></html>';

        Http::shouldReceive('timeout')->twice()->andReturnSelf();
        Http::shouldReceive('withoutRedirecting')->twice()->andReturnSelf();
        Http::shouldReceive('get')
            ->with($url)
            ->once()
            ->andReturn(Http::response('', 301, ['Location' => '/new-page']));
        Http::shouldReceive('get')
            ->with('https://example.com/new-page')
            ->once()
            ->andReturn(Http::response('', 200));

        $result = $this->canonicalUrlService->analyze($url, $html);

        $redirectAnalysis = $result['redirect_analysis'];
        $this->assertEquals('https://example.com/new-page', $redirectAnalysis['final_url']);
    }

    public function test_canonical_score_calculation_perfect_scenario()
    {
        $url = 'https://example.com/seo-friendly-url';
        $html = '<html><head><link rel="canonical" href="https://example.com/seo-friendly-url"></head><body>Content</body></html>';

        Http::shouldReceive('timeout')->twice()->andReturnSelf();
        Http::shouldReceive('head')->once()->andReturn(Http::response('', 200));
        Http::shouldReceive('withoutRedirecting')->once()->andReturnSelf();
        Http::shouldReceive('get')->once()->andReturn(Http::response('', 200));

        $result = $this->canonicalUrlService->analyze($url, $html);

        // Should get high score for:
        // - Having canonical tag (20%)
        // - No canonical issues (20%)
        // - Self-referencing canonical (10%)
        // - SEO-friendly URL (15%)
        // - Good readability score (15%)
        // - No redirects (20%)
        // - Low duplication risk (10%)
        $this->assertGreaterThanOrEqual(95, $result['canonical_score']);
    }

    public function test_canonical_score_calculation_poor_scenario()
    {
        $url = 'https://example.com/Category/Page?ID=123&Session=ABC';
        $html = '<html><head></head><body>Content</body></html>';

        $result = $this->canonicalUrlService->analyze($url, $html);

        // Should get low score for:
        // - No canonical tag (0% of 50%)
        // - Non-SEO-friendly URL (poor readability)
        // - Multiple parameters with session info (high duplication risk)
        $this->assertLessThan(50, $result['canonical_score']);
    }

    public function test_url_readability_score_calculation()
    {
        $reflection = new \ReflectionClass($this->canonicalUrlService);
        $method = $reflection->getMethod('calculateUrlReadabilityScore');
        $method->setAccessible(true);

        $testCases = [
            'https://example.com/good-url' => 100, // Perfect URL
            'https://example.com/very/deep/path/with/many/segments/here' => 80, // Deep path penalty
            'https://example.com/URL-With-UPPERCASE' => 85, // Special chars penalty
            'https://example.com/page?param1=value1&param2=value2&param3=value3' => 76, // Parameter penalty
        ];

        foreach ($testCases as $url => $expectedMinScore) {
            $score = $method->invoke($this->canonicalUrlService, $url);
            $this->assertGreaterThanOrEqual($expectedMinScore - 10, $score, "URL: {$url}");
        }
    }

    public function test_seo_friendly_url_detection()
    {
        $reflection = new \ReflectionClass($this->canonicalUrlService);
        $method = $reflection->getMethod('isUrlSeoFriendly');
        $method->setAccessible(true);

        $seoFriendlyUrls = [
            'https://example.com/seo-friendly-url',
            'https://example.com/product/best-laptop-2024',
            'https://example.com/category/electronics'
        ];

        $nonSeoFriendlyUrls = [
            'https://example.com/product?id=123',
            'https://example.com/Category/Product',
            'https://example.com/123456'
        ];

        foreach ($seoFriendlyUrls as $url) {
            $this->assertTrue($method->invoke($this->canonicalUrlService, $url), "URL should be SEO friendly: {$url}");
        }

        foreach ($nonSeoFriendlyUrls as $url) {
            $this->assertFalse($method->invoke($this->canonicalUrlService, $url), "URL should not be SEO friendly: {$url}");
        }
    }

    public function test_parameter_categorization()
    {
        $reflection = new \ReflectionClass($this->canonicalUrlService);
        $method = $reflection->getMethod('categorizeParameters');
        $method->setAccessible(true);

        $parameters = [
            'q' => 'search term',
            'page' => '2',
            'utm_source' => 'google',
            'utm_campaign' => 'summer_sale',
            'session' => 'abc123',
            'csrf' => 'token456',
            'share' => 'facebook',
            'custom' => 'value'
        ];

        $result = $method->invoke($this->canonicalUrlService, $parameters);

        $this->assertContains('q', $result['seo']);
        $this->assertContains('page', $result['seo']);
        $this->assertContains('utm_source', $result['tracking']);
        $this->assertContains('utm_campaign', $result['tracking']);
        $this->assertContains('session', $result['session']);
        $this->assertContains('csrf', $result['session']);
        $this->assertContains('share', $result['social']);
        $this->assertContains('custom', $result['other']);
    }

    public function test_content_hashing_for_duplicate_detection()
    {
        $url = 'https://example.com/page';
        $html = '
            <html>
                <head>
                    <title>Page Title</title>
                    <meta name="description" content="Page description">
                </head>
                <body>
                    <h1>Main Content</h1>
                    <p>Some content here</p>
                </body>
            </html>
        ';

        $result = $this->canonicalUrlService->analyze($url, $html);

        $duplicateContent = $result['duplicate_content'];
        $this->assertNotNull($duplicateContent['content_hash']);
        $this->assertNotNull($duplicateContent['title_hash']);
        $this->assertNotNull($duplicateContent['meta_description_hash']);
        $this->assertEquals(md5('Page Title'), $duplicateContent['title_hash']);
        $this->assertEquals(md5('Page description'), $duplicateContent['meta_description_hash']);
    }

    public function test_analyze_with_invalid_html()
    {
        $url = 'https://example.com/page';
        $html = 'invalid html content';

        $result = $this->canonicalUrlService->analyze($url, $html);

        $this->assertNotEmpty($result['recommendations']);
        $this->assertContains(
            'Unable to parse HTML for canonical analysis',
            array_column($result['recommendations'], 'message')
        );
    }

    public function test_long_url_recommendation()
    {
        $longUrl = 'https://example.com/' . str_repeat('very-long-path-segment/', 10) . 'final-page';
        $html = '<html><head></head><body>Content</body></html>';

        $result = $this->canonicalUrlService->analyze($longUrl, $html);

        $this->assertContains(
            'URL is quite long',
            array_column($result['recommendations'], 'message')
        );
    }

    public function test_high_duplication_risk_recommendation()
    {
        $url = 'https://example.com/page?utm_source=google&utm_medium=cpc&utm_campaign=test&session=123&csrf=abc';
        $html = '<html><head></head><body>Content</body></html>';

        $result = $this->canonicalUrlService->analyze($url, $html);

        $this->assertContains(
            'High duplicate content risk from URL parameters',
            array_column($result['recommendations'], 'message')
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}