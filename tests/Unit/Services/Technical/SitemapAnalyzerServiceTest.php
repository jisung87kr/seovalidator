<?php

namespace Tests\Unit\Services\Technical;

use Tests\TestCase;
use App\Services\Technical\SitemapAnalyzerService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Mockery;

class SitemapAnalyzerServiceTest extends TestCase
{
    private SitemapAnalyzerService $sitemapAnalyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sitemapAnalyzer = new SitemapAnalyzerService();
    }

    public function test_analyze_with_valid_robots_txt_and_sitemap()
    {
        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once();

        $robotsContent = "User-agent: *\nDisallow: /admin\nSitemap: https://example.com/sitemap.xml";
        $sitemapXml = '<?xml version="1.0" encoding="UTF-8"?>
            <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
                <url>
                    <loc>https://example.com/</loc>
                    <lastmod>2024-01-01</lastmod>
                    <changefreq>daily</changefreq>
                    <priority>1.0</priority>
                </url>
                <url>
                    <loc>https://example.com/about</loc>
                    <lastmod>2024-01-02</lastmod>
                    <changefreq>weekly</changefreq>
                    <priority>0.8</priority>
                </url>
            </urlset>';

        Http::shouldReceive('timeout')
            ->times(3) // robots.txt, sitemap.xml, URL accessibility test
            ->andReturnSelf();
        Http::shouldReceive('get')
            ->with('https://example.com/robots.txt')
            ->once()
            ->andReturn(Http::response($robotsContent, 200));
        Http::shouldReceive('get')
            ->with('https://example.com/sitemap.xml')
            ->once()
            ->andReturn(Http::response($sitemapXml, 200, ['Content-Type' => 'application/xml']));
        Http::shouldReceive('head')
            ->twice() // URL accessibility tests
            ->andReturn(Http::response('', 200));

        $result = $this->sitemapAnalyzer->analyze('https://example.com');

        $this->assertIsArray($result);
        $this->assertGreaterThan(70, $result['sitemap_score']);
        $this->assertTrue($result['robots_txt']['accessible']);
        $this->assertCount(1, $result['robots_txt']['sitemaps']);
        $this->assertCount(1, $result['sitemaps']);
        $this->assertEquals('urlset', $result['sitemaps'][0]['type']);
        $this->assertCount(2, $result['sitemaps'][0]['urls']);
    }

    public function test_analyze_with_missing_robots_txt()
    {
        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once();

        Http::shouldReceive('timeout')->times(5)->andReturnSelf(); // robots.txt + 4 common sitemap locations
        Http::shouldReceive('get')
            ->with('https://example.com/robots.txt')
            ->once()
            ->andReturn(Http::response('Not Found', 404));
        Http::shouldReceive('head')
            ->times(4) // Check common sitemap locations
            ->andReturn(Http::response('', 404));

        $result = $this->sitemapAnalyzer->analyze('https://example.com');

        $this->assertFalse($result['robots_txt']['accessible']);
        $this->assertEmpty($result['sitemaps']);
        $this->assertContains(
            'robots.txt file not accessible',
            array_column($result['recommendations'], 'message')
        );
    }

    public function test_robots_txt_parsing()
    {
        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once();

        $robotsContent = "# Robots file\n" .
                        "User-agent: *\n" .
                        "Disallow: /admin/\n" .
                        "Disallow: /private/\n" .
                        "Allow: /public/\n" .
                        "Crawl-delay: 10\n" .
                        "Sitemap: https://example.com/sitemap.xml\n" .
                        "Sitemap: https://example.com/sitemap-news.xml\n" .
                        "\n" .
                        "User-agent: Googlebot\n" .
                        "Disallow: /admin/\n" .
                        "Crawl-delay: 5";

        Http::shouldReceive('timeout')->once()->andReturnSelf();
        Http::shouldReceive('get')
            ->with('https://example.com/robots.txt')
            ->once()
            ->andReturn(Http::response($robotsContent, 200));

        $result = $this->sitemapAnalyzer->analyze('https://example.com');

        $robots = $result['robots_txt'];
        $this->assertTrue($robots['accessible']);
        $this->assertCount(2, $robots['sitemaps']);
        $this->assertCount(2, $robots['disallowed_paths']);
        $this->assertCount(1, $robots['allowed_paths']);
        $this->assertEquals(10, $robots['crawl_delay']);
        $this->assertArrayHasKey('*', $robots['user_agents']);
        $this->assertArrayHasKey('Googlebot', $robots['user_agents']);
        $this->assertEquals(5, $robots['user_agents']['Googlebot']['crawl_delay']);
    }

    public function test_robots_txt_syntax_errors()
    {
        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once();

        $robotsContent = "User-agent: *\n" .
                        "Disallow /admin\n" .  // Missing colon
                        "Sitemap invalid-url\n" . // Invalid URL
                        "Crawl-delay: -5\n";    // Invalid value

        Http::shouldReceive('timeout')->once()->andReturnSelf();
        Http::shouldReceive('get')
            ->with('https://example.com/robots.txt')
            ->once()
            ->andReturn(Http::response($robotsContent, 200));

        $result = $this->sitemapAnalyzer->analyze('https://example.com');

        $robots = $result['robots_txt'];
        $this->assertNotEmpty($robots['syntax_errors']);
        $this->assertContains(
            'Syntax errors found in robots.txt',
            array_column($result['recommendations'], 'message')
        );
    }

    public function test_sitemap_index_parsing()
    {
        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once();

        $robotsContent = "User-agent: *\nSitemap: https://example.com/sitemap_index.xml";
        $sitemapIndexXml = '<?xml version="1.0" encoding="UTF-8"?>
            <sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
                <sitemap>
                    <loc>https://example.com/sitemap1.xml</loc>
                    <lastmod>2024-01-01</lastmod>
                </sitemap>
                <sitemap>
                    <loc>https://example.com/sitemap2.xml</loc>
                    <lastmod>2024-01-02</lastmod>
                </sitemap>
            </sitemapindex>';

        Http::shouldReceive('timeout')->twice()->andReturnSelf();
        Http::shouldReceive('get')
            ->with('https://example.com/robots.txt')
            ->once()
            ->andReturn(Http::response($robotsContent, 200));
        Http::shouldReceive('get')
            ->with('https://example.com/sitemap_index.xml')
            ->once()
            ->andReturn(Http::response($sitemapIndexXml, 200, ['Content-Type' => 'application/xml']));

        $result = $this->sitemapAnalyzer->analyze('https://example.com');

        $this->assertCount(1, $result['sitemap_index']);
        $this->assertEquals('sitemap_index', $result['sitemap_index'][0]['type']);
        $this->assertCount(2, $result['sitemap_index'][0]['child_sitemaps']);
        $this->assertEquals(2, $result['sitemap_index'][0]['statistics']['total_child_sitemaps']);
        $this->assertEquals(2, $result['sitemap_index'][0]['statistics']['with_lastmod']);
    }

    public function test_sitemap_url_validation()
    {
        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once();

        $robotsContent = "User-agent: *\nSitemap: https://example.com/sitemap.xml";
        $sitemapXml = '<?xml version="1.0" encoding="UTF-8"?>
            <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
                <url>
                    <loc>invalid-url</loc>
                    <changefreq>invalid-freq</changefreq>
                    <priority>2.0</priority>
                    <lastmod>invalid-date</lastmod>
                </url>
                <url>
                    <loc>https://example.com/valid</loc>
                    <changefreq>daily</changefreq>
                    <priority>0.8</priority>
                    <lastmod>2024-01-01</lastmod>
                </url>
            </urlset>';

        Http::shouldReceive('timeout')->times(3)->andReturnSelf();
        Http::shouldReceive('get')
            ->with('https://example.com/robots.txt')
            ->once()
            ->andReturn(Http::response($robotsContent, 200));
        Http::shouldReceive('get')
            ->with('https://example.com/sitemap.xml')
            ->once()
            ->andReturn(Http::response($sitemapXml, 200, ['Content-Type' => 'application/xml']));
        Http::shouldReceive('head')
            ->once()
            ->andReturn(Http::response('', 200));

        $result = $this->sitemapAnalyzer->analyze('https://example.com');

        $sitemap = $result['sitemaps'][0];
        $this->assertNotEmpty($sitemap['validation']['errors']);
        $this->assertNotEmpty($sitemap['validation']['warnings']);
        $this->assertContains('Invalid URL', $sitemap['validation']['errors'][0]);
    }

    public function test_url_accessibility_analysis()
    {
        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once();

        $robotsContent = "User-agent: *\nSitemap: https://example.com/sitemap.xml";
        $sitemapXml = '<?xml version="1.0" encoding="UTF-8"?>
            <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
                <url>
                    <loc>https://example.com/accessible</loc>
                </url>
                <url>
                    <loc>https://example.com/not-found</loc>
                </url>
                <url>
                    <loc>https://example.com/server-error</loc>
                </url>
            </urlset>';

        Http::shouldReceive('timeout')->andReturnSelf();
        Http::shouldReceive('get')
            ->with('https://example.com/robots.txt')
            ->once()
            ->andReturn(Http::response($robotsContent, 200));
        Http::shouldReceive('get')
            ->with('https://example.com/sitemap.xml')
            ->once()
            ->andReturn(Http::response($sitemapXml, 200, ['Content-Type' => 'application/xml']));

        // Mock URL accessibility tests
        Http::shouldReceive('head')
            ->with('https://example.com/accessible')
            ->once()
            ->andReturn(Http::response('', 200));
        Http::shouldReceive('head')
            ->with('https://example.com/not-found')
            ->once()
            ->andReturn(Http::response('', 404));
        Http::shouldReceive('head')
            ->with('https://example.com/server-error')
            ->once()
            ->andReturn(Http::response('', 500));

        $result = $this->sitemapAnalyzer->analyze('https://example.com');

        $urlAnalysis = $result['url_analysis'];
        $this->assertEquals(3, $urlAnalysis['total_urls']);
        $this->assertEquals(3, $urlAnalysis['tested_urls']);
        $this->assertEquals(1, $urlAnalysis['accessible_urls']);
        $this->assertEquals(2, $urlAnalysis['inaccessible_urls']);
        $this->assertArrayHasKey(200, $urlAnalysis['status_code_distribution']);
        $this->assertArrayHasKey(404, $urlAnalysis['status_code_distribution']);
        $this->assertArrayHasKey(500, $urlAnalysis['status_code_distribution']);
    }

    public function test_gzipped_sitemap_decompression()
    {
        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once();

        $robotsContent = "User-agent: *\nSitemap: https://example.com/sitemap.xml.gz";
        $sitemapXml = '<?xml version="1.0" encoding="UTF-8"?>
            <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
                <url>
                    <loc>https://example.com/</loc>
                </url>
            </urlset>';
        $gzippedContent = gzencode($sitemapXml);

        Http::shouldReceive('timeout')->times(3)->andReturnSelf();
        Http::shouldReceive('get')
            ->with('https://example.com/robots.txt')
            ->once()
            ->andReturn(Http::response($robotsContent, 200));
        Http::shouldReceive('get')
            ->with('https://example.com/sitemap.xml.gz')
            ->once()
            ->andReturn(Http::response($gzippedContent, 200, ['Content-Encoding' => 'gzip']));
        Http::shouldReceive('head')
            ->once()
            ->andReturn(Http::response('', 200));

        $result = $this->sitemapAnalyzer->analyze('https://example.com');

        $this->assertCount(1, $result['sitemaps']);
        $this->assertEquals('gzip', $result['sitemaps'][0]['compression']);
        $this->assertEquals('urlset', $result['sitemaps'][0]['type']);
        $this->assertCount(1, $result['sitemaps'][0]['urls']);
    }

    public function test_common_sitemap_location_discovery()
    {
        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once();

        // No sitemap in robots.txt
        $robotsContent = "User-agent: *\nDisallow: /admin";
        $sitemapXml = '<?xml version="1.0" encoding="UTF-8"?>
            <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
                <url><loc>https://example.com/</loc></url>
            </urlset>';

        Http::shouldReceive('timeout')->andReturnSelf();
        Http::shouldReceive('get')
            ->with('https://example.com/robots.txt')
            ->once()
            ->andReturn(Http::response($robotsContent, 200));

        // Test common locations - only /sitemap.xml exists
        Http::shouldReceive('head')
            ->with('https://example.com/sitemap.xml')
            ->once()
            ->andReturn(Http::response('', 200, ['Content-Type' => 'application/xml']));
        Http::shouldReceive('head')
            ->with('https://example.com/sitemap_index.xml')
            ->once()
            ->andReturn(Http::response('', 404));
        Http::shouldReceive('head')
            ->with('https://example.com/sitemaps.xml')
            ->once()
            ->andReturn(Http::response('', 404));
        Http::shouldReceive('head')
            ->with('https://example.com/sitemap1.xml')
            ->once()
            ->andReturn(Http::response('', 404));

        // Get the found sitemap
        Http::shouldReceive('get')
            ->with('https://example.com/sitemap.xml')
            ->once()
            ->andReturn(Http::response($sitemapXml, 200, ['Content-Type' => 'application/xml']));
        Http::shouldReceive('head')
            ->once()
            ->andReturn(Http::response('', 200));

        $result = $this->sitemapAnalyzer->analyze('https://example.com');

        $this->assertCount(1, $result['sitemaps']);
        $this->assertEquals('https://example.com/sitemap.xml', $result['sitemaps'][0]['url']);
    }

    public function test_invalid_xml_handling()
    {
        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once();

        $robotsContent = "User-agent: *\nSitemap: https://example.com/sitemap.xml";
        $invalidXml = '<?xml version="1.0"?><urlset><url><loc>test</loc><unclosed-tag></url></urlset>';

        Http::shouldReceive('timeout')->twice()->andReturnSelf();
        Http::shouldReceive('get')
            ->with('https://example.com/robots.txt')
            ->once()
            ->andReturn(Http::response($robotsContent, 200));
        Http::shouldReceive('get')
            ->with('https://example.com/sitemap.xml')
            ->once()
            ->andReturn(Http::response($invalidXml, 200, ['Content-Type' => 'application/xml']));

        $result = $this->sitemapAnalyzer->analyze('https://example.com');

        $this->assertCount(1, $result['sitemaps']);
        $sitemap = $result['sitemaps'][0];
        $this->assertFalse($sitemap['validation']['valid_xml']);
        $this->assertNotEmpty($sitemap['validation']['errors']);
    }

    public function test_sitemap_score_calculation()
    {
        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once();

        $robotsContent = "User-agent: *\nSitemap: https://example.com/sitemap.xml";
        $sitemapXml = '<?xml version="1.0" encoding="UTF-8"?>
            <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
                <url>
                    <loc>https://example.com/</loc>
                    <lastmod>' . now()->format('Y-m-d') . '</lastmod>
                    <changefreq>daily</changefreq>
                    <priority>1.0</priority>
                </url>
            </urlset>';

        Http::shouldReceive('timeout')->times(3)->andReturnSelf();
        Http::shouldReceive('get')
            ->with('https://example.com/robots.txt')
            ->once()
            ->andReturn(Http::response($robotsContent, 200));
        Http::shouldReceive('get')
            ->with('https://example.com/sitemap.xml')
            ->once()
            ->andReturn(Http::response($sitemapXml, 200, ['Content-Type' => 'application/xml']));
        Http::shouldReceive('head')
            ->once()
            ->andReturn(Http::response('', 200));

        $result = $this->sitemapAnalyzer->analyze('https://example.com');

        // Should get high score for:
        // - Accessible robots.txt (20%)
        // - Sitemap reference in robots.txt (+10%)
        // - Accessible sitemap (40%)
        // - Perfect URL accessibility (25%)
        // - Quality factors (15% - lastmod, recent update, no errors)
        $this->assertGreaterThanOrEqual(90, $result['sitemap_score']);
    }

    public function test_large_sitemap_recommendation()
    {
        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once();

        $robotsContent = "User-agent: *\nSitemap: https://example.com/sitemap.xml";

        // Create sitemap with many URLs (simulate large sitemap)
        $urls = '';
        for ($i = 1; $i <= 100; $i++) {
            $urls .= "<url><loc>https://example.com/page{$i}</loc></url>";
        }
        $sitemapXml = '<?xml version="1.0" encoding="UTF-8"?>
            <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . $urls . '</urlset>';

        Http::shouldReceive('timeout')->andReturnSelf();
        Http::shouldReceive('get')
            ->with('https://example.com/robots.txt')
            ->once()
            ->andReturn(Http::response($robotsContent, 200));
        Http::shouldReceive('get')
            ->with('https://example.com/sitemap.xml')
            ->once()
            ->andReturn(Http::response($sitemapXml, 200, ['Content-Type' => 'application/xml']));

        // Don't test all URLs
        Http::shouldReceive('head')->times(50)->andReturn(Http::response('', 200));

        $result = $this->sitemapAnalyzer->analyze('https://example.com', ['max_accessibility_tests' => 50]);

        $this->assertEquals(100, $result['sitemaps'][0]['statistics']['total_urls']);
        $this->assertEquals(50, $result['url_analysis']['tested_urls']);
    }

    public function test_cached_results()
    {
        $cachedData = [
            'sitemap_score' => 85,
            'robots_txt' => ['accessible' => true],
            'cached' => true
        ];

        Cache::shouldReceive('get')->once()->andReturn($cachedData);

        $result = $this->sitemapAnalyzer->analyze('https://example.com');

        $this->assertEquals($cachedData, $result);
    }

    public function test_force_refresh_bypasses_cache()
    {
        Cache::shouldNotReceive('get');
        Cache::shouldReceive('put')->once();

        Http::shouldReceive('timeout')->once()->andReturnSelf();
        Http::shouldReceive('get')
            ->with('https://example.com/robots.txt')
            ->once()
            ->andReturn(Http::response('User-agent: *', 200));

        $result = $this->sitemapAnalyzer->analyze('https://example.com', ['force_refresh' => true]);

        $this->assertIsArray($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}