<?php

namespace Tests\Unit\Services\Technical;

use Tests\TestCase;
use App\Services\Technical\SecurityService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Mockery;

class SecurityServiceTest extends TestCase
{
    private SecurityService $securityService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->securityService = new SecurityService();
    }

    public function test_analyze_with_https_url()
    {
        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once();

        $mockHeaders = [
            'Strict-Transport-Security' => ['max-age=31536000; includeSubDomains; preload'],
            'Content-Security-Policy' => ['default-src \'self\''],
            'X-Frame-Options' => ['SAMEORIGIN'],
            'X-Content-Type-Options' => ['nosniff'],
            'X-XSS-Protection' => ['1; mode=block'],
            'Referrer-Policy' => ['strict-origin-when-cross-origin']
        ];

        Http::shouldReceive('timeout')
            ->times(3) // HTTPS analysis, redirect test, security headers
            ->andReturnSelf();
        Http::shouldReceive('withoutRedirecting')
            ->once()
            ->andReturnSelf();
        Http::shouldReceive('get')
            ->times(2)
            ->andReturn(Http::response('', 200, $mockHeaders));

        $html = '<html><head></head><body>Content</body></html>';

        $result = $this->securityService->analyze('https://example.com', $html);

        $this->assertIsArray($result);
        $this->assertGreaterThan(70, $result['security_score']);
        $this->assertTrue($result['https_analysis']['is_https']);
        $this->assertTrue($result['security_headers']['headers']['strict-transport-security']['present']);
        $this->assertEmpty($result['mixed_content']['http_images']);
    }

    public function test_analyze_with_http_url()
    {
        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once();

        Http::shouldReceive('timeout')
            ->twice()
            ->andReturnSelf();
        Http::shouldReceive('get')
            ->twice()
            ->andReturn(Http::response('', 200));

        $html = '<html><head></head><body>Content</body></html>';

        $result = $this->securityService->analyze('http://example.com', $html);

        $this->assertFalse($result['https_analysis']['is_https']);
        $this->assertLessThan(50, $result['security_score']);
        $this->assertContains(
            'Site not served over HTTPS',
            array_column($result['recommendations'], 'message')
        );
    }

    public function test_ssl_certificate_analysis_for_https()
    {
        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once();

        Http::shouldReceive('timeout')->twice()->andReturnSelf();
        Http::shouldReceive('withoutRedirecting')->once()->andReturnSelf();
        Http::shouldReceive('get')->twice()->andReturn(Http::response('', 200));

        $html = '<html><head></head><body>Content</body></html>';

        $result = $this->securityService->analyze('https://example.com', $html);

        $this->assertArrayHasKey('ssl_certificate', $result);
        // Note: SSL certificate analysis requires actual SSL connection,
        // so in tests it will likely show connection errors
    }

    public function test_mixed_content_detection()
    {
        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once();

        Http::shouldReceive('timeout')->twice()->andReturnSelf();
        Http::shouldReceive('withoutRedirecting')->once()->andReturnSelf();
        Http::shouldReceive('get')->twice()->andReturn(Http::response('', 200));

        $html = '
            <html>
                <head>
                    <link rel="stylesheet" href="http://insecure.example.com/style.css">
                </head>
                <body>
                    <img src="http://insecure.example.com/image.jpg">
                    <script src="http://insecure.example.com/script.js"></script>
                    <a href="http://other.example.com">Link</a>
                </body>
            </html>
        ';

        $result = $this->securityService->analyze('https://example.com', $html);

        $this->assertTrue($result['mixed_content']['applicable']);
        $this->assertCount(1, $result['mixed_content']['http_images']);
        $this->assertCount(1, $result['mixed_content']['http_scripts']);
        $this->assertCount(1, $result['mixed_content']['http_stylesheets']);
        $this->assertCount(1, $result['mixed_content']['http_links']);
        $this->assertEquals(3, $result['mixed_content']['total_issues']); // Images, scripts, stylesheets (not links)
    }

    public function test_mixed_content_not_applicable_for_http()
    {
        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once();

        Http::shouldReceive('timeout')->twice()->andReturnSelf();
        Http::shouldReceive('get')->twice()->andReturn(Http::response('', 200));

        $html = '<html><head></head><body><img src="http://example.com/image.jpg"></body></html>';

        $result = $this->securityService->analyze('http://example.com', $html);

        $this->assertFalse($result['mixed_content']['applicable']);
        $this->assertEquals('Page is not served over HTTPS', $result['mixed_content']['reason']);
    }

    public function test_security_headers_analysis()
    {
        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once();

        $perfectHeaders = [
            'Strict-Transport-Security' => ['max-age=31536000; includeSubDomains; preload'],
            'Content-Security-Policy' => ['default-src \'self\'; script-src \'self\''],
            'X-Frame-Options' => ['DENY'],
            'X-Content-Type-Options' => ['nosniff'],
            'X-XSS-Protection' => ['1; mode=block'],
            'Referrer-Policy' => ['strict-origin-when-cross-origin'],
            'Permissions-Policy' => ['geolocation=(), microphone=()']
        ];

        Http::shouldReceive('timeout')->twice()->andReturnSelf();
        Http::shouldReceive('withoutRedirecting')->once()->andReturnSelf();
        Http::shouldReceive('get')->twice()->andReturn(Http::response('', 200, $perfectHeaders));

        $html = '<html><head></head><body>Content</body></html>';

        $result = $this->securityService->analyze('https://example.com', $html);

        $headers = $result['security_headers']['headers'];
        $this->assertTrue($headers['strict-transport-security']['present']);
        $this->assertEquals(31536000, $headers['strict-transport-security']['max_age']);
        $this->assertTrue($headers['strict-transport-security']['include_subdomains']);
        $this->assertTrue($headers['strict-transport-security']['preload']);

        $this->assertTrue($headers['content-security-policy']['present']);
        $this->assertTrue($headers['x-frame-options']['present']);
        $this->assertTrue($headers['x-frame-options']['blocks_framing']);
        $this->assertTrue($headers['x-content-type-options']['nosniff']);
        $this->assertTrue($headers['x-xss-protection']['enabled']);
        $this->assertTrue($headers['referrer-policy']['restricts_referrer']);
        $this->assertTrue($headers['permissions-policy']['present']);

        $this->assertEquals(100, $result['security_headers']['security_score']);
    }

    public function test_security_headers_analysis_with_missing_headers()
    {
        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once();

        Http::shouldReceive('timeout')->twice()->andReturnSelf();
        Http::shouldReceive('withoutRedirecting')->once()->andReturnSelf();
        Http::shouldReceive('get')->twice()->andReturn(Http::response('', 200, []));

        $html = '<html><head></head><body>Content</body></html>';

        $result = $this->securityService->analyze('https://example.com', $html);

        $headers = $result['security_headers']['headers'];
        $this->assertFalse($headers['strict-transport-security']['present']);
        $this->assertFalse($headers['content-security-policy']['present']);
        $this->assertFalse($headers['x-frame-options']['present']);
        $this->assertFalse($headers['x-content-type-options']['present']);
        $this->assertFalse($headers['x-xss-protection']['present']);
        $this->assertFalse($headers['referrer-policy']['present']);

        $this->assertEquals(0, $result['security_headers']['security_score']);
    }

    public function test_external_resources_analysis()
    {
        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once();

        Http::shouldReceive('timeout')->twice()->andReturnSelf();
        Http::shouldReceive('withoutRedirecting')->once()->andReturnSelf();
        Http::shouldReceive('get')->twice()->andReturn(Http::response('', 200));

        $html = '
            <html>
                <head>
                    <link rel="stylesheet" href="https://cdn.example.com/style.css">
                    <script src="https://analytics.google.com/script.js"></script>
                </head>
                <body>
                    <img src="https://images.example.com/logo.png">
                    <iframe src="https://youtube.com/embed/video"></iframe>
                </body>
            </html>
        ';

        $result = $this->securityService->analyze('https://example.com', $html);

        $external = $result['external_resources'];
        $this->assertCount(1, $external['stylesheets']);
        $this->assertCount(1, $external['scripts']);
        $this->assertCount(1, $external['images']);
        $this->assertCount(1, $external['iframes']);
        $this->assertCount(4, $external['domains']);

        $analysis = $external['security_analysis'];
        $this->assertEquals(4, $analysis['total_domains']);
        $this->assertGreaterThanOrEqual(1, $analysis['cdn_domains']); // cdn.example.com
        $this->assertGreaterThanOrEqual(1, $analysis['social_media_domains']); // youtube.com
    }

    public function test_content_security_policy_meta_tag()
    {
        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once();

        Http::shouldReceive('timeout')->twice()->andReturnSelf();
        Http::shouldReceive('withoutRedirecting')->once()->andReturnSelf();
        Http::shouldReceive('get')->twice()->andReturn(Http::response('', 200));

        $html = '
            <html>
                <head>
                    <meta http-equiv="Content-Security-Policy" content="default-src \'self\'; script-src \'self\' \'unsafe-inline\'">
                </head>
                <body>Content</body>
            </html>
        ';

        $result = $this->securityService->analyze('https://example.com', $html);

        $csp = $result['content_security_policy'];
        $this->assertNotNull($csp['meta_tag']);
        $this->assertArrayHasKey('default-src', $csp['directives']);
        $this->assertArrayHasKey('script-src', $csp['directives']);

        $analysis = $csp['security_analysis'];
        $this->assertTrue($analysis['has_default_src']);
        $this->assertTrue($analysis['has_script_src']);
        $this->assertTrue($analysis['allows_unsafe_inline']);
        $this->assertFalse($analysis['allows_unsafe_eval']);
    }

    public function test_https_redirect_analysis()
    {
        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once();

        // Mock redirect from HTTP to HTTPS
        Http::shouldReceive('timeout')->twice()->andReturnSelf();
        Http::shouldReceive('withoutRedirecting')->once()->andReturnSelf();
        Http::shouldReceive('get')
            ->once()
            ->with('http://example.com')
            ->andReturn(Http::response('', 301, ['Location' => 'https://example.com']));
        Http::shouldReceive('get')
            ->once()
            ->with('https://example.com')
            ->andReturn(Http::response('', 200));

        $html = '<html><head></head><body>Content</body></html>';

        $result = $this->securityService->analyze('https://example.com', $html);

        $redirectAnalysis = $result['https_analysis']['redirect_analysis'];
        $this->assertEquals(301, $redirectAnalysis['status_code']);
        $this->assertTrue($redirectAnalysis['redirects_to_https']);
        $this->assertTrue($redirectAnalysis['is_permanent_redirect']);
        $this->assertTrue($result['https_analysis']['force_https']);
    }

    public function test_security_score_calculation()
    {
        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once();

        $goodHeaders = [
            'Strict-Transport-Security' => ['max-age=31536000'],
            'Content-Security-Policy' => ['default-src \'self\''],
            'X-Frame-Options' => ['SAMEORIGIN'],
            'X-Content-Type-Options' => ['nosniff'],
            'X-XSS-Protection' => ['1']
        ];

        Http::shouldReceive('timeout')->twice()->andReturnSelf();
        Http::shouldReceive('withoutRedirecting')->once()->andReturnSelf();
        Http::shouldReceive('get')->twice()->andReturn(Http::response('', 200, $goodHeaders));

        $html = '<html><head></head><body>Content</body></html>';

        $result = $this->securityService->analyze('https://example.com', $html);

        // Should get high score for HTTPS + good headers + no mixed content
        $this->assertGreaterThan(80, $result['security_score']);
    }

    public function test_recommendations_for_missing_security_features()
    {
        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once();

        Http::shouldReceive('timeout')->twice()->andReturnSelf();
        Http::shouldReceive('get')->twice()->andReturn(Http::response('', 200, []));

        $html = '
            <html>
                <head></head>
                <body>
                    <img src="http://insecure.example.com/image.jpg">
                </body>
            </html>
        ';

        $result = $this->securityService->analyze('http://example.com', $html);

        $recommendations = $result['recommendations'];
        $messages = array_column($recommendations, 'message');

        $this->assertContains('Site not served over HTTPS', $messages);
        $this->assertContains('Missing Strict-Transport-Security header', $messages);
        $this->assertContains('Missing Content-Security-Policy header', $messages);
        $this->assertContains('Missing X-Frame-Options header', $messages);
    }

    public function test_csp_effectiveness_analysis()
    {
        $reflection = new \ReflectionClass($this->securityService);
        $method = $reflection->getMethod('analyzeCspEffectiveness');
        $method->setAccessible(true);

        $goodCsp = [
            'default-src' => '\'self\'',
            'script-src' => '\'self\' \'nonce-123\'',
            'style-src' => '\'self\''
        ];

        $badCsp = [
            'script-src' => '\'self\' \'unsafe-inline\' \'unsafe-eval\'',
            'style-src' => '\'self\' \'unsafe-inline\''
        ];

        $goodAnalysis = $method->invoke($this->securityService, $goodCsp);
        $badAnalysis = $method->invoke($this->securityService, $badCsp);

        $this->assertTrue($goodAnalysis['has_default_src']);
        $this->assertFalse($goodAnalysis['allows_unsafe_inline']);
        $this->assertFalse($goodAnalysis['allows_unsafe_eval']);
        $this->assertGreaterThan(80, $goodAnalysis['effectiveness_score']);

        $this->assertFalse($badAnalysis['has_default_src']);
        $this->assertTrue($badAnalysis['allows_unsafe_inline']);
        $this->assertTrue($badAnalysis['allows_unsafe_eval']);
        $this->assertLessThan(50, $badAnalysis['effectiveness_score']);
    }

    public function test_analyze_with_cached_results()
    {
        $cachedData = [
            'security_score' => 95,
            'https_analysis' => ['is_https' => true],
            'cached' => true
        ];

        Cache::shouldReceive('get')->once()->andReturn($cachedData);

        $result = $this->securityService->analyze('https://example.com', '<html></html>');

        $this->assertEquals($cachedData, $result);
    }

    public function test_force_refresh_bypasses_cache()
    {
        // Should not check cache when force_refresh is true
        Cache::shouldNotReceive('get');
        Cache::shouldReceive('put')->once();

        Http::shouldReceive('timeout')->twice()->andReturnSelf();
        Http::shouldReceive('withoutRedirecting')->once()->andReturnSelf();
        Http::shouldReceive('get')->twice()->andReturn(Http::response('', 200));

        $result = $this->securityService->analyze(
            'https://example.com',
            '<html></html>',
            ['force_refresh' => true]
        );

        $this->assertIsArray($result);
    }

    public function test_hsts_max_age_extraction()
    {
        $reflection = new \ReflectionClass($this->securityService);
        $method = $reflection->getMethod('extractHstsMaxAge');
        $method->setAccessible(true);

        $testCases = [
            'max-age=31536000' => 31536000,
            'max-age=31536000; includeSubDomains' => 31536000,
            'includeSubDomains; max-age=86400; preload' => 86400,
            'no-max-age-here' => null,
            '' => null
        ];

        foreach ($testCases as $header => $expected) {
            $result = $method->invoke($this->securityService, $header);
            $this->assertEquals($expected, $result, "Failed for header: {$header}");
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}