<?php

namespace Tests\Unit\Services\External;

use Tests\TestCase;
use App\Services\External\MozApiClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;

class MozApiClientTest extends TestCase
{
    private MozApiClient $client;
    private MockHandler $mockHandler;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock configuration
        config([
            'services.moz.access_id' => 'test-access-id',
            'services.moz.secret_key' => 'test-secret-key',
            'services.moz.max_retries' => 3,
            'services.moz.retry_delay' => 100, // Fast for tests
            'services.moz.cache_timeout' => 3600
        ]);

        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);

        // Replace the client with our mocked version
        $this->client = new MozApiClient();
        $reflection = new \ReflectionClass($this->client);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->client, new Client(['handler' => $handlerStack]));
    }

    public function test_constructor_throws_exception_when_credentials_missing(): void
    {
        config(['services.moz.access_id' => '']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Moz API credentials not configured');

        new MozApiClient();
    }

    public function test_get_domain_metrics_returns_cached_results(): void
    {
        $domain = 'example.com';
        $cachedData = [
            'domain' => $domain,
            'url_metrics' => ['domain_authority' => 65],
            'link_metrics' => ['status' => 'available'],
            'analyzed_at' => now()->toISOString()
        ];

        Cache::shouldReceive('remember')
            ->once()
            ->andReturn($cachedData);

        $result = $this->client->getDomainMetrics($domain);

        $this->assertEquals($cachedData, $result);
        $this->assertArrayHasKey('url_metrics', $result);
        $this->assertArrayHasKey('link_metrics', $result);
    }

    public function test_get_domain_metrics_makes_api_calls_when_not_cached(): void
    {
        $domain = 'example.com';

        // Mock successful API responses
        $this->mockHandler->append(
            new Response(200, [], json_encode($this->getMockUrlMetricsResponse())),
            new Response(200, [], json_encode($this->getMockLinkStatusResponse())),
            new Response(200, [], json_encode($this->getMockLinkingDomainsResponse()))
        );

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        Log::shouldReceive('info')->atLeast()->once();

        $result = $this->client->getDomainMetrics($domain);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('domain', $result);
        $this->assertArrayHasKey('url_metrics', $result);
        $this->assertArrayHasKey('link_metrics', $result);
        $this->assertArrayHasKey('domain_authority_assessment', $result);
        $this->assertArrayHasKey('spam_risk_assessment', $result);
        $this->assertArrayHasKey('overall_seo_health', $result);
    }

    public function test_get_domain_metrics_handles_api_failures(): void
    {
        $domain = 'example.com';

        // Mock API failure
        $this->mockHandler->append(
            new RequestException('API Error', new Request('POST', 'test'))
        );

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        Log::shouldReceive('error')->atLeast()->once();
        Log::shouldReceive('info')->atLeast()->once();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Moz URL Metrics API request failed');

        $this->client->getDomainMetrics($domain);
    }

    public function test_get_domain_metrics_handles_rate_limiting(): void
    {
        $domain = 'example.com';

        // Mock rate limit response
        $this->mockHandler->append(
            new RequestException(
                'Rate limited',
                new Request('POST', 'test'),
                new Response(429, [], 'Rate limit exceeded')
            )
        );

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        Log::shouldReceive('error')->atLeast()->once();
        Log::shouldReceive('info')->atLeast()->once();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Moz API rate limit exceeded');

        $this->client->getDomainMetrics($domain);
    }

    public function test_get_domain_metrics_handles_authentication_failure(): void
    {
        $domain = 'example.com';

        // Mock authentication failure
        $this->mockHandler->append(
            new RequestException(
                'Authentication failed',
                new Request('POST', 'test'),
                new Response(401, [], 'Unauthorized')
            )
        );

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        Log::shouldReceive('error')->atLeast()->once();
        Log::shouldReceive('info')->atLeast()->once();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Moz API authentication failed');

        $this->client->getDomainMetrics($domain);
    }

    public function test_domain_authority_assessment_excellent(): void
    {
        $domain = 'example.com';

        $mockUrlMetrics = $this->getMockUrlMetricsResponse();
        $mockUrlMetrics['results'][0]['domain_authority'] = 85; // Excellent DA

        $this->mockHandler->append(
            new Response(200, [], json_encode($mockUrlMetrics)),
            new Response(200, [], json_encode($this->getMockLinkStatusResponse())),
            new Response(200, [], json_encode($this->getMockLinkingDomainsResponse()))
        );

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        Log::shouldReceive('info')->atLeast()->once();

        $result = $this->client->getDomainMetrics($domain);

        $this->assertEquals('Excellent', $result['domain_authority_assessment']['assessment']);
        $this->assertEquals(85, $result['domain_authority_assessment']['score']);
        $this->assertStringContainsString('Maintain high-quality', $result['domain_authority_assessment']['recommendation']);
    }

    public function test_spam_risk_assessment_high(): void
    {
        $domain = 'example.com';

        $mockUrlMetrics = $this->getMockUrlMetricsResponse();
        $mockUrlMetrics['results'][0]['spam_score'] = 75; // High spam score

        $this->mockHandler->append(
            new Response(200, [], json_encode($mockUrlMetrics)),
            new Response(200, [], json_encode($this->getMockLinkStatusResponse())),
            new Response(200, [], json_encode($this->getMockLinkingDomainsResponse()))
        );

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        Log::shouldReceive('info')->atLeast()->once();

        $result = $this->client->getDomainMetrics($domain);

        $this->assertEquals('Very High', $result['spam_risk_assessment']['risk_level']);
        $this->assertEquals(75, $result['spam_risk_assessment']['score']);
        $this->assertTrue($result['spam_risk_assessment']['action_required']);
        $this->assertStringContainsString('Immediate action required', $result['spam_risk_assessment']['recommendation']);
    }

    public function test_overall_seo_health_calculation(): void
    {
        $domain = 'example.com';

        $mockUrlMetrics = $this->getMockUrlMetricsResponse();
        $mockUrlMetrics['results'][0]['domain_authority'] = 70;
        $mockUrlMetrics['results'][0]['spam_score'] = 10;
        $mockUrlMetrics['results'][0]['external_root_domains_to_root_domain'] = 500;

        $this->mockHandler->append(
            new Response(200, [], json_encode($mockUrlMetrics)),
            new Response(200, [], json_encode($this->getMockLinkStatusResponse())),
            new Response(200, [], json_encode($this->getMockLinkingDomainsResponse()))
        );

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        Log::shouldReceive('info')->atLeast()->once();

        $result = $this->client->getDomainMetrics($domain);

        $health = $result['overall_seo_health'];

        $this->assertArrayHasKey('score', $health);
        $this->assertArrayHasKey('grade', $health);
        $this->assertArrayHasKey('components', $health);
        $this->assertIsNumeric($health['score']);
        $this->assertContains($health['grade'], ['A', 'B', 'C', 'D', 'F']);
        $this->assertEquals(70, $health['components']['domain_authority']);
        $this->assertEquals(10, $health['components']['spam_risk']);
        $this->assertEquals(500, $health['components']['backlink_count']);
    }

    public function test_link_profile_health_assessment(): void
    {
        $domain = 'example.com';

        $mockLinkStatus = $this->getMockLinkStatusResponse();
        $mockLinkStatus['results'][0]['pages_in_index'] = 800;
        $mockLinkStatus['results'][0]['pages_crawled'] = 1000;

        $this->mockHandler->append(
            new Response(200, [], json_encode($this->getMockUrlMetricsResponse())),
            new Response(200, [], json_encode($mockLinkStatus)),
            new Response(200, [], json_encode($this->getMockLinkingDomainsResponse()))
        );

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        Log::shouldReceive('info')->atLeast()->once();

        $result = $this->client->getDomainMetrics($domain);

        $linkProfile = $result['link_metrics']['link_profile_health'];

        $this->assertEquals(80.0, $linkProfile['index_ratio']);
        $this->assertEquals('Good', $linkProfile['health_status']);
        $this->assertEquals(1000, $linkProfile['pages_crawled']);
        $this->assertEquals(800, $linkProfile['pages_indexed']);
    }

    public function test_top_linking_domains_quality_scoring(): void
    {
        $domain = 'example.com';

        $this->mockHandler->append(
            new Response(200, [], json_encode($this->getMockUrlMetricsResponse())),
            new Response(200, [], json_encode($this->getMockLinkStatusResponse())),
            new Response(200, [], json_encode($this->getMockLinkingDomainsResponse()))
        );

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        Log::shouldReceive('info')->atLeast()->once();

        $result = $this->client->getDomainMetrics($domain);

        $linkingDomains = $result['link_metrics']['top_linking_domains'];

        $this->assertIsArray($linkingDomains);
        $this->assertNotEmpty($linkingDomains);

        foreach ($linkingDomains as $domain) {
            $this->assertArrayHasKey('domain', $domain);
            $this->assertArrayHasKey('domain_authority', $domain);
            $this->assertArrayHasKey('spam_score', $domain);
            $this->assertArrayHasKey('quality_score', $domain);
            $this->assertIsNumeric($domain['quality_score']);
        }

        // Verify domains are sorted by quality score (descending)
        $qualityScores = array_column($linkingDomains, 'quality_score');
        $sortedScores = $qualityScores;
        rsort($sortedScores);
        $this->assertEquals($sortedScores, $qualityScores);
    }

    private function getMockUrlMetricsResponse(): array
    {
        return [
            'results' => [
                [
                    'domain_authority' => 65,
                    'page_authority' => 58,
                    'spam_score' => 15,
                    'root_domains_to_root_domain' => 1250,
                    'external_root_domains_to_root_domain' => 850,
                    'root_domains_to_page' => 45,
                    'external_root_domains_to_page' => 35,
                    'equity_links_to_page' => 125,
                    'equity_links_to_root_domain' => 2850,
                    'nofollow_equity_links_to_page' => 25,
                    'nofollow_equity_links_to_root_domain' => 450,
                    'last_crawled' => '2024-09-25T10:30:00Z'
                ]
            ]
        ];
    }

    private function getMockLinkStatusResponse(): array
    {
        return [
            'results' => [
                [
                    'status' => 'completed',
                    'pages_crawled' => 1500,
                    'pages_in_index' => 1200,
                    'last_crawl_started' => '2024-09-20T08:00:00Z',
                    'last_crawl_completed' => '2024-09-22T16:30:00Z'
                ]
            ]
        ];
    }

    private function getMockLinkingDomainsResponse(): array
    {
        return [
            'results' => [
                [
                    'source_domain' => 'high-authority-site.com',
                    'source_domain_authority' => 85,
                    'source_spam_score' => 5,
                    'equity_links' => 15
                ],
                [
                    'source_domain' => 'medium-authority-site.com',
                    'source_domain_authority' => 55,
                    'source_spam_score' => 20,
                    'equity_links' => 8
                ],
                [
                    'source_domain' => 'low-authority-site.com',
                    'source_domain_authority' => 25,
                    'source_spam_score' => 45,
                    'equity_links' => 3
                ]
            ]
        ];
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}