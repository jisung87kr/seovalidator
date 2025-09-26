<?php

namespace Tests\Unit\Services\Technical;

use Tests\TestCase;
use App\Services\Technical\PageSpeedService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Client\Response;
use Mockery;

class PageSpeedServiceTest extends TestCase
{
    private PageSpeedService $pageSpeedService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pageSpeedService = new PageSpeedService();
    }

    public function test_analyze_with_valid_api_key_makes_api_calls()
    {
        config(['services.google.pagespeed_api_key' => 'test-api-key']);
        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once();

        $mockResponse = [
            'lighthouseResult' => [
                'categories' => [
                    'performance' => ['score' => 0.85]
                ],
                'audits' => [
                    'largest-contentful-paint' => ['numericValue' => 2000],
                    'cumulative-layout-shift' => ['numericValue' => 0.05],
                    'first-contentful-paint' => ['numericValue' => 1500]
                ]
            ]
        ];

        Http::fake([
            '*' => Http::response($mockResponse, 200)
        ]);

        $result = $this->pageSpeedService->analyze('https://example.com');

        $this->assertIsArray($result);
        $this->assertEquals(85, $result['performance_score']);
        $this->assertTrue($result['api_available']);
        $this->assertArrayHasKey('core_web_vitals', $result);
        $this->assertArrayHasKey('lcp', $result['core_web_vitals']);
        $this->assertArrayHasKey('cls', $result['core_web_vitals']);
        $this->assertArrayHasKey('fcp', $result['core_web_vitals']);
    }

    public function test_analyze_without_api_key_uses_fallback()
    {
        config(['services.google.pagespeed_api_key' => '']);
        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once();

        $result = $this->pageSpeedService->analyze('https://example.com');

        $this->assertIsArray($result);
        $this->assertFalse($result['api_available']);
        $this->assertTrue($result['fallback_analysis']);
        $this->assertArrayHasKey('fallback_checks', $result);
        $this->assertArrayHasKey('performance_score', $result);
    }

    public function test_analyze_with_api_error_uses_fallback()
    {
        config(['services.google.pagespeed_api_key' => 'test-api-key']);
        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once();

        Http::shouldReceive('timeout')
            ->andReturnSelf();
        Http::shouldReceive('get')
            ->andThrow(new \Exception('API Error'));

        $result = $this->pageSpeedService->analyze('https://example.com');

        $this->assertIsArray($result);
        $this->assertNotEmpty($result['errors']);
        $this->assertTrue($result['fallback_analysis']);
    }

    public function test_analyze_with_cached_results()
    {
        $cachedData = [
            'performance_score' => 90,
            'api_available' => true,
            'cached' => true
        ];

        Cache::shouldReceive('get')->once()->andReturn($cachedData);

        $result = $this->pageSpeedService->analyze('https://example.com');

        $this->assertEquals($cachedData, $result);
    }

    public function test_analyze_mobile_with_valid_html()
    {
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
                    <button>Click me</button>
                </body>
            </html>
        ';

        $result = $this->pageSpeedService->analyzeMobile('https://example.com', $html);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('mobile_score', $result);
        $this->assertArrayHasKey('mobile_friendly', $result);
        $this->assertArrayHasKey('viewport_configuration', $result);
        $this->assertArrayHasKey('responsive_design', $result);

        $this->assertTrue($result['viewport_configuration']['has_viewport_meta']);
        $this->assertTrue($result['viewport_configuration']['width_device_width']);
        $this->assertGreaterThan(0, $result['responsive_design']['media_queries']);
        $this->assertGreaterThan(0, $result['responsive_design']['responsive_images']['total']);
    }

    public function test_analyze_mobile_without_viewport_meta()
    {
        $html = '<html><head></head><body>Content</body></html>';

        $result = $this->pageSpeedService->analyzeMobile('https://example.com', $html);

        $this->assertFalse($result['viewport_configuration']['has_viewport_meta']);
        $this->assertFalse($result['viewport_configuration']['width_device_width']);
        $this->assertContains(
            'Missing viewport meta tag',
            array_column($result['recommendations'], 'message')
        );
    }

    public function test_analyze_mobile_with_invalid_html()
    {
        $html = 'invalid html content';

        $result = $this->pageSpeedService->analyzeMobile('https://example.com', $html);

        $this->assertNotEmpty($result['recommendations']);
        $this->assertContains(
            'Unable to parse HTML for mobile analysis',
            array_column($result['recommendations'], 'message')
        );
    }

    public function test_core_web_vitals_rating_calculation()
    {
        config(['services.google.pagespeed_api_key' => 'test-api-key']);
        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once();

        $mockResponse = [
            'lighthouseResult' => [
                'categories' => [
                    'performance' => ['score' => 0.75]
                ],
                'audits' => [
                    'largest-contentful-paint' => ['numericValue' => 3000],     // Poor (>2500ms)
                    'cumulative-layout-shift' => ['numericValue' => 0.05],    // Good (<0.1)
                    'first-contentful-paint' => ['numericValue' => 2500]      // Needs improvement (>1800ms)
                ]
            ]
        ];

        Http::shouldReceive('timeout')->twice()->andReturnSelf();
        Http::shouldReceive('get')->twice()->andReturn(Http::response($mockResponse, 200));

        $result = $this->pageSpeedService->analyze('https://example.com');

        $vitals = $result['core_web_vitals'];
        $this->assertEquals('poor', $vitals['lcp']['rating']);
        $this->assertEquals('good', $vitals['cls']['rating']);
        $this->assertEquals('needs_improvement', $vitals['fcp']['rating']);
    }

    public function test_fallback_analysis_https_detection()
    {
        config(['services.google.pagespeed_api_key' => '']);
        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once();

        $httpsResult = $this->pageSpeedService->analyze('https://example.com');
        $httpResult = $this->pageSpeedService->analyze('http://example.com');

        $this->assertTrue($httpsResult['fallback_checks']['https']);
        $this->assertFalse($httpResult['fallback_checks']['https']);
        $this->assertGreaterThan($httpResult['performance_score'], $httpsResult['performance_score']);
    }

    public function test_fallback_analysis_cdn_detection()
    {
        config(['services.google.pagespeed_api_key' => '']);
        Cache::shouldReceive('get')->twice()->andReturn(null);
        Cache::shouldReceive('put')->twice();

        $cdnResult = $this->pageSpeedService->analyze('https://cdn.example.com');
        $normalResult = $this->pageSpeedService->analyze('https://www.example.com');

        $this->assertTrue($cdnResult['fallback_checks']['possible_cdn']);
        $this->assertFalse($normalResult['fallback_checks']['possible_cdn']);
    }

    public function test_extract_opportunities_from_api_response()
    {
        config(['services.google.pagespeed_api_key' => 'test-api-key']);
        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once();

        $mockResponse = [
            'lighthouseResult' => [
                'categories' => [
                    'performance' => ['score' => 0.60]
                ],
                'audits' => [
                    'unused-css-rules' => [
                        'score' => 0.3,
                        'title' => 'Remove unused CSS',
                        'description' => 'Remove dead rules from stylesheets',
                        'numericValue' => 500,
                        'displayValue' => 'Potential savings of 500 ms'
                    ],
                    'render-blocking-resources' => [
                        'score' => 0.4,
                        'title' => 'Eliminate render-blocking resources',
                        'description' => 'Resources are blocking the first paint',
                        'numericValue' => 300,
                        'displayValue' => 'Potential savings of 300 ms'
                    ]
                ]
            ]
        ];

        Http::shouldReceive('timeout')->twice()->andReturnSelf();
        Http::shouldReceive('get')->twice()->andReturn(Http::response($mockResponse, 200));

        $result = $this->pageSpeedService->analyze('https://example.com');

        $this->assertCount(2, $result['opportunities']);
        $this->assertEquals('Remove unused CSS', $result['opportunities'][0]['title']);
        $this->assertEquals('Eliminate render-blocking resources', $result['opportunities'][1]['title']);
    }

    public function test_viewport_score_calculation()
    {
        $testCases = [
            'width=device-width, initial-scale=1' => 100,
            'width=device-width' => 40,
            'initial-scale=1' => 30,
            'width=device-width, user-scalable=no' => 80,
            '' => 0
        ];

        $reflection = new \ReflectionClass($this->pageSpeedService);
        $method = $reflection->getMethod('calculateViewportScore');
        $method->setAccessible(true);

        foreach ($testCases as $viewportContent => $expectedScore) {
            $score = $method->invoke($this->pageSpeedService, $viewportContent);
            $this->assertEquals($expectedScore, $score, "Failed for viewport: {$viewportContent}");
        }
    }

    public function test_media_queries_detection()
    {
        $reflection = new \ReflectionClass($this->pageSpeedService);
        $method = $reflection->getMethod('countMediaQueries');
        $method->setAccessible(true);

        $htmlWithMediaQueries = '
            <style>
                @media (max-width: 768px) { body { font-size: 14px; } }
                @media (min-width: 1024px) { body { font-size: 16px; } }
            </style>
        ';

        $count = $method->invoke($this->pageSpeedService, $htmlWithMediaQueries);
        $this->assertEquals(2, $count);

        $htmlWithoutMediaQueries = '<style>body { font-size: 14px; }</style>';
        $count = $method->invoke($this->pageSpeedService, $htmlWithoutMediaQueries);
        $this->assertEquals(0, $count);
    }

    public function test_force_refresh_bypasses_cache()
    {
        config(['services.google.pagespeed_api_key' => 'test-api-key']);

        // Should not check cache when force_refresh is true
        Cache::shouldNotReceive('get');
        Cache::shouldReceive('put')->once();

        $mockResponse = [
            'lighthouseResult' => [
                'categories' => [
                    'performance' => ['score' => 0.85]
                ],
                'audits' => []
            ]
        ];

        Http::shouldReceive('timeout')->twice()->andReturnSelf();
        Http::shouldReceive('get')->twice()->andReturn(Http::response($mockResponse, 200));

        $result = $this->pageSpeedService->analyze('https://example.com', ['force_refresh' => true]);

        $this->assertIsArray($result);
    }

    public function test_mobile_score_calculation()
    {
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
                    <button>Click me</button>
                </body>
            </html>
        ';

        $result = $this->pageSpeedService->analyzeMobile('https://example.com', $html);

        // Should get high score for having viewport meta tag, media queries, and responsive images
        $this->assertGreaterThan(70, $result['mobile_score']);
        $this->assertTrue($result['mobile_friendly']);
    }

    public function test_recommendations_generation_for_poor_performance()
    {
        config(['services.google.pagespeed_api_key' => 'test-api-key']);
        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once();

        $mockResponse = [
            'lighthouseResult' => [
                'categories' => [
                    'performance' => ['score' => 0.30] // Poor performance
                ],
                'audits' => [
                    'largest-contentful-paint' => ['numericValue' => 5000], // Poor LCP
                    'cumulative-layout-shift' => ['numericValue' => 0.3],   // Poor CLS
                    'first-input-delay' => ['numericValue' => 400]          // Poor FID
                ]
            ],
            'loadingExperience' => [
                'metrics' => [
                    'LARGEST_CONTENTFUL_PAINT_MS' => ['percentile' => 5000, 'category' => 'SLOW'],
                    'CUMULATIVE_LAYOUT_SHIFT_SCORE' => ['percentile' => 0.3, 'category' => 'SLOW'],
                    'FIRST_INPUT_DELAY_MS' => ['percentile' => 400, 'category' => 'SLOW']
                ]
            ]
        ];

        Http::shouldReceive('timeout')->twice()->andReturnSelf();
        Http::shouldReceive('get')->twice()->andReturn(Http::response($mockResponse, 200));

        $result = $this->pageSpeedService->analyze('https://example.com');

        $this->assertNotEmpty($result['recommendations']);
        $messages = array_column($result['recommendations'], 'message');
        $this->assertContains('Poor page speed performance', $messages);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}