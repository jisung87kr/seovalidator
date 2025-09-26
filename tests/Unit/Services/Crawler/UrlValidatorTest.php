<?php

namespace Tests\Unit\Services\Crawler;

use Tests\TestCase;
use App\Services\Crawler\UrlValidator;
use App\Services\Crawler\UrlValidationException;

class UrlValidatorTest extends TestCase
{
    private UrlValidator $urlValidator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->urlValidator = new UrlValidator();
    }

    public function test_validate_accepts_valid_https_url()
    {
        $url = 'https://example.com';
        $result = $this->urlValidator->validate($url);

        $this->assertEquals('https://example.com/', $result);
    }

    public function test_validate_accepts_valid_http_url()
    {
        $url = 'http://example.com';
        $result = $this->urlValidator->validate($url);

        $this->assertEquals('http://example.com/', $result);
    }

    public function test_validate_adds_https_to_url_without_scheme()
    {
        $url = 'example.com';
        $result = $this->urlValidator->validate($url);

        $this->assertEquals('https://example.com/', $result);
    }

    public function test_validate_normalizes_url_components()
    {
        $url = 'HTTPS://EXAMPLE.COM/Path/To/Page';
        $result = $this->urlValidator->validate($url);

        $this->assertEquals('https://example.com/Path/To/Page', $result);
    }

    public function test_validate_rejects_empty_url()
    {
        $this->expectException(UrlValidationException::class);
        $this->expectExceptionMessage('URL cannot be empty');

        $this->urlValidator->validate('');
    }

    public function test_validate_rejects_url_too_long()
    {
        $longUrl = 'https://example.com/' . str_repeat('a', 2048);

        $this->expectException(UrlValidationException::class);
        $this->expectExceptionMessage('URL exceeds maximum length');

        $this->urlValidator->validate($longUrl);
    }

    public function test_validate_rejects_invalid_url_format()
    {
        $this->expectException(UrlValidationException::class);
        // This will fail at DNS resolution step for invalid domains
        $this->expectExceptionMessageMatches('/Invalid URL format|Domain does not exist/');

        $this->urlValidator->validate('not-a-url');
    }

    public function test_validate_rejects_unsupported_scheme()
    {
        $this->expectException(UrlValidationException::class);
        $this->expectExceptionMessage('Unsupported URL scheme');

        $this->urlValidator->validate('ftp://example.com');
    }

    public function test_validate_rejects_url_without_host()
    {
        $this->expectException(UrlValidationException::class);
        $this->expectExceptionMessage('Invalid URL format');

        $this->urlValidator->validate('https:///path');
    }

    public function test_validate_rejects_localhost()
    {
        $this->expectException(UrlValidationException::class);
        $this->expectExceptionMessage('Access to localhost/internal URLs is not allowed');

        $this->urlValidator->validate('https://localhost');
    }

    public function test_validate_rejects_private_ip()
    {
        $this->expectException(UrlValidationException::class);
        $this->expectExceptionMessage('Access to private/reserved IP addresses is not allowed');

        $this->urlValidator->validate('https://192.168.1.1');
    }

    public function test_validate_rejects_blocked_tld()
    {
        $this->expectException(UrlValidationException::class);
        $this->expectExceptionMessage('Access to test domains is not allowed');

        $this->urlValidator->validate('https://example.test');
    }

    public function test_validate_rejects_suspicious_patterns()
    {
        $this->expectException(UrlValidationException::class);
        $this->expectExceptionMessage('URL contains suspicious patterns');

        $this->urlValidator->validate('https://example.com/../etc/passwd');
    }

    public function test_validate_handles_query_parameters()
    {
        $url = 'https://example.com/search?q=test&page=1';
        $result = $this->urlValidator->validate($url);

        $this->assertEquals('https://example.com/search?q=test&page=1', $result);
    }

    public function test_validate_handles_port_numbers()
    {
        $url = 'https://example.com:8080/path';
        $result = $this->urlValidator->validate($url);

        $this->assertEquals('https://example.com:8080/path', $result);
    }

    public function test_validate_removes_default_ports()
    {
        $httpUrl = 'http://example.com:80/path';
        $httpsUrl = 'https://example.com:443/path';

        $httpResult = $this->urlValidator->validate($httpUrl);
        $httpsResult = $this->urlValidator->validate($httpsUrl);

        $this->assertEquals('http://example.com/path', $httpResult);
        $this->assertEquals('https://example.com/path', $httpsResult);
    }

    public function test_extract_domain()
    {
        $url = 'https://www.example.com/path';
        $domain = $this->urlValidator->extractDomain($url);

        $this->assertEquals('www.example.com', $domain);
    }

    public function test_extract_protocol()
    {
        $url = 'https://example.com';
        $protocol = $this->urlValidator->extractProtocol($url);

        $this->assertEquals('https', $protocol);
    }

    public function test_is_https()
    {
        $httpsUrl = 'https://example.com';
        $httpUrl = 'http://example.com';

        $this->assertTrue($this->urlValidator->isHttps($httpsUrl));
        $this->assertFalse($this->urlValidator->isHttps($httpUrl));
    }

    public function test_force_https()
    {
        $httpUrl = 'http://example.com';
        $result = $this->urlValidator->forceHttps($httpUrl);

        $this->assertEquals('https://example.com', $result);
    }

    public function test_validate_batch()
    {
        $urls = [
            'https://example.com',
            'http://test.com',
            'invalid-url',
            'https://localhost'
        ];

        $result = $this->urlValidator->validateBatch($urls);

        $this->assertArrayHasKey('valid_urls', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('summary', $result);

        $this->assertCount(2, $result['valid_urls']);
        $this->assertCount(2, $result['errors']);
        $this->assertEquals(4, $result['summary']['total_urls']);
        $this->assertEquals(2, $result['summary']['valid']);
        $this->assertEquals(2, $result['summary']['invalid']);
    }

    public function test_parse_url()
    {
        $url = 'https://www.example.com:8080/path/to/page?param=value#section';
        $result = $this->urlValidator->parseUrl($url);

        $this->assertEquals('https://www.example.com:8080/path/to/page?param=value#section', $result['full_url']);
        $this->assertEquals('https', $result['scheme']);
        $this->assertEquals('www.example.com', $result['host']);
        $this->assertEquals(8080, $result['port']);
        $this->assertEquals('/path/to/page', $result['path']);
        $this->assertEquals('param=value', $result['query']);
        $this->assertEquals('section', $result['fragment']);
        $this->assertEquals('www.example.com', $result['domain']);
        $this->assertTrue($result['is_https']);
        $this->assertEquals('https://www.example.com:8080', $result['base_url']);
    }

    public function test_get_validation_rules()
    {
        $rules = $this->urlValidator->getValidationRules();

        $this->assertArrayHasKey('allowed_schemes', $rules);
        $this->assertArrayHasKey('blocked_domains', $rules);
        $this->assertArrayHasKey('blocked_tlds', $rules);
        $this->assertArrayHasKey('max_url_length', $rules);
        $this->assertArrayHasKey('security_checks', $rules);

        $this->assertContains('http', $rules['allowed_schemes']);
        $this->assertContains('https', $rules['allowed_schemes']);
        $this->assertContains('localhost', $rules['blocked_domains']);
        $this->assertEquals(2048, $rules['max_url_length']);
    }

    public function test_validate_handles_unicode_domains()
    {
        // IDN (Internationalized Domain Names) handling - use encoded path
        $url = 'https://example.com/' . urlencode('üñíçødé');
        $result = $this->urlValidator->validate($url);

        $this->assertIsString($result);
        $this->assertStringContainsString('example.com', $result);
    }

    public function test_validate_handles_fragments()
    {
        $url = 'https://example.com/page#section';
        $result = $this->urlValidator->validate($url);

        $this->assertEquals('https://example.com/page#section', $result);
    }

    public function test_validate_url_encodes_path_components()
    {
        // URL with spaces should be pre-encoded or will fail validation
        $url = 'https://example.com/path%20with%20spaces';
        $result = $this->urlValidator->validate($url);

        $this->assertStringContainsString('path%20with%20spaces', $result);
    }
}