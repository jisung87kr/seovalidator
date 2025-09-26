<?php

namespace Tests\Unit\Services\External;

use Tests\TestCase;
use App\Services\External\GooglePageSpeedClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;

class GooglePageSpeedClientTest extends TestCase
{
    private GooglePageSpeedClient $client;
    private MockHandler $mockHandler;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock configuration
        config([
            'services.google.pagespeed_api_key' => 'test-api-key',
            'services.google.pagespeed_max_retries' => 3,
            'services.google.pagespeed_retry_delay' => 100, // Fast for tests
            'services.google.pagespeed_cache_timeout' => 3600
        ]);

        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);

        // Replace the client with our mocked version
        $this->client = new GooglePageSpeedClient();
        $reflection = new \ReflectionClass($this->client);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->client, new Client(['handler' => $handlerStack]));
    }

    public function test_constructor_throws_exception_when_api_key_missing(): void
    {
        config(['services.google.pagespeed_api_key' => '']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Google PageSpeed API key not configured');

        new GooglePageSpeedClient();
    }

    public function test_analyze_url_returns_cached_results(): void
    {
        $url = 'https://example.com';
        $cachedData = [
            'mobile' => ['performance_score' => 85],
            'desktop' => ['performance_score' => 92],
            'analyzed_at' => now()->toISOString(),
            'cache_expires_at' => now()->addHour()->toISOString()
        ];

        Cache::shouldReceive('remember')
            ->once()
            ->andReturn($cachedData);

        $result = $this->client->analyzeUrl($url);

        $this->assertEquals($cachedData, $result);
        $this->assertArrayHasKey('mobile', $result);
        $this->assertArrayHasKey('desktop', $result);
    }

    public function test_analyze_url_makes_api_calls_when_not_cached(): void
    {
        $url = 'https://example.com';

        // Mock successful API responses
        $this->mockHandler->append(
            new Response(200, [], json_encode($this->getMockPageSpeedResponse('mobile'))),
            new Response(200, [], json_encode($this->getMockPageSpeedResponse('desktop')))
        );

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        Log::shouldReceive('info')->atLeast()->once();

        $result = $this->client->analyzeUrl($url);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('mobile', $result);
        $this->assertArrayHasKey('desktop', $result);
        $this->assertArrayHasKey('analyzed_at', $result);
        $this->assertArrayHasKey('cache_expires_at', $result);
    }

    public function test_analyze_url_handles_api_failures_gracefully(): void
    {
        $url = 'https://example.com';

        // Mock API failure
        $this->mockHandler->append(
            new RequestException('API Error', new Request('GET', 'test')),
            new RequestException('API Error', new Request('GET', 'test'))
        );

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        Log::shouldReceive('error')->atLeast()->once();
        Log::shouldReceive('info')->atLeast()->once();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Google PageSpeed API request failed');

        $this->client->analyzeUrl($url);
    }

    public function test_analyze_url_handles_rate_limiting(): void
    {
        $url = 'https://example.com';

        // Mock rate limit response
        $this->mockHandler->append(
            new RequestException(
                'Rate limited',
                new Request('GET', 'test'),
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
        $this->expectExceptionMessage('Google PageSpeed API rate limit exceeded');

        $this->client->analyzeUrl($url);
    }

    public function test_analyze_url_parses_response_correctly(): void
    {
        $url = 'https://example.com';

        $this->mockHandler->append(
            new Response(200, [], json_encode($this->getMockPageSpeedResponse('mobile'))),
            new Response(200, [], json_encode($this->getMockPageSpeedResponse('desktop')))
        );

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        Log::shouldReceive('info')->atLeast()->once();

        $result = $this->client->analyzeUrl($url);

        // Verify mobile results structure
        $this->assertArrayHasKey('strategy', $result['mobile']);
        $this->assertArrayHasKey('scores', $result['mobile']);
        $this->assertArrayHasKey('core_web_vitals', $result['mobile']);
        $this->assertArrayHasKey('performance_metrics', $result['mobile']);
        $this->assertArrayHasKey('opportunities', $result['mobile']);
        $this->assertArrayHasKey('diagnostics', $result['mobile']);

        // Verify scores structure
        $this->assertArrayHasKey('performance_score', $result['mobile']['scores']);
        $this->assertArrayHasKey('accessibility_score', $result['mobile']['scores']);
        $this->assertArrayHasKey('best_practices_score', $result['mobile']['scores']);
        $this->assertArrayHasKey('seo_score', $result['mobile']['scores']);

        // Verify Core Web Vitals structure
        $this->assertArrayHasKey('lcp', $result['mobile']['core_web_vitals']);
        $this->assertArrayHasKey('fcp', $result['mobile']['core_web_vitals']);
        $this->assertArrayHasKey('cls', $result['mobile']['core_web_vitals']);
    }

    public function test_analyze_url_with_custom_options(): void
    {
        $url = 'https://example.com';
        $options = [
            'categories' => ['performance', 'seo'],
            'locale' => 'ko'
        ];

        $this->mockHandler->append(
            new Response(200, [], json_encode($this->getMockPageSpeedResponse('mobile'))),
            new Response(200, [], json_encode($this->getMockPageSpeedResponse('desktop')))
        );

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        Log::shouldReceive('info')->atLeast()->once();

        $result = $this->client->analyzeUrl($url, $options);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('mobile', $result);
        $this->assertArrayHasKey('desktop', $result);
    }

    public function test_core_web_vitals_assessment(): void
    {
        $url = 'https://example.com';

        // Create mock response with specific Core Web Vitals values
        $mockResponse = $this->getMockPageSpeedResponse('mobile');
        $mockResponse['lighthouseResult']['audits']['largest-contentful-paint']['numericValue'] = 1500; // Good LCP
        $mockResponse['lighthouseResult']['audits']['cumulative-layout-shift']['numericValue'] = 0.05; // Good CLS
        $mockResponse['lighthouseResult']['audits']['total-blocking-time']['numericValue'] = 50; // Good TBT

        $this->mockHandler->append(
            new Response(200, [], json_encode($mockResponse)),
            new Response(200, [], json_encode($this->getMockPageSpeedResponse('desktop')))
        );

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        Log::shouldReceive('info')->atLeast()->once();

        $result = $this->client->analyzeUrl($url);

        // Verify Core Web Vitals assessments
        $this->assertEquals('Good', $result['mobile']['core_web_vitals']['lcp']['assessment']);
        $this->assertEquals('Good', $result['mobile']['core_web_vitals']['cls']['assessment']);
        $this->assertEquals('Good', $result['mobile']['core_web_vitals']['tbt']['assessment']);
    }

    private function getMockPageSpeedResponse(string $strategy): array
    {
        return [
            'lighthouseResult' => [
                'lighthouseVersion' => '10.0.0',
                'categories' => [
                    'performance' => ['score' => 0.85],
                    'accessibility' => ['score' => 0.92],
                    'best-practices' => ['score' => 0.88],
                    'seo' => ['score' => 0.95]
                ],
                'audits' => [
                    'largest-contentful-paint' => [
                        'numericValue' => 2000,
                        'displayValue' => '2.0 s',
                        'score' => 0.8
                    ],
                    'cumulative-layout-shift' => [
                        'numericValue' => 0.15,
                        'displayValue' => '0.15',
                        'score' => 0.7
                    ],
                    'total-blocking-time' => [
                        'numericValue' => 150,
                        'displayValue' => '150 ms',
                        'score' => 0.85
                    ],
                    'first-contentful-paint' => [
                        'numericValue' => 1200,
                        'displayValue' => '1.2 s',
                        'score' => 0.9
                    ],
                    'speed-index' => [
                        'numericValue' => 2500,
                        'displayValue' => '2.5 s',
                        'score' => 0.8
                    ],
                    'unused-css-rules' => [
                        'title' => 'Remove unused CSS',
                        'description' => 'Remove dead rules from stylesheets',
                        'score' => 0.6,
                        'numericValue' => 500,
                        'displayValue' => 'Potential savings of 500 ms',
                        'details' => [
                            'overallSavingsMs' => 500,
                            'overallSavingsBytes' => 50000
                        ]
                    ],
                    'render-blocking-resources' => [
                        'title' => 'Eliminate render-blocking resources',
                        'description' => 'Resources are blocking the first paint',
                        'score' => 0.7,
                        'numericValue' => 300,
                        'displayValue' => 'Potential savings of 300 ms'
                    ]
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