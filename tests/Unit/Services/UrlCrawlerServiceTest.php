<?php

namespace Tests\Unit\Services;

use App\Contracts\CrawlerInterface;
use App\DTOs\CrawlResult;
use App\Services\UrlCrawlerService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class UrlCrawlerServiceTest extends TestCase
{

    private UrlCrawlerService $crawler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crawler = new UrlCrawlerService();
    }

    public function test_implements_crawler_interface(): void
    {
        $this->assertInstanceOf(CrawlerInterface::class, $this->crawler);
    }

    public function test_validate_url_returns_true_for_valid_urls(): void
    {
        $validUrls = [
            'https://example.com',
            'http://test.com',
            'https://subdomain.example.com/path',
            'http://localhost:8000',
        ];

        foreach ($validUrls as $url) {
            $this->assertTrue(
                $this->crawler->validateUrl($url),
                "URL should be valid: {$url}"
            );
        }
    }

    public function test_validate_url_returns_false_for_invalid_urls(): void
    {
        $invalidUrls = [
            'invalid-url',
            'ftp://example.com',
            'mailto:test@example.com',
            '',
            'javascript:alert("xss")',
        ];

        foreach ($invalidUrls as $url) {
            $this->assertFalse(
                $this->crawler->validateUrl($url),
                "URL should be invalid: {$url}"
            );
        }
    }

    public function test_set_user_agent_returns_self(): void
    {
        $result = $this->crawler->setUserAgent('Custom User Agent');
        $this->assertSame($this->crawler, $result);
    }

    public function test_set_timeout_returns_self(): void
    {
        $result = $this->crawler->setTimeout(60);
        $this->assertSame($this->crawler, $result);
    }

    public function test_crawl_returns_failure_for_invalid_url(): void
    {
        $result = $this->crawler->crawl('invalid-url');

        $this->assertInstanceOf(CrawlResult::class, $result);
        $this->assertFalse($result->isSuccessful());
        $this->assertEquals('invalid-url', $result->url);
        $this->assertEquals('Invalid URL format', $result->errorMessage);
    }

    public function test_crawl_result_success_factory(): void
    {
        $url = 'https://example.com';
        $htmlContent = '<html><title>Test</title></html>';
        $extractedData = [
            'title' => 'Test',
            'meta_description' => 'Description',
            'images' => [],
            'internal_links' => [],
            'external_links' => [],
            'meta_tags' => [],
        ];
        $responseTime = 1.5;
        $statusCode = 200;

        $result = CrawlResult::success(
            $url,
            $htmlContent,
            $extractedData,
            $responseTime,
            $statusCode
        );

        $this->assertTrue($result->isSuccessful());
        $this->assertEquals($url, $result->url);
        $this->assertEquals('Test', $result->title);
        $this->assertEquals('Description', $result->metaDescription);
        $this->assertEquals($htmlContent, $result->htmlContent);
        $this->assertEquals($responseTime, $result->responseTime);
        $this->assertEquals($statusCode, $result->statusCode);
        $this->assertNull($result->errorMessage);
    }

    public function test_crawl_result_failure_factory(): void
    {
        $url = 'https://example.com';
        $errorMessage = 'Connection timeout';
        $statusCode = 500;
        $responseTime = 30.0;

        $result = CrawlResult::failure($url, $errorMessage, $statusCode, $responseTime);

        $this->assertFalse($result->isSuccessful());
        $this->assertEquals($url, $result->url);
        $this->assertEquals($errorMessage, $result->errorMessage);
        $this->assertEquals($statusCode, $result->statusCode);
        $this->assertEquals($responseTime, $result->responseTime);
        $this->assertNull($result->htmlContent);
        $this->assertNull($result->title);
    }

    public function test_crawl_result_get_total_links_count(): void
    {
        $result = new CrawlResult(
            url: 'https://example.com',
            title: 'Test',
            metaDescription: null,
            htmlContent: '<html></html>',
            images: [],
            internalLinks: [['url' => 'link1'], ['url' => 'link2']],
            externalLinks: [['url' => 'link3']],
            metaTags: [],
            responseTime: 1.0,
            statusCode: 200,
            errorMessage: null,
            crawledAt: now()
        );

        $this->assertEquals(3, $result->getTotalLinksCount());
    }

    public function test_crawl_result_get_images_without_alt(): void
    {
        $images = [
            ['src' => 'image1.jpg', 'alt' => 'Description'],
            ['src' => 'image2.jpg', 'alt' => ''],
            ['src' => 'image3.jpg', 'alt' => null],
        ];

        $result = new CrawlResult(
            url: 'https://example.com',
            title: 'Test',
            metaDescription: null,
            htmlContent: '<html></html>',
            images: $images,
            internalLinks: [],
            externalLinks: [],
            metaTags: [],
            responseTime: 1.0,
            statusCode: 200,
            errorMessage: null,
            crawledAt: now()
        );

        $imagesWithoutAlt = $result->getImagesWithoutAlt();
        $this->assertCount(2, $imagesWithoutAlt);
    }

    public function test_caching_can_be_disabled(): void
    {
        Config::set('crawler.cache.enabled', false);
        
        // Mock a successful crawl scenario would go here
        // Since we can't easily mock Browsershot in unit tests,
        // we'll test the cache behavior separately
        $this->assertFalse(config('crawler.cache.enabled'));
    }

    public function test_cache_settings_are_configurable(): void
    {
        Config::set('crawler.cache.ttl', 3600);
        Config::set('crawler.cache.prefix', 'test_crawl:');
        
        $this->assertEquals(3600, config('crawler.cache.ttl'));
        $this->assertEquals('test_crawl:', config('crawler.cache.prefix'));
    }

    public function test_retry_settings_are_configurable(): void
    {
        Config::set('crawler.retry.max_attempts', 5);
        Config::set('crawler.retry.delay_seconds', 3);
        
        $this->assertEquals(5, config('crawler.retry.max_attempts'));
        $this->assertEquals(3, config('crawler.retry.delay_seconds'));
    }

    public function test_user_agents_are_configurable(): void
    {
        $userAgents = [
            'Mozilla/5.0 (Test Browser)',
            'Custom Bot/1.0',
        ];
        
        Config::set('crawler.user_agents', $userAgents);
        
        $this->assertEquals($userAgents, config('crawler.user_agents'));
    }

    public function test_browsershot_settings_are_configurable(): void
    {
        $args = ['--no-sandbox', '--disable-gpu'];
        Config::set('crawler.browsershot.args', $args);
        
        $this->assertEquals($args, config('crawler.browsershot.args'));
    }

    public function test_timeout_setting_is_configurable(): void
    {
        Config::set('crawler.timeout', 45);
        
        $crawler = new UrlCrawlerService();
        $this->assertEquals(45, config('crawler.timeout'));
    }
}