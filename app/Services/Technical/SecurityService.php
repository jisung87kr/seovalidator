<?php

namespace App\Services\Technical;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use DOMDocument;
use DOMXPath;
use Exception;

/**
 * Security analysis service for technical SEO
 * Validates SSL certificates, HTTPS implementation, security headers,
 * and other security factors that impact SEO
 */
class SecurityService
{
    private int $timeout = 15;
    private int $cacheMinutes = 30;

    /**
     * Perform comprehensive security analysis
     */
    public function analyze(string $url, string $html, array $options = []): array
    {
        Log::info('Starting security analysis', ['url' => $url]);

        $cacheKey = "security:" . md5($url);

        if (!($options['force_refresh'] ?? false)) {
            $cached = Cache::get($cacheKey);
            if ($cached) {
                Log::debug('Returning cached security results', ['url' => $url]);
                return $cached;
            }
        }

        $results = [
            'url' => $url,
            'analyzed_at' => now()->toISOString(),
            'security_score' => 0,
            'https_analysis' => [],
            'ssl_certificate' => [],
            'security_headers' => [],
            'mixed_content' => [],
            'external_resources' => [],
            'content_security_policy' => [],
            'recommendations' => [],
            'errors' => []
        ];

        // Parse HTML for analysis
        $dom = $this->initializeDom($html);
        $xpath = $dom ? new DOMXPath($dom) : null;

        // Analyze HTTPS implementation
        try {
            $results['https_analysis'] = $this->analyzeHttpsImplementation($url);
        } catch (Exception $e) {
            Log::warning('HTTPS analysis failed', ['error' => $e->getMessage()]);
            $results['errors'][] = 'HTTPS analysis failed: ' . $e->getMessage();
        }

        // Analyze SSL certificate
        try {
            $results['ssl_certificate'] = $this->analyzeSslCertificate($url);
        } catch (Exception $e) {
            Log::warning('SSL certificate analysis failed', ['error' => $e->getMessage()]);
            $results['errors'][] = 'SSL certificate analysis failed: ' . $e->getMessage();
        }

        // Analyze security headers
        try {
            $results['security_headers'] = $this->analyzeSecurityHeaders($url);
        } catch (Exception $e) {
            Log::warning('Security headers analysis failed', ['error' => $e->getMessage()]);
            $results['errors'][] = 'Security headers analysis failed: ' . $e->getMessage();
        }

        // Analyze mixed content issues
        if ($xpath) {
            try {
                $results['mixed_content'] = $this->analyzeMixedContent($url, $xpath);
            } catch (Exception $e) {
                Log::warning('Mixed content analysis failed', ['error' => $e->getMessage()]);
                $results['errors'][] = 'Mixed content analysis failed: ' . $e->getMessage();
            }

            // Analyze external resources security
            try {
                $results['external_resources'] = $this->analyzeExternalResources($url, $xpath);
            } catch (Exception $e) {
                Log::warning('External resources analysis failed', ['error' => $e->getMessage()]);
                $results['errors'][] = 'External resources analysis failed: ' . $e->getMessage();
            }

            // Analyze Content Security Policy
            try {
                $results['content_security_policy'] = $this->analyzeContentSecurityPolicy($html, $xpath);
            } catch (Exception $e) {
                Log::warning('CSP analysis failed', ['error' => $e->getMessage()]);
                $results['errors'][] = 'CSP analysis failed: ' . $e->getMessage();
            }
        }

        // Calculate security score
        $results['security_score'] = $this->calculateSecurityScore($results);

        // Generate security recommendations
        $results['recommendations'] = $this->generateSecurityRecommendations($results);

        Cache::put($cacheKey, $results, $this->cacheMinutes * 60);

        Log::info('Security analysis completed', [
            'url' => $url,
            'security_score' => $results['security_score']
        ]);

        return $results;
    }

    /**
     * Analyze HTTPS implementation
     */
    private function analyzeHttpsImplementation(string $url): array
    {
        $urlParts = parse_url($url);
        $isHttps = ($urlParts['scheme'] ?? '') === 'https';

        $results = [
            'is_https' => $isHttps,
            'scheme' => $urlParts['scheme'] ?? '',
            'port' => $urlParts['port'] ?? ($isHttps ? 443 : 80),
            'redirect_analysis' => [],
            'hsts_support' => false,
            'force_https' => false
        ];

        if ($isHttps) {
            // Test HTTP to HTTPS redirect
            $httpUrl = str_replace('https://', 'http://', $url);
            $results['redirect_analysis'] = $this->testHttpsRedirect($httpUrl);
            $results['force_https'] = $results['redirect_analysis']['redirects_to_https'] ?? false;
        } else {
            // Check if HTTPS version is available
            $httpsUrl = str_replace('http://', 'https://', $url);
            $results['https_available'] = $this->testHttpsAvailability($httpsUrl);
        }

        return $results;
    }

    /**
     * Test HTTP to HTTPS redirect
     */
    private function testHttpsRedirect(string $httpUrl): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withoutRedirecting()
                ->get($httpUrl);

            $redirectsToHttps = false;
            $statusCode = $response->status();
            $location = $response->header('Location');

            if (in_array($statusCode, [301, 302, 303, 307, 308]) && $location) {
                $redirectsToHttps = str_starts_with($location, 'https://');
            }

            return [
                'status_code' => $statusCode,
                'redirects_to_https' => $redirectsToHttps,
                'location' => $location,
                'is_permanent_redirect' => in_array($statusCode, [301, 308])
            ];

        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
                'redirects_to_https' => false
            ];
        }
    }

    /**
     * Test HTTPS availability
     */
    private function testHttpsAvailability(string $httpsUrl): bool
    {
        try {
            $response = Http::timeout($this->timeout)->get($httpsUrl);
            return $response->successful();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Analyze SSL certificate
     */
    private function analyzeSslCertificate(string $url): array
    {
        $urlParts = parse_url($url);

        if (($urlParts['scheme'] ?? '') !== 'https') {
            return [
                'has_ssl' => false,
                'reason' => 'URL is not HTTPS'
            ];
        }

        $host = $urlParts['host'];
        $port = $urlParts['port'] ?? 443;

        try {
            $context = stream_context_create([
                'ssl' => [
                    'capture_peer_cert' => true,
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ]);

            $socket = stream_socket_client(
                "ssl://{$host}:{$port}",
                $errno,
                $errstr,
                $this->timeout,
                STREAM_CLIENT_CONNECT,
                $context
            );

            if (!$socket) {
                return [
                    'has_ssl' => false,
                    'error' => "Connection failed: {$errstr} ({$errno})"
                ];
            }

            $cert = stream_context_get_params($socket)['options']['ssl']['peer_certificate'] ?? null;
            fclose($socket);

            if (!$cert) {
                return [
                    'has_ssl' => false,
                    'error' => 'No certificate found'
                ];
            }

            $certInfo = openssl_x509_parse($cert);

            return [
                'has_ssl' => true,
                'issuer' => $certInfo['issuer']['CN'] ?? 'Unknown',
                'subject' => $certInfo['subject']['CN'] ?? 'Unknown',
                'valid_from' => date('Y-m-d H:i:s', $certInfo['validFrom_time_t']),
                'valid_to' => date('Y-m-d H:i:s', $certInfo['validTo_time_t']),
                'days_until_expiry' => ceil(($certInfo['validTo_time_t'] - time()) / 86400),
                'is_expired' => $certInfo['validTo_time_t'] < time(),
                'is_self_signed' => $certInfo['issuer'] === $certInfo['subject'],
                'signature_algorithm' => $certInfo['signatureTypeSN'] ?? 'Unknown',
                'key_size' => $this->extractKeySize($cert),
                'san_domains' => $this->extractSanDomains($certInfo)
            ];

        } catch (Exception $e) {
            return [
                'has_ssl' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Analyze security headers
     */
    private function analyzeSecurityHeaders(string $url): array
    {
        try {
            $response = Http::timeout($this->timeout)->get($url);
            $headers = $response->headers();

            $securityHeaders = [
                'strict-transport-security' => [
                    'present' => isset($headers['Strict-Transport-Security']),
                    'value' => $headers['Strict-Transport-Security'][0] ?? null,
                    'max_age' => $this->extractHstsMaxAge($headers['Strict-Transport-Security'][0] ?? ''),
                    'include_subdomains' => str_contains($headers['Strict-Transport-Security'][0] ?? '', 'includeSubDomains'),
                    'preload' => str_contains($headers['Strict-Transport-Security'][0] ?? '', 'preload')
                ],
                'content-security-policy' => [
                    'present' => isset($headers['Content-Security-Policy']),
                    'value' => $headers['Content-Security-Policy'][0] ?? null,
                    'directives' => $this->parseCspDirectives($headers['Content-Security-Policy'][0] ?? '')
                ],
                'x-frame-options' => [
                    'present' => isset($headers['X-Frame-Options']),
                    'value' => $headers['X-Frame-Options'][0] ?? null,
                    'blocks_framing' => in_array(strtoupper($headers['X-Frame-Options'][0] ?? ''), ['DENY', 'SAMEORIGIN'])
                ],
                'x-content-type-options' => [
                    'present' => isset($headers['X-Content-Type-Options']),
                    'value' => $headers['X-Content-Type-Options'][0] ?? null,
                    'nosniff' => strtolower($headers['X-Content-Type-Options'][0] ?? '') === 'nosniff'
                ],
                'x-xss-protection' => [
                    'present' => isset($headers['X-XSS-Protection']),
                    'value' => $headers['X-XSS-Protection'][0] ?? null,
                    'enabled' => str_starts_with($headers['X-XSS-Protection'][0] ?? '', '1')
                ],
                'referrer-policy' => [
                    'present' => isset($headers['Referrer-Policy']),
                    'value' => $headers['Referrer-Policy'][0] ?? null,
                    'restricts_referrer' => in_array($headers['Referrer-Policy'][0] ?? '', [
                        'no-referrer', 'same-origin', 'strict-origin', 'strict-origin-when-cross-origin'
                    ])
                ],
                'permissions-policy' => [
                    'present' => isset($headers['Permissions-Policy']),
                    'value' => $headers['Permissions-Policy'][0] ?? null
                ]
            ];

            return [
                'response_status' => $response->status(),
                'headers' => $securityHeaders,
                'security_score' => $this->calculateHeaderSecurityScore($securityHeaders)
            ];

        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
                'security_score' => 0
            ];
        }
    }

    /**
     * Analyze mixed content issues
     */
    private function analyzeMixedContent(string $url, DOMXPath $xpath): array
    {
        if (!str_starts_with($url, 'https://')) {
            return [
                'applicable' => false,
                'reason' => 'Page is not served over HTTPS'
            ];
        }

        $mixedContent = [
            'applicable' => true,
            'http_images' => [],
            'http_scripts' => [],
            'http_stylesheets' => [],
            'http_links' => [],
            'total_issues' => 0
        ];

        // Find HTTP images
        $httpImages = $xpath->query('//img[starts-with(@src, "http://")]');
        foreach ($httpImages as $img) {
            $mixedContent['http_images'][] = $img->getAttribute('src');
        }

        // Find HTTP scripts
        $httpScripts = $xpath->query('//script[starts-with(@src, "http://")]');
        foreach ($httpScripts as $script) {
            $mixedContent['http_scripts'][] = $script->getAttribute('src');
        }

        // Find HTTP stylesheets
        $httpStylesheets = $xpath->query('//link[@rel="stylesheet" and starts-with(@href, "http://")]');
        foreach ($httpStylesheets as $link) {
            $mixedContent['http_stylesheets'][] = $link->getAttribute('href');
        }

        // Find HTTP links
        $httpLinks = $xpath->query('//a[starts-with(@href, "http://")]');
        foreach ($httpLinks as $link) {
            $mixedContent['http_links'][] = $link->getAttribute('href');
        }

        $mixedContent['total_issues'] = count($mixedContent['http_images']) +
                                       count($mixedContent['http_scripts']) +
                                       count($mixedContent['http_stylesheets']);

        return $mixedContent;
    }

    /**
     * Analyze external resources security
     */
    private function analyzeExternalResources(string $url, DOMXPath $xpath): array
    {
        $baseDomain = parse_url($url, PHP_URL_HOST);

        $externalResources = [
            'scripts' => [],
            'stylesheets' => [],
            'images' => [],
            'iframes' => [],
            'domains' => [],
            'security_analysis' => []
        ];

        // Analyze external scripts
        $scripts = $xpath->query('//script[@src]');
        foreach ($scripts as $script) {
            $src = $script->getAttribute('src');
            $domain = parse_url($src, PHP_URL_HOST);

            if ($domain && $domain !== $baseDomain) {
                $externalResources['scripts'][] = $src;
                $externalResources['domains'][] = $domain;
            }
        }

        // Analyze external stylesheets
        $stylesheets = $xpath->query('//link[@rel="stylesheet" and @href]');
        foreach ($stylesheets as $link) {
            $href = $link->getAttribute('href');
            $domain = parse_url($href, PHP_URL_HOST);

            if ($domain && $domain !== $baseDomain) {
                $externalResources['stylesheets'][] = $href;
                $externalResources['domains'][] = $domain;
            }
        }

        // Analyze external images
        $images = $xpath->query('//img[@src]');
        foreach ($images as $img) {
            $src = $img->getAttribute('src');
            $domain = parse_url($src, PHP_URL_HOST);

            if ($domain && $domain !== $baseDomain) {
                $externalResources['images'][] = $src;
                $externalResources['domains'][] = $domain;
            }
        }

        // Analyze iframes
        $iframes = $xpath->query('//iframe[@src]');
        foreach ($iframes as $iframe) {
            $src = $iframe->getAttribute('src');
            $domain = parse_url($src, PHP_URL_HOST);

            if ($domain && $domain !== $baseDomain) {
                $externalResources['iframes'][] = $src;
                $externalResources['domains'][] = $domain;
            }
        }

        $externalResources['domains'] = array_unique($externalResources['domains']);
        $externalResources['security_analysis'] = $this->analyzeExternalDomainsSecurity($externalResources['domains']);

        return $externalResources;
    }

    /**
     * Analyze Content Security Policy
     */
    private function analyzeContentSecurityPolicy(string $html, DOMXPath $xpath): array
    {
        $csp = [
            'meta_tag' => null,
            'header' => null,
            'directives' => [],
            'security_analysis' => []
        ];

        // Check for CSP in meta tag
        $cspMeta = $xpath->query('//meta[@http-equiv="Content-Security-Policy"]/@content');
        if ($cspMeta->length > 0) {
            $csp['meta_tag'] = $cspMeta->item(0)->value;
            $csp['directives'] = $this->parseCspDirectives($csp['meta_tag']);
        }

        // Analyze CSP effectiveness
        if (!empty($csp['directives'])) {
            $csp['security_analysis'] = $this->analyzeCspEffectiveness($csp['directives']);
        }

        return $csp;
    }

    /**
     * Calculate overall security score
     */
    private function calculateSecurityScore(array $results): int
    {
        $score = 0;
        $maxScore = 0;

        // HTTPS implementation (40%)
        if (isset($results['https_analysis']['is_https'])) {
            $maxScore += 40;
            if ($results['https_analysis']['is_https']) {
                $score += 40;

                // Bonus for proper HTTPS redirect
                if ($results['https_analysis']['force_https']) {
                    $score += 5;
                }
            }
        }

        // SSL Certificate (25%)
        if (isset($results['ssl_certificate']['has_ssl'])) {
            $maxScore += 25;
            if ($results['ssl_certificate']['has_ssl']) {
                $score += 15;

                // Deduct for issues
                if ($results['ssl_certificate']['is_expired'] ?? false) {
                    $score -= 10;
                }
                if ($results['ssl_certificate']['is_self_signed'] ?? false) {
                    $score -= 5;
                }
                if (($results['ssl_certificate']['days_until_expiry'] ?? 365) < 30) {
                    $score -= 5;
                }
            }
        }

        // Security Headers (20%)
        if (isset($results['security_headers']['security_score'])) {
            $maxScore += 20;
            $score += ($results['security_headers']['security_score'] / 100) * 20;
        }

        // Mixed Content (10%)
        if (isset($results['mixed_content']['total_issues'])) {
            $maxScore += 10;
            if ($results['mixed_content']['total_issues'] === 0) {
                $score += 10;
            } else {
                $score += max(0, 10 - $results['mixed_content']['total_issues']);
            }
        }

        // External Resources Security (5%)
        $maxScore += 5;
        $externalDomains = count($results['external_resources']['domains'] ?? []);
        if ($externalDomains === 0) {
            $score += 5;
        } else {
            $score += max(0, 5 - floor($externalDomains / 5));
        }

        return $maxScore > 0 ? min(100, max(0, round(($score / $maxScore) * 100))) : 0;
    }

    // Helper methods

    private function initializeDom(string $html): ?DOMDocument
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);

        $success = $dom->loadHTML(
            '<?xml encoding="UTF-8">' . $html,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();

        return $success ? $dom : null;
    }

    private function extractKeySize($cert): ?int
    {
        $publicKey = openssl_pkey_get_public($cert);
        if ($publicKey) {
            $keyDetails = openssl_pkey_get_details($publicKey);
            return $keyDetails['bits'] ?? null;
        }
        return null;
    }

    private function extractSanDomains(array $certInfo): array
    {
        $sanDomains = [];

        if (isset($certInfo['extensions']['subjectAltName'])) {
            $san = $certInfo['extensions']['subjectAltName'];
            $domains = explode(', ', $san);

            foreach ($domains as $domain) {
                if (str_starts_with($domain, 'DNS:')) {
                    $sanDomains[] = substr($domain, 4);
                }
            }
        }

        return $sanDomains;
    }

    private function extractHstsMaxAge(string $hstsHeader): ?int
    {
        if (preg_match('/max-age=(\d+)/', $hstsHeader, $matches)) {
            return (int)$matches[1];
        }
        return null;
    }

    private function parseCspDirectives(string $csp): array
    {
        $directives = [];
        $parts = explode(';', $csp);

        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) continue;

            $directiveParts = preg_split('/\s+/', $part, 2);
            $directiveName = $directiveParts[0];
            $directiveValue = $directiveParts[1] ?? '';

            $directives[$directiveName] = $directiveValue;
        }

        return $directives;
    }

    private function calculateHeaderSecurityScore(array $headers): int
    {
        $score = 0;

        if ($headers['strict-transport-security']['present']) $score += 25;
        if ($headers['content-security-policy']['present']) $score += 20;
        if ($headers['x-frame-options']['present']) $score += 15;
        if ($headers['x-content-type-options']['nosniff'] ?? false) $score += 15;
        if ($headers['x-xss-protection']['enabled'] ?? false) $score += 10;
        if ($headers['referrer-policy']['restricts_referrer'] ?? false) $score += 10;
        if ($headers['permissions-policy']['present']) $score += 5;

        return min(100, $score);
    }

    private function analyzeExternalDomainsSecurity(array $domains): array
    {
        $analysis = [
            'total_domains' => count($domains),
            'high_risk_domains' => 0,
            'cdn_domains' => 0,
            'social_media_domains' => 0
        ];

        $highRiskIndicators = ['ads', 'tracker', 'analytics'];
        $cdnIndicators = ['cdn', 'cloudfront', 'fastly', 'cloudflare'];
        $socialDomains = ['facebook.com', 'twitter.com', 'instagram.com', 'youtube.com'];

        foreach ($domains as $domain) {
            $domain = strtolower($domain);

            foreach ($highRiskIndicators as $indicator) {
                if (str_contains($domain, $indicator)) {
                    $analysis['high_risk_domains']++;
                    break;
                }
            }

            foreach ($cdnIndicators as $indicator) {
                if (str_contains($domain, $indicator)) {
                    $analysis['cdn_domains']++;
                    break;
                }
            }

            foreach ($socialDomains as $socialDomain) {
                if (str_contains($domain, $socialDomain)) {
                    $analysis['social_media_domains']++;
                    break;
                }
            }
        }

        return $analysis;
    }

    private function analyzeCspEffectiveness(array $directives): array
    {
        $analysis = [
            'has_default_src' => isset($directives['default-src']),
            'allows_unsafe_inline' => false,
            'allows_unsafe_eval' => false,
            'has_script_src' => isset($directives['script-src']),
            'has_style_src' => isset($directives['style-src']),
            'effectiveness_score' => 0
        ];

        foreach ($directives as $directive => $value) {
            if (str_contains($value, "'unsafe-inline'")) {
                $analysis['allows_unsafe_inline'] = true;
            }
            if (str_contains($value, "'unsafe-eval'")) {
                $analysis['allows_unsafe_eval'] = true;
            }
        }

        // Calculate effectiveness score
        $score = 0;
        if ($analysis['has_default_src']) $score += 30;
        if ($analysis['has_script_src']) $score += 25;
        if ($analysis['has_style_src']) $score += 15;
        if (!$analysis['allows_unsafe_inline']) $score += 20;
        if (!$analysis['allows_unsafe_eval']) $score += 10;

        $analysis['effectiveness_score'] = $score;

        return $analysis;
    }

    /**
     * Generate security recommendations
     */
    private function generateSecurityRecommendations(array $results): array
    {
        $recommendations = [];

        // HTTPS recommendations
        if (!($results['https_analysis']['is_https'] ?? false)) {
            $recommendations[] = [
                'type' => 'error',
                'message' => 'Site not served over HTTPS',
                'impact' => 'high',
                'fix' => 'Implement SSL certificate and serve your site over HTTPS'
            ];
        } elseif (!($results['https_analysis']['force_https'] ?? false)) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => 'HTTP requests not redirected to HTTPS',
                'impact' => 'medium',
                'fix' => 'Implement 301 redirects from HTTP to HTTPS'
            ];
        }

        // SSL Certificate recommendations
        if (isset($results['ssl_certificate']['is_expired']) && $results['ssl_certificate']['is_expired']) {
            $recommendations[] = [
                'type' => 'error',
                'message' => 'SSL certificate has expired',
                'impact' => 'high',
                'fix' => 'Renew your SSL certificate immediately'
            ];
        } elseif (isset($results['ssl_certificate']['days_until_expiry']) && $results['ssl_certificate']['days_until_expiry'] < 30) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => 'SSL certificate expires soon',
                'impact' => 'medium',
                'fix' => 'Renew your SSL certificate before it expires'
            ];
        }

        if (isset($results['ssl_certificate']['is_self_signed']) && $results['ssl_certificate']['is_self_signed']) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => 'Using self-signed SSL certificate',
                'impact' => 'medium',
                'fix' => 'Use a certificate from a trusted Certificate Authority'
            ];
        }

        // Security headers recommendations
        $headers = $results['security_headers']['headers'] ?? [];

        if (!($headers['strict-transport-security']['present'] ?? false)) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => 'Missing Strict-Transport-Security header',
                'impact' => 'medium',
                'fix' => 'Add HSTS header to force HTTPS connections'
            ];
        }

        if (!($headers['content-security-policy']['present'] ?? false)) {
            $recommendations[] = [
                'type' => 'info',
                'message' => 'Missing Content-Security-Policy header',
                'impact' => 'medium',
                'fix' => 'Implement CSP header to prevent XSS attacks'
            ];
        }

        if (!($headers['x-frame-options']['present'] ?? false)) {
            $recommendations[] = [
                'type' => 'info',
                'message' => 'Missing X-Frame-Options header',
                'impact' => 'low',
                'fix' => 'Add X-Frame-Options header to prevent clickjacking'
            ];
        }

        // Mixed content recommendations
        $mixedContentIssues = $results['mixed_content']['total_issues'] ?? 0;
        if ($mixedContentIssues > 0) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => "{$mixedContentIssues} mixed content issues found",
                'impact' => 'medium',
                'fix' => 'Update all HTTP resources to use HTTPS'
            ];
        }

        return $recommendations;
    }
}