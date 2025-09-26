<?php

namespace App\Services\Technical;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use DOMDocument;
use DOMXPath;
use Exception;

/**
 * Canonical URL analysis service
 * Detects and validates canonical URLs, identifies duplicate content issues,
 * and analyzes URL structure optimization
 */
class CanonicalUrlService
{
    private int $timeout = 15;

    /**
     * Analyze canonical URLs and related issues
     */
    public function analyze(string $url, string $html, array $options = []): array
    {
        Log::info('Starting canonical URL analysis', ['url' => $url]);

        $results = [
            'url' => $url,
            'analyzed_at' => now()->toISOString(),
            'canonical_score' => 0,
            'canonical_analysis' => [],
            'url_structure' => [],
            'duplicate_content' => [],
            'redirect_analysis' => [],
            'parameter_analysis' => [],
            'recommendations' => []
        ];

        // Parse HTML for analysis
        $dom = $this->initializeDom($html);
        if (!$dom) {
            $results['recommendations'][] = [
                'type' => 'error',
                'message' => 'Unable to parse HTML for canonical analysis',
                'impact' => 'high',
                'fix' => 'Ensure valid HTML structure'
            ];
            return $results;
        }

        $xpath = new DOMXPath($dom);

        // Analyze canonical tags
        $results['canonical_analysis'] = $this->analyzeCanonicalTags($url, $xpath);

        // Analyze URL structure
        $results['url_structure'] = $this->analyzeUrlStructure($url);

        // Test for duplicate content indicators
        $results['duplicate_content'] = $this->analyzeDuplicateContentIndicators($url, $html, $options);

        // Analyze redirects
        try {
            $results['redirect_analysis'] = $this->analyzeRedirects($url);
        } catch (Exception $e) {
            Log::warning('Redirect analysis failed', ['error' => $e->getMessage()]);
            $results['redirect_analysis'] = ['error' => $e->getMessage()];
        }

        // Analyze URL parameters
        $results['parameter_analysis'] = $this->analyzeUrlParameters($url);

        // Calculate canonical score
        $results['canonical_score'] = $this->calculateCanonicalScore($results);

        // Generate recommendations
        $results['recommendations'] = $this->generateCanonicalRecommendations($results);

        Log::info('Canonical URL analysis completed', [
            'url' => $url,
            'canonical_score' => $results['canonical_score']
        ]);

        return $results;
    }

    /**
     * Analyze canonical tags in HTML
     */
    private function analyzeCanonicalTags(string $url, DOMXPath $xpath): array
    {
        $analysis = [
            'has_canonical' => false,
            'canonical_url' => null,
            'is_self_referencing' => false,
            'multiple_canonicals' => false,
            'canonical_issues' => [],
            'http_equiv_canonical' => false,
            'relative_canonical' => false
        ];

        // Find link rel="canonical"
        $canonicalLinks = $xpath->query('//link[@rel="canonical"]');
        $canonicalCount = $canonicalLinks->length;

        if ($canonicalCount > 0) {
            $analysis['has_canonical'] = true;
            $canonicalUrl = $canonicalLinks->item(0)->getAttribute('href');
            $analysis['canonical_url'] = $canonicalUrl;

            // Check for multiple canonical tags
            if ($canonicalCount > 1) {
                $analysis['multiple_canonicals'] = true;
                $analysis['canonical_issues'][] = "Multiple canonical tags found ({$canonicalCount})";

                // Collect all canonical URLs
                $allCanonicals = [];
                foreach ($canonicalLinks as $link) {
                    $allCanonicals[] = $link->getAttribute('href');
                }
                $analysis['all_canonical_urls'] = $allCanonicals;
            }

            // Validate canonical URL
            $canonicalValidation = $this->validateCanonicalUrl($url, $canonicalUrl);
            $analysis = array_merge($analysis, $canonicalValidation);

            // Check for relative canonical
            if (!filter_var($canonicalUrl, FILTER_VALIDATE_URL)) {
                $analysis['relative_canonical'] = true;
                if (!str_starts_with($canonicalUrl, '/')) {
                    $analysis['canonical_issues'][] = 'Canonical URL is relative but not absolute path';
                }
            }
        }

        // Check for HTTP-EQUIV canonical (rare but possible)
        $httpEquivCanonical = $xpath->query('//meta[@http-equiv="canonical"]');
        if ($httpEquivCanonical->length > 0) {
            $analysis['http_equiv_canonical'] = true;
            $analysis['canonical_issues'][] = 'HTTP-EQUIV canonical found (non-standard)';
        }

        // Check for JavaScript-set canonical (basic detection)
        if (str_contains($html ?? '', 'rel="canonical"') && !$analysis['has_canonical']) {
            $analysis['canonical_issues'][] = 'Possible JavaScript-set canonical detected';
        }

        return $analysis;
    }

    /**
     * Validate canonical URL
     */
    private function validateCanonicalUrl(string $currentUrl, string $canonicalUrl): array
    {
        $validation = [
            'is_valid_url' => false,
            'is_self_referencing' => false,
            'canonical_accessible' => false,
            'protocol_mismatch' => false,
            'domain_mismatch' => false,
            'canonical_issues' => []
        ];

        // Make canonical URL absolute if relative
        if (!filter_var($canonicalUrl, FILTER_VALIDATE_URL)) {
            if (str_starts_with($canonicalUrl, '/')) {
                $currentParts = parse_url($currentUrl);
                $canonicalUrl = $currentParts['scheme'] . '://' . $currentParts['host'] .
                               (isset($currentParts['port']) ? ':' . $currentParts['port'] : '') . $canonicalUrl;
            } else {
                $validation['canonical_issues'][] = 'Invalid canonical URL format';
                return $validation;
            }
        }

        $validation['is_valid_url'] = filter_var($canonicalUrl, FILTER_VALIDATE_URL) !== false;

        if (!$validation['is_valid_url']) {
            $validation['canonical_issues'][] = 'Canonical URL is not a valid URL';
            return $validation;
        }

        // Parse URLs for comparison
        $currentParts = parse_url($currentUrl);
        $canonicalParts = parse_url($canonicalUrl);

        // Check if self-referencing
        $validation['is_self_referencing'] = $this->normalizeUrl($currentUrl) === $this->normalizeUrl($canonicalUrl);

        // Check protocol mismatch
        if ($currentParts['scheme'] !== $canonicalParts['scheme']) {
            $validation['protocol_mismatch'] = true;
            if ($currentParts['scheme'] === 'https' && $canonicalParts['scheme'] === 'http') {
                $validation['canonical_issues'][] = 'Canonical points to HTTP while current page is HTTPS';
            }
        }

        // Check domain mismatch
        if ($currentParts['host'] !== $canonicalParts['host']) {
            $validation['domain_mismatch'] = true;
            $validation['canonical_issues'][] = 'Canonical URL points to different domain';
        }

        // Test canonical URL accessibility
        try {
            $response = Http::timeout($this->timeout)->head($canonicalUrl);
            $validation['canonical_accessible'] = $response->successful();
            $validation['canonical_status_code'] = $response->status();

            if (!$response->successful()) {
                $validation['canonical_issues'][] = "Canonical URL returns HTTP {$response->status()}";
            }
        } catch (Exception $e) {
            $validation['canonical_accessible'] = false;
            $validation['canonical_issues'][] = 'Cannot access canonical URL: ' . $e->getMessage();
        }

        return $validation;
    }

    /**
     * Analyze URL structure
     */
    private function analyzeUrlStructure(string $url): array
    {
        $parts = parse_url($url);
        $path = $parts['path'] ?? '/';
        $query = $parts['query'] ?? '';

        $params = [];
        if ($query) {
            parse_str($query, $params);
        }

        return [
            'url_length' => strlen($url),
            'path_segments' => array_filter(explode('/', trim($path, '/'))),
            'path_depth' => count(array_filter(explode('/', trim($path, '/')))),
            'has_query_parameters' => !empty($query),
            'query_parameters' => $params,
            'parameter_count' => count($params),
            'has_fragment' => isset($parts['fragment']),
            'fragment' => $parts['fragment'] ?? null,
            'contains_uppercase' => $url !== strtolower($url),
            'contains_spaces' => str_contains($url, ' '),
            'contains_special_chars' => $this->containsSpecialCharacters($url),
            'seo_friendly' => $this->isUrlSeoFriendly($url),
            'readability_score' => $this->calculateUrlReadabilityScore($url)
        ];
    }

    /**
     * Analyze duplicate content indicators
     */
    private function analyzeDuplicateContentIndicators(string $url, string $html, array $options): array
    {
        $analysis = [
            'url_variations' => [],
            'parameter_variations' => [],
            'content_hash' => md5(strip_tags($html)),
            'title_hash' => null,
            'meta_description_hash' => null,
            'potential_duplicates' => []
        ];

        // Extract title and meta description for hashing
        if (preg_match('/<title[^>]*>(.+?)<\/title>/i', $html, $matches)) {
            $analysis['title_hash'] = md5(trim($matches[1]));
        }

        if (preg_match('/<meta\s+name=["\']description["\']\s+content=["\'](.+?)["\']/i', $html, $matches)) {
            $analysis['meta_description_hash'] = md5(trim($matches[1]));
        }

        // Generate common URL variations
        $analysis['url_variations'] = $this->generateUrlVariations($url);

        // Analyze parameters that might create duplicates
        $urlParts = parse_url($url);
        if (isset($urlParts['query'])) {
            $analysis['parameter_variations'] = $this->analyzeParameterDuplication($url);
        }

        return $analysis;
    }

    /**
     * Generate common URL variations
     */
    private function generateUrlVariations(string $url): array
    {
        $parts = parse_url($url);
        $baseUrl = $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
        $path = $parts['path'] ?? '/';
        $query = $parts['query'] ?? '';

        $variations = [];

        // Trailing slash variations
        if (str_ends_with($path, '/')) {
            $variations['without_trailing_slash'] = $baseUrl . rtrim($path, '/') . ($query ? '?' . $query : '');
        } else {
            $variations['with_trailing_slash'] = $baseUrl . $path . '/' . ($query ? '?' . $query : '');
        }

        // WWW variations
        $host = $parts['host'];
        if (str_starts_with($host, 'www.')) {
            $nonWwwHost = substr($host, 4);
            $variations['without_www'] = $parts['scheme'] . '://' . $nonWwwHost .
                                       (isset($parts['port']) ? ':' . $parts['port'] : '') .
                                       $path . ($query ? '?' . $query : '');
        } else {
            $variations['with_www'] = $parts['scheme'] . '://www.' . $host .
                                    (isset($parts['port']) ? ':' . $parts['port'] : '') .
                                    $path . ($query ? '?' . $query : '');
        }

        // Protocol variations
        if ($parts['scheme'] === 'https') {
            $variations['http_version'] = 'http://' . $host .
                                        (isset($parts['port']) ? ':' . $parts['port'] : '') .
                                        $path . ($query ? '?' . $query : '');
        } else {
            $variations['https_version'] = 'https://' . $host .
                                         (isset($parts['port']) ? ':' . $parts['port'] : '') .
                                         $path . ($query ? '?' . $query : '');
        }

        // Case variations (if URL contains uppercase)
        if ($url !== strtolower($url)) {
            $variations['lowercase'] = strtolower($url);
        }

        return $variations;
    }

    /**
     * Analyze URL parameters for duplication potential
     */
    private function analyzeParameterDuplication(string $url): array
    {
        $parts = parse_url($url);
        $query = $parts['query'] ?? '';

        if (empty($query)) {
            return [];
        }

        parse_str($query, $parameters);

        $analysis = [
            'total_parameters' => count($parameters),
            'seo_parameters' => [],
            'tracking_parameters' => [],
            'session_parameters' => [],
            'duplication_risk' => 'low'
        ];

        // Common parameter categories
        $seoParams = ['page', 'sort', 'filter', 'category', 'search', 'q'];
        $trackingParams = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'fbclid'];
        $sessionParams = ['sid', 'session', 'token', 'csrf', 'timestamp'];

        foreach ($parameters as $key => $value) {
            $lowerKey = strtolower($key);

            if (in_array($lowerKey, $seoParams)) {
                $analysis['seo_parameters'][] = $key;
            } elseif (in_array($lowerKey, $trackingParams)) {
                $analysis['tracking_parameters'][] = $key;
            } elseif (in_array($lowerKey, $sessionParams)) {
                $analysis['session_parameters'][] = $key;
            }
        }

        // Assess duplication risk
        if (count($analysis['tracking_parameters']) > 3 || count($analysis['session_parameters']) > 0) {
            $analysis['duplication_risk'] = 'high';
        } elseif (count($parameters) > 5 || count($analysis['tracking_parameters']) > 0) {
            $analysis['duplication_risk'] = 'medium';
        }

        return $analysis;
    }

    /**
     * Analyze redirects
     */
    private function analyzeRedirects(string $url): array
    {
        $analysis = [
            'redirect_chain' => [],
            'final_url' => $url,
            'redirect_count' => 0,
            'has_redirects' => false,
            'redirect_types' => [],
            'max_redirects_reached' => false
        ];

        $currentUrl = $url;
        $maxRedirects = 10;
        $redirectCount = 0;

        try {
            while ($redirectCount < $maxRedirects) {
                $response = Http::timeout($this->timeout)->withoutRedirecting()->get($currentUrl);
                $statusCode = $response->status();

                $analysis['redirect_chain'][] = [
                    'url' => $currentUrl,
                    'status_code' => $statusCode,
                    'redirect_type' => $this->getRedirectType($statusCode)
                ];

                if (in_array($statusCode, [301, 302, 303, 307, 308])) {
                    $location = $response->header('Location');
                    if (!$location) {
                        break;
                    }

                    // Make location absolute if relative
                    if (!filter_var($location, FILTER_VALIDATE_URL)) {
                        $urlParts = parse_url($currentUrl);
                        $location = $urlParts['scheme'] . '://' . $urlParts['host'] .
                                   (isset($urlParts['port']) ? ':' . $urlParts['port'] : '') . $location;
                    }

                    $analysis['redirect_types'][] = $statusCode;
                    $currentUrl = $location;
                    $redirectCount++;
                } else {
                    break;
                }
            }

            if ($redirectCount >= $maxRedirects) {
                $analysis['max_redirects_reached'] = true;
            }

            $analysis['final_url'] = $currentUrl;
            $analysis['redirect_count'] = $redirectCount;
            $analysis['has_redirects'] = $redirectCount > 0;

        } catch (Exception $e) {
            $analysis['error'] = $e->getMessage();
        }

        return $analysis;
    }

    /**
     * Analyze URL parameters
     */
    private function analyzeUrlParameters(string $url): array
    {
        $parts = parse_url($url);
        $query = $parts['query'] ?? '';

        if (empty($query)) {
            return [
                'has_parameters' => false,
                'parameter_count' => 0
            ];
        }

        parse_str($query, $parameters);

        return [
            'has_parameters' => true,
            'parameter_count' => count($parameters),
            'parameters' => array_keys($parameters),
            'parameter_analysis' => $this->categorizeParameters($parameters)
        ];
    }

    /**
     * Calculate canonical score
     */
    private function calculateCanonicalScore(array $results): int
    {
        $score = 0;

        // Canonical tag presence and validity (40%)
        $canonicalAnalysis = $results['canonical_analysis'];
        if ($canonicalAnalysis['has_canonical']) {
            $score += 20;

            if (empty($canonicalAnalysis['canonical_issues'])) {
                $score += 20;
            } else {
                $score += 10; // Partial credit for having canonical with issues
            }

            // Bonus for self-referencing canonical
            if ($canonicalAnalysis['is_self_referencing']) {
                $score += 10;
            }
        }

        // URL structure (30%)
        $urlStructure = $results['url_structure'];
        if ($urlStructure['seo_friendly']) {
            $score += 15;
        }
        $score += min(15, $urlStructure['readability_score'] * 0.15);

        // Redirect handling (20%)
        $redirectAnalysis = $results['redirect_analysis'];
        if (!isset($redirectAnalysis['error'])) {
            if (!$redirectAnalysis['has_redirects']) {
                $score += 20; // No redirects is good
            } elseif ($redirectAnalysis['redirect_count'] === 1) {
                $score += 15; // Single redirect is acceptable
            } elseif ($redirectAnalysis['redirect_count'] <= 3) {
                $score += 10; // Multiple redirects but manageable
            }
        }

        // Duplicate content risk (10%)
        $duplicateAnalysis = $results['duplicate_content'];
        $parameterAnalysis = $results['parameter_analysis'];

        $riskScore = 10;
        if (isset($parameterAnalysis['parameter_analysis']['duplication_risk'])) {
            switch ($parameterAnalysis['parameter_analysis']['duplication_risk']) {
                case 'high':
                    $riskScore = 0;
                    break;
                case 'medium':
                    $riskScore = 5;
                    break;
                case 'low':
                    $riskScore = 10;
                    break;
            }
        }
        $score += $riskScore;

        return min(100, max(0, $score));
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

    private function normalizeUrl(string $url): string
    {
        // Basic URL normalization for comparison
        $url = strtolower($url);
        $url = rtrim($url, '/');
        return $url;
    }

    private function containsSpecialCharacters(string $url): bool
    {
        return preg_match('/[^a-zA-Z0-9\-._~:\/\?#\[\]@!$&\'()*+,;=%]/', $url) > 0;
    }

    private function isUrlSeoFriendly(string $url): bool
    {
        $parts = parse_url($url);
        $path = $parts['path'] ?? '/';

        // Check for SEO-friendly characteristics
        $seoFriendly = true;

        // Contains readable words separated by hyphens
        if (!preg_match('/\/[a-z0-9-]+/', $path)) {
            $seoFriendly = false;
        }

        // Avoid query parameters for main content
        if (str_contains($path, '?') && str_contains($url, 'id=')) {
            $seoFriendly = false;
        }

        // Avoid uppercase letters
        if ($url !== strtolower($url)) {
            $seoFriendly = false;
        }

        return $seoFriendly;
    }

    private function calculateUrlReadabilityScore(string $url): float
    {
        $parts = parse_url($url);
        $path = $parts['path'] ?? '/';

        $score = 100;

        // Length penalty
        if (strlen($url) > 100) {
            $score -= 20;
        } elseif (strlen($url) > 75) {
            $score -= 10;
        }

        // Depth penalty
        $depth = substr_count(trim($path, '/'), '/');
        if ($depth > 5) {
            $score -= 20;
        } elseif ($depth > 3) {
            $score -= 10;
        }

        // Special characters penalty
        if ($this->containsSpecialCharacters($url)) {
            $score -= 15;
        }

        // Parameter penalty
        if (isset($parts['query']) && !empty($parts['query'])) {
            $paramCount = count(explode('&', $parts['query']));
            $score -= min(15, $paramCount * 3);
        }

        // Readability bonus for hyphens
        if (str_contains($path, '-')) {
            $score += 10;
        }

        return max(0, min(100, $score));
    }

    private function getRedirectType(int $statusCode): string
    {
        return match($statusCode) {
            301 => 'Permanent Redirect',
            302 => 'Temporary Redirect',
            303 => 'See Other',
            307 => 'Temporary Redirect (HTTP/1.1)',
            308 => 'Permanent Redirect (HTTP/1.1)',
            default => 'Unknown'
        };
    }

    private function categorizeParameters(array $parameters): array
    {
        $categories = [
            'seo' => [],
            'tracking' => [],
            'session' => [],
            'social' => [],
            'other' => []
        ];

        $categoryMap = [
            'seo' => ['page', 'sort', 'filter', 'category', 'search', 'q', 'keyword'],
            'tracking' => ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'fbclid', 'ref'],
            'session' => ['sid', 'session', 'token', 'csrf', 'timestamp', 'nonce'],
            'social' => ['share', 'social', 'fb', 'twitter', 'linkedin']
        ];

        foreach ($parameters as $key => $value) {
            $lowerKey = strtolower($key);
            $categorized = false;

            foreach ($categoryMap as $category => $keywords) {
                foreach ($keywords as $keyword) {
                    if (str_contains($lowerKey, $keyword)) {
                        $categories[$category][] = $key;
                        $categorized = true;
                        break 2;
                    }
                }
            }

            if (!$categorized) {
                $categories['other'][] = $key;
            }
        }

        return $categories;
    }

    /**
     * Generate canonical recommendations
     */
    private function generateCanonicalRecommendations(array $results): array
    {
        $recommendations = [];

        $canonicalAnalysis = $results['canonical_analysis'];

        // Canonical tag recommendations
        if (!$canonicalAnalysis['has_canonical']) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => 'Missing canonical tag',
                'impact' => 'medium',
                'fix' => 'Add a rel="canonical" link tag to specify the preferred version of this page'
            ];
        } else {
            if ($canonicalAnalysis['multiple_canonicals']) {
                $recommendations[] = [
                    'type' => 'error',
                    'message' => 'Multiple canonical tags found',
                    'impact' => 'high',
                    'fix' => 'Remove duplicate canonical tags - only one canonical URL should be specified'
                ];
            }

            foreach ($canonicalAnalysis['canonical_issues'] as $issue) {
                $recommendations[] = [
                    'type' => 'warning',
                    'message' => "Canonical issue: {$issue}",
                    'impact' => 'medium',
                    'fix' => 'Fix the canonical URL implementation'
                ];
            }
        }

        // URL structure recommendations
        $urlStructure = $results['url_structure'];

        if ($urlStructure['url_length'] > 100) {
            $recommendations[] = [
                'type' => 'info',
                'message' => 'URL is quite long',
                'impact' => 'low',
                'fix' => 'Consider shortening the URL for better user experience'
            ];
        }

        if (!$urlStructure['seo_friendly']) {
            $recommendations[] = [
                'type' => 'info',
                'message' => 'URL structure could be more SEO-friendly',
                'impact' => 'low',
                'fix' => 'Use descriptive words separated by hyphens in URLs'
            ];
        }

        if ($urlStructure['contains_uppercase']) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => 'URL contains uppercase letters',
                'impact' => 'medium',
                'fix' => 'Use lowercase letters in URLs to avoid duplicate content issues'
            ];
        }

        // Redirect recommendations
        $redirectAnalysis = $results['redirect_analysis'];

        if (isset($redirectAnalysis['redirect_count']) && $redirectAnalysis['redirect_count'] > 3) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => 'Multiple redirects in chain',
                'impact' => 'medium',
                'fix' => 'Reduce redirect chain length to improve page load speed'
            ];
        }

        if (isset($redirectAnalysis['max_redirects_reached']) && $redirectAnalysis['max_redirects_reached']) {
            $recommendations[] = [
                'type' => 'error',
                'message' => 'Redirect chain is too long',
                'impact' => 'high',
                'fix' => 'Fix redirect loop or reduce redirect chain length'
            ];
        }

        // Parameter recommendations
        $parameterAnalysis = $results['parameter_analysis'];

        if (isset($parameterAnalysis['parameter_analysis']['duplication_risk'])) {
            $risk = $parameterAnalysis['parameter_analysis']['duplication_risk'];
            if ($risk === 'high') {
                $recommendations[] = [
                    'type' => 'warning',
                    'message' => 'High duplicate content risk from URL parameters',
                    'impact' => 'high',
                    'fix' => 'Use canonical tags or URL parameter handling to prevent duplicate content'
                ];
            } elseif ($risk === 'medium') {
                $recommendations[] = [
                    'type' => 'info',
                    'message' => 'Medium duplicate content risk from URL parameters',
                    'impact' => 'medium',
                    'fix' => 'Consider implementing parameter handling to avoid duplicate content'
                ];
            }
        }

        return $recommendations;
    }
}