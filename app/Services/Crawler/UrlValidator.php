<?php

namespace App\Services\Crawler;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UrlValidator
{
    private array $allowedSchemes = ['http', 'https'];
    private array $blockedDomains = [
        'localhost',
        '127.0.0.1',
        '0.0.0.0',
        '::1'
    ];
    private array $blockedTlds = [
        'test',
        'localhost',
        'local'
    ];
    private int $maxUrlLength = 2048;

    /**
     * Validate and normalize a URL
     */
    public function validate(string $url): string
    {
        Log::debug('Validating URL', ['url' => $url]);

        // Basic URL validation
        $this->validateBasicFormat($url);

        // Parse URL components - try with https if no scheme
        $originalUrl = $url;
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }

        $parsedUrl = parse_url($url);
        if ($parsedUrl === false) {
            throw new UrlValidationException("Invalid URL format: {$originalUrl}");
        }

        // Validate and normalize components
        $normalizedUrl = $this->normalizeUrl($parsedUrl, $url);

        // Security validations
        $this->validateSecurity($normalizedUrl);

        // Accessibility validation
        $this->validateAccessibility($normalizedUrl);

        Log::debug('URL validation successful', [
            'original_url' => $originalUrl,
            'normalized_url' => $normalizedUrl
        ]);

        return $normalizedUrl;
    }

    /**
     * Validate basic URL format
     */
    private function validateBasicFormat(string $url): void
    {
        if (empty($url)) {
            throw new UrlValidationException("URL cannot be empty");
        }

        if (strlen($url) > $this->maxUrlLength) {
            throw new UrlValidationException("URL exceeds maximum length of {$this->maxUrlLength} characters");
        }

        // Try to add scheme if missing before validation
        $testUrl = $url;
        if (!preg_match('#^https?://#i', $testUrl)) {
            $testUrl = 'https://' . $testUrl;
        }

        if (!filter_var($testUrl, FILTER_VALIDATE_URL)) {
            throw new UrlValidationException("Invalid URL format: {$url}");
        }
    }

    /**
     * Normalize URL components
     */
    private function normalizeUrl(array $parsedUrl, string $originalUrl): string
    {
        // Validate scheme
        $scheme = $parsedUrl['scheme'] ?? null;
        if (!$scheme) {
            // Try to add https by default
            $scheme = 'https';
            $originalUrl = "https://{$originalUrl}";
            $parsedUrl = parse_url($originalUrl);
        }

        if (!in_array(strtolower($scheme), $this->allowedSchemes)) {
            throw new UrlValidationException("Unsupported URL scheme: {$scheme}. Only HTTP and HTTPS are allowed.");
        }

        // Validate host
        $host = $parsedUrl['host'] ?? null;
        if (!$host || empty(trim($host))) {
            throw new UrlValidationException("URL must contain a valid host");
        }

        // Normalize host (lowercase)
        $host = strtolower(trim($host));

        // Normalize scheme (lowercase)
        $scheme = strtolower($scheme);

        // Validate port
        $port = $parsedUrl['port'] ?? null;
        if ($port !== null && ($port < 1 || $port > 65535)) {
            throw new UrlValidationException("Invalid port number: {$port}");
        }

        // Normalize path
        $path = $parsedUrl['path'] ?? '/';
        if (empty($path)) {
            $path = '/';
        }

        // URL-encode path components properly
        $pathParts = explode('/', $path);
        $pathParts = array_map(function($part) {
            return rawurlencode(rawurldecode($part));
        }, $pathParts);
        $path = implode('/', $pathParts);

        // Build normalized URL
        $normalizedUrl = $scheme . '://' . $host;

        if ($port !== null) {
            // Only include port if it's not the default for the scheme
            if (($scheme === 'http' && $port !== 80) || ($scheme === 'https' && $port !== 443)) {
                $normalizedUrl .= ':' . $port;
            }
        }

        $normalizedUrl .= $path;

        // Add query string if present
        if (!empty($parsedUrl['query'])) {
            $normalizedUrl .= '?' . $parsedUrl['query'];
        }

        // Add fragment if present (though usually not needed for crawling)
        if (!empty($parsedUrl['fragment'])) {
            $normalizedUrl .= '#' . $parsedUrl['fragment'];
        }

        return $normalizedUrl;
    }

    /**
     * Validate URL security
     */
    private function validateSecurity(string $url): void
    {
        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'];

        // Check blocked domains
        if (in_array(strtolower($host), $this->blockedDomains)) {
            throw new UrlValidationException("Access to localhost/internal URLs is not allowed: {$host}");
        }

        // Check for private IP addresses
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if (!filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new UrlValidationException("Access to private/reserved IP addresses is not allowed: {$host}");
            }
        }

        // Check blocked TLDs
        $hostParts = explode('.', $host);
        $tld = strtolower(end($hostParts));
        if (in_array($tld, $this->blockedTlds)) {
            throw new UrlValidationException("Access to {$tld} domains is not allowed");
        }

        // Additional security checks
        $this->validateForSuspiciousPatterns($url);
    }

    /**
     * Validate for suspicious URL patterns
     */
    private function validateForSuspiciousPatterns(string $url): void
    {
        $suspiciousPatterns = [
            '/\.\./i', // Directory traversal
            '/file:\/\//i', // File protocol
            '/javascript:/i', // JavaScript protocol
            '/data:/i', // Data protocol
            '/@/i' // @ symbol (potential credential injection)
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $url)) {
                throw new UrlValidationException("URL contains suspicious patterns and is blocked for security reasons");
            }
        }
    }

    /**
     * Validate URL accessibility (basic check)
     */
    private function validateAccessibility(string $url): void
    {
        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'];

        // Check if domain exists (basic DNS check)
        if (!$this->isDomainResolvable($host)) {
            throw new UrlValidationException("Domain does not exist or is not resolvable: {$host}");
        }
    }

    /**
     * Check if domain is resolvable
     */
    private function isDomainResolvable(string $domain): bool
    {
        // Skip IP address validation
        if (filter_var($domain, FILTER_VALIDATE_IP)) {
            return true;
        }

        try {
            $records = dns_get_record($domain, DNS_A | DNS_AAAA);
            return !empty($records);
        } catch (\Exception $e) {
            Log::warning('DNS resolution failed', [
                'domain' => $domain,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Extract domain from URL
     */
    public function extractDomain(string $url): string
    {
        $parsedUrl = parse_url($url);
        return $parsedUrl['host'] ?? '';
    }

    /**
     * Extract protocol from URL
     */
    public function extractProtocol(string $url): string
    {
        $parsedUrl = parse_url($url);
        return $parsedUrl['scheme'] ?? '';
    }

    /**
     * Check if URL is HTTPS
     */
    public function isHttps(string $url): bool
    {
        return $this->extractProtocol($url) === 'https';
    }

    /**
     * Convert HTTP URL to HTTPS
     */
    public function forceHttps(string $url): string
    {
        return str_replace('http://', 'https://', $url);
    }

    /**
     * Validate multiple URLs
     */
    public function validateBatch(array $urls): array
    {
        $results = [];
        $errors = [];

        foreach ($urls as $url) {
            try {
                $results[$url] = $this->validate($url);
            } catch (UrlValidationException $e) {
                $errors[$url] = $e->getMessage();
            }
        }

        return [
            'valid_urls' => $results,
            'errors' => $errors,
            'summary' => [
                'total_urls' => count($urls),
                'valid' => count($results),
                'invalid' => count($errors)
            ]
        ];
    }

    /**
     * Get URL validation rules and limits
     */
    public function getValidationRules(): array
    {
        return [
            'allowed_schemes' => $this->allowedSchemes,
            'blocked_domains' => $this->blockedDomains,
            'blocked_tlds' => $this->blockedTlds,
            'max_url_length' => $this->maxUrlLength,
            'security_checks' => [
                'private_ip_addresses' => true,
                'directory_traversal' => true,
                'suspicious_protocols' => true,
                'credential_injection' => true
            ]
        ];
    }

    /**
     * Parse URL into components with validation
     */
    public function parseUrl(string $url): array
    {
        $validatedUrl = $this->validate($url);
        $parsedUrl = parse_url($validatedUrl);

        return [
            'full_url' => $validatedUrl,
            'scheme' => $parsedUrl['scheme'] ?? '',
            'host' => $parsedUrl['host'] ?? '',
            'port' => $parsedUrl['port'] ?? null,
            'path' => $parsedUrl['path'] ?? '/',
            'query' => $parsedUrl['query'] ?? '',
            'fragment' => $parsedUrl['fragment'] ?? '',
            'domain' => $this->extractDomain($validatedUrl),
            'is_https' => $this->isHttps($validatedUrl),
            'base_url' => $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . ($parsedUrl['port'] ? ':' . $parsedUrl['port'] : '')
        ];
    }
}

/**
 * Custom exception for URL validation errors
 */
class UrlValidationException extends \Exception
{
    //
}