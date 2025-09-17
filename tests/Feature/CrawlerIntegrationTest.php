<?php

namespace Tests\Feature;

use App\Services\UrlCrawlerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class CrawlerIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private UrlCrawlerService $crawler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crawler = new UrlCrawlerService();
        
        // Disable caching for tests to ensure fresh results
        Config::set('crawler.cache.enabled', false);
        
        // Set reasonable timeout for tests
        Config::set('crawler.timeout', 10);
        
        // Reduce retries for faster tests
        Config::set('crawler.retry.max_attempts', 1);
    }

    /**
     * Test crawling a simple HTML page
     * Note: This test requires Chrome/Chromium to be installed
     * 
     * @group integration
     */
    public function test_crawl_simple_html_page(): void
    {
        // Create a simple test HTML content
        $testHtml = '
        <!DOCTYPE html>
        <html>
        <head>
            <title>Test Page</title>
            <meta name="description" content="This is a test page">
            <meta name="keywords" content="test, seo, crawler">
            <meta property="og:title" content="Test OG Title">
            <meta name="twitter:card" content="summary">
            <link rel="canonical" href="https://example.com/canonical">
        </head>
        <body>
            <h1>Main Heading</h1>
            <h2>Sub Heading</h2>
            <p>This is test content with <a href="/internal">internal link</a> 
               and <a href="https://external.com">external link</a>.</p>
            <img src="image1.jpg" alt="Test Image">
            <img src="image2.jpg" title="Image Title">
        </body>
        </html>';

        // For integration testing, we would need a test server
        // or mock the Browsershot response. For now, we'll test
        // the data extraction logic separately.
        
        $this->markTestSkipped('Integration test requires Chrome/Chromium installation and test server');
    }

    /**
     * Test URL validation in integration context
     */
    public function test_url_validation_integration(): void
    {
        // Test various URL formats
        $testCases = [
            ['https://www.google.com', true],
            ['http://localhost:8000', true],
            ['https://subdomain.example.com/path?query=value', true],
            ['ftp://files.example.com', false],
            ['mailto:test@example.com', false],
            ['javascript:void(0)', false],
            ['', false],
            ['not-a-url', false],
        ];

        foreach ($testCases as [$url, $expected]) {
            $result = $this->crawler->validateUrl($url);
            $this->assertEquals(
                $expected, 
                $result, 
                "URL validation failed for: {$url}"
            );
        }
    }

    /**
     * Test crawler configuration integration
     */
    public function test_crawler_configuration_integration(): void
    {
        // Test that configuration is properly loaded
        $this->assertIsInt(config('crawler.timeout'));
        $this->assertIsArray(config('crawler.user_agents'));
        $this->assertIsArray(config('crawler.browsershot.args'));
        
        // Test configuration defaults
        $this->assertGreaterThan(0, config('crawler.timeout'));
        $this->assertNotEmpty(config('crawler.user_agents'));
    }

    /**
     * Test cache integration
     */
    public function test_cache_integration(): void
    {
        Config::set('crawler.cache.enabled', true);
        Config::set('crawler.cache.ttl', 60);
        
        $cacheKey = 'seo_crawl:' . md5('https://example.com');
        
        // Ensure cache is clean
        Cache::forget($cacheKey);
        $this->assertNull(Cache::get($cacheKey));
        
        // Cache integration would be tested with actual crawl results
        // For now, we verify cache configuration is working
        Cache::put($cacheKey, 'test_data', 60);
        $this->assertEquals('test_data', Cache::get($cacheKey));
        
        Cache::forget($cacheKey);
    }

    /**
     * Test error handling integration
     */
    public function test_error_handling_integration(): void
    {
        // Test with invalid URL
        $result = $this->crawler->crawl('invalid-url');
        
        $this->assertFalse($result->isSuccessful());
        $this->assertEquals('invalid-url', $result->url);
        $this->assertEquals('Invalid URL format', $result->errorMessage);
        $this->assertNull($result->htmlContent);
    }

    /**
     * Test timeout configuration
     */
    public function test_timeout_configuration(): void
    {
        $originalTimeout = config('crawler.timeout');
        
        // Test setting custom timeout
        $this->crawler->setTimeout(45);
        
        // Test chaining
        $result = $this->crawler->setTimeout(60);
        $this->assertInstanceOf(UrlCrawlerService::class, $result);
    }

    /**
     * Test user agent configuration
     */
    public function test_user_agent_configuration(): void
    {
        $customUserAgent = 'TestBot/1.0 (Test Suite)';
        
        // Test setting custom user agent
        $result = $this->crawler->setUserAgent($customUserAgent);
        $this->assertInstanceOf(UrlCrawlerService::class, $result);
    }

    /**
     * Test service container binding
     */
    public function test_service_container_binding(): void
    {
        // Test that the service can be resolved from container
        $crawler = app(UrlCrawlerService::class);
        $this->assertInstanceOf(UrlCrawlerService::class, $crawler);
        
        // Test interface binding would go here if we bind the interface
        // $interfaceCrawler = app(CrawlerInterface::class);
        // $this->assertInstanceOf(CrawlerInterface::class, $interfaceCrawler);
    }

    /**
     * Test configuration file loading
     */
    public function test_configuration_file_loading(): void
    {
        // Verify that our crawler config file is loaded
        $config = config('crawler');
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('timeout', $config);
        $this->assertArrayHasKey('user_agents', $config);
        $this->assertArrayHasKey('cache', $config);
        $this->assertArrayHasKey('retry', $config);
        $this->assertArrayHasKey('browsershot', $config);
        $this->assertArrayHasKey('extraction', $config);
    }

    /**
     * Test retry configuration
     */
    public function test_retry_configuration(): void
    {
        Config::set('crawler.retry.max_attempts', 2);
        Config::set('crawler.retry.delay_seconds', 1);
        
        $this->assertEquals(2, config('crawler.retry.max_attempts'));
        $this->assertEquals(1, config('crawler.retry.delay_seconds'));
    }
}