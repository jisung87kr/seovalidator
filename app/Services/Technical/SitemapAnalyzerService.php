<?php

namespace App\Services\Technical;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;
use SimpleXMLElement;

/**
 * Sitemap and robots.txt analysis service
 * Analyzes sitemap accessibility, structure, and robots.txt directives
 */
class SitemapAnalyzerService
{
    private int $timeout = 20;
    private int $cacheMinutes = 120; // Cache for 2 hours
    private int $maxSitemapSize = 50 * 1024 * 1024; // 50MB max
    private int $maxUrlsToAnalyze = 1000; // Limit URL analysis

    /**
     * Analyze sitemap and robots.txt
     */
    public function analyze(string $url, array $options = []): array
    {
        Log::info('Starting sitemap analysis', ['url' => $url]);

        $cacheKey = "sitemap:" . md5($url);

        if (!($options['force_refresh'] ?? false)) {
            $cached = Cache::get($cacheKey);
            if ($cached) {
                Log::debug('Returning cached sitemap results', ['url' => $url]);
                return $cached;
            }
        }

        $results = [
            'url' => $url,
            'analyzed_at' => now()->toISOString(),
            'sitemap_score' => 0,
            'robots_txt' => [],
            'sitemaps' => [],
            'sitemap_index' => [],
            'url_analysis' => [],
            'accessibility' => [],
            'recommendations' => [],
            'errors' => []
        ];

        $baseUrl = $this->getBaseUrl($url);

        // Analyze robots.txt
        try {
            $results['robots_txt'] = $this->analyzeRobotsTxt($baseUrl);
        } catch (Exception $e) {
            Log::warning('Robots.txt analysis failed', ['error' => $e->getMessage()]);
            $results['errors'][] = 'Robots.txt analysis failed: ' . $e->getMessage();
        }

        // Find and analyze sitemaps
        $sitemapUrls = $this->discoverSitemaps($baseUrl, $results['robots_txt']);

        foreach ($sitemapUrls as $sitemapUrl) {
            try {
                Log::debug('Analyzing sitemap', ['sitemap_url' => $sitemapUrl]);
                $sitemapAnalysis = $this->analyzeSitemap($sitemapUrl, $options);

                if ($sitemapAnalysis['type'] === 'sitemap_index') {
                    $results['sitemap_index'][] = $sitemapAnalysis;
                } else {
                    $results['sitemaps'][] = $sitemapAnalysis;
                }

            } catch (Exception $e) {
                Log::warning('Sitemap analysis failed', [
                    'sitemap_url' => $sitemapUrl,
                    'error' => $e->getMessage()
                ]);
                $results['errors'][] = "Sitemap analysis failed for {$sitemapUrl}: " . $e->getMessage();
            }
        }

        // Analyze URL accessibility
        try {
            $results['url_analysis'] = $this->analyzeUrlAccessibility($results['sitemaps'], $options);
        } catch (Exception $e) {
            Log::warning('URL accessibility analysis failed', ['error' => $e->getMessage()]);
            $results['errors'][] = 'URL accessibility analysis failed: ' . $e->getMessage();
        }

        // Analyze overall accessibility
        $results['accessibility'] = $this->analyzeOverallAccessibility($results);

        // Calculate sitemap score
        $results['sitemap_score'] = $this->calculateSitemapScore($results);

        // Generate recommendations
        $results['recommendations'] = $this->generateSitemapRecommendations($results);

        Cache::put($cacheKey, $results, $this->cacheMinutes * 60);

        Log::info('Sitemap analysis completed', [
            'url' => $url,
            'sitemap_score' => $results['sitemap_score'],
            'sitemaps_found' => count($results['sitemaps']),
            'errors' => count($results['errors'])
        ]);

        return $results;
    }

    /**
     * Analyze robots.txt file
     */
    private function analyzeRobotsTxt(string $baseUrl): array
    {
        $robotsUrl = rtrim($baseUrl, '/') . '/robots.txt';

        try {
            $response = Http::timeout($this->timeout)->get($robotsUrl);

            $analysis = [
                'url' => $robotsUrl,
                'accessible' => $response->successful(),
                'status_code' => $response->status(),
                'content' => '',
                'size' => 0,
                'user_agents' => [],
                'sitemaps' => [],
                'disallowed_paths' => [],
                'allowed_paths' => [],
                'crawl_delay' => null,
                'syntax_errors' => [],
                'directives_count' => 0
            ];

            if ($response->successful()) {
                $content = $response->body();
                $analysis['content'] = $content;
                $analysis['size'] = strlen($content);

                // Parse robots.txt content
                $this->parseRobotsTxt($content, $analysis);
            }

            return $analysis;

        } catch (Exception $e) {
            return [
                'url' => $robotsUrl,
                'accessible' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Parse robots.txt content
     */
    private function parseRobotsTxt(string $content, array &$analysis): void
    {
        $lines = explode("\n", $content);
        $currentUserAgent = '*';

        foreach ($lines as $lineNumber => $line) {
            $line = trim($line);

            // Skip empty lines and comments
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            $analysis['directives_count']++;

            // Parse directive
            if (str_contains($line, ':')) {
                [$directive, $value] = explode(':', $line, 2);
                $directive = strtolower(trim($directive));
                $value = trim($value);

                switch ($directive) {
                    case 'user-agent':
                        $currentUserAgent = $value;
                        if (!isset($analysis['user_agents'][$currentUserAgent])) {
                            $analysis['user_agents'][$currentUserAgent] = [
                                'disallow' => [],
                                'allow' => [],
                                'crawl_delay' => null
                            ];
                        }
                        break;

                    case 'disallow':
                        if (!empty($value)) {
                            $analysis['user_agents'][$currentUserAgent]['disallow'][] = $value;
                            $analysis['disallowed_paths'][] = $value;
                        }
                        break;

                    case 'allow':
                        if (!empty($value)) {
                            $analysis['user_agents'][$currentUserAgent]['allow'][] = $value;
                            $analysis['allowed_paths'][] = $value;
                        }
                        break;

                    case 'sitemap':
                        if (filter_var($value, FILTER_VALIDATE_URL)) {
                            $analysis['sitemaps'][] = $value;
                        } else {
                            $analysis['syntax_errors'][] = "Line {$lineNumber}: Invalid sitemap URL - {$value}";
                        }
                        break;

                    case 'crawl-delay':
                        $delay = (int)$value;
                        if ($delay > 0) {
                            $analysis['user_agents'][$currentUserAgent]['crawl_delay'] = $delay;
                            if ($currentUserAgent === '*' || $analysis['crawl_delay'] === null) {
                                $analysis['crawl_delay'] = $delay;
                            }
                        } else {
                            $analysis['syntax_errors'][] = "Line {$lineNumber}: Invalid crawl-delay value - {$value}";
                        }
                        break;

                    default:
                        // Unknown directive
                        Log::debug('Unknown robots.txt directive', [
                            'directive' => $directive,
                            'value' => $value,
                            'line' => $lineNumber
                        ]);
                        break;
                }
            } else {
                $analysis['syntax_errors'][] = "Line {$lineNumber}: Invalid syntax - {$line}";
            }
        }

        // Remove duplicates
        $analysis['sitemaps'] = array_unique($analysis['sitemaps']);
        $analysis['disallowed_paths'] = array_unique($analysis['disallowed_paths']);
        $analysis['allowed_paths'] = array_unique($analysis['allowed_paths']);
    }

    /**
     * Discover sitemap URLs
     */
    private function discoverSitemaps(string $baseUrl, array $robotsAnalysis): array
    {
        $sitemapUrls = [];

        // From robots.txt
        if (isset($robotsAnalysis['sitemaps'])) {
            $sitemapUrls = array_merge($sitemapUrls, $robotsAnalysis['sitemaps']);
        }

        // Common sitemap locations
        $commonLocations = [
            '/sitemap.xml',
            '/sitemap_index.xml',
            '/sitemaps.xml',
            '/sitemap1.xml'
        ];

        foreach ($commonLocations as $path) {
            $sitemapUrl = rtrim($baseUrl, '/') . $path;

            // Skip if already found in robots.txt
            if (in_array($sitemapUrl, $sitemapUrls)) {
                continue;
            }

            try {
                $response = Http::timeout(5)->head($sitemapUrl);
                if ($response->successful()) {
                    $contentType = $response->header('Content-Type', '');
                    if (str_contains($contentType, 'xml') || str_contains($contentType, 'text')) {
                        $sitemapUrls[] = $sitemapUrl;
                    }
                }
            } catch (Exception $e) {
                // Ignore failures for common location testing
            }
        }

        return array_unique($sitemapUrls);
    }

    /**
     * Analyze individual sitemap
     */
    private function analyzeSitemap(string $sitemapUrl, array $options = []): array
    {
        try {
            $response = Http::timeout($this->timeout)->get($sitemapUrl);

            $analysis = [
                'url' => $sitemapUrl,
                'accessible' => $response->successful(),
                'status_code' => $response->status(),
                'content_type' => $response->header('Content-Type', ''),
                'size' => 0,
                'type' => 'unknown',
                'urls' => [],
                'child_sitemaps' => [],
                'last_modified' => null,
                'compression' => null,
                'validation' => [],
                'statistics' => []
            ];

            if (!$response->successful()) {
                $analysis['error'] = "HTTP {$response->status()}";
                return $analysis;
            }

            $content = $response->body();
            $analysis['size'] = strlen($content);

            // Detect compression
            $analysis['compression'] = $this->detectCompression($content, $response->headers());

            // Decompress if needed
            if ($analysis['compression']) {
                $content = $this->decompressContent($content, $analysis['compression']);
            }

            // Parse XML
            $this->parseXmlSitemap($content, $analysis, $options);

            return $analysis;

        } catch (Exception $e) {
            return [
                'url' => $sitemapUrl,
                'accessible' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Parse XML sitemap content
     */
    private function parseXmlSitemap(string $content, array &$analysis, array $options): void
    {
        libxml_use_internal_errors(true);

        try {
            $xml = new SimpleXMLElement($content);
            $analysis['validation']['valid_xml'] = true;

            // Determine sitemap type
            if ($xml->sitemap) {
                // Sitemap index
                $analysis['type'] = 'sitemap_index';
                $this->parseSitemapIndex($xml, $analysis);
            } elseif ($xml->url) {
                // URL sitemap
                $analysis['type'] = 'urlset';
                $this->parseUrlSitemap($xml, $analysis, $options);
            } else {
                $analysis['validation']['errors'][] = 'Unknown sitemap format';
            }

        } catch (Exception $e) {
            $analysis['validation']['valid_xml'] = false;
            $analysis['validation']['errors'][] = 'XML parsing error: ' . $e->getMessage();

            // Get libxml errors
            $xmlErrors = libxml_get_errors();
            foreach ($xmlErrors as $error) {
                $analysis['validation']['errors'][] = "Line {$error->line}: {$error->message}";
            }
        }

        libxml_clear_errors();
    }

    /**
     * Parse sitemap index
     */
    private function parseSitemapIndex(SimpleXMLElement $xml, array &$analysis): void
    {
        foreach ($xml->sitemap as $sitemap) {
            $childSitemap = [
                'loc' => (string)$sitemap->loc,
                'lastmod' => (string)$sitemap->lastmod
            ];

            $analysis['child_sitemaps'][] = $childSitemap;
        }

        $analysis['statistics'] = [
            'total_child_sitemaps' => count($analysis['child_sitemaps']),
            'with_lastmod' => count(array_filter($analysis['child_sitemaps'], fn($s) => !empty($s['lastmod'])))
        ];
    }

    /**
     * Parse URL sitemap
     */
    private function parseUrlSitemap(SimpleXMLElement $xml, array &$analysis, array $options): void
    {
        $urlCount = 0;
        $maxUrls = $options['max_urls_to_analyze'] ?? $this->maxUrlsToAnalyze;

        foreach ($xml->url as $url) {
            if ($urlCount >= $maxUrls) {
                break;
            }

            $urlData = [
                'loc' => (string)$url->loc,
                'lastmod' => (string)$url->lastmod,
                'changefreq' => (string)$url->changefreq,
                'priority' => (string)$url->priority
            ];

            $analysis['urls'][] = $urlData;
            $urlCount++;
        }

        // Calculate statistics
        $analysis['statistics'] = $this->calculateUrlStatistics($analysis['urls']);

        // Validate URL data
        $analysis['validation'] = array_merge(
            $analysis['validation'] ?? [],
            $this->validateUrlData($analysis['urls'])
        );
    }

    /**
     * Calculate URL statistics
     */
    private function calculateUrlStatistics(array $urls): array
    {
        $stats = [
            'total_urls' => count($urls),
            'with_lastmod' => 0,
            'with_changefreq' => 0,
            'with_priority' => 0,
            'changefreq_distribution' => [],
            'priority_distribution' => [],
            'recent_updates' => 0
        ];

        $thirtyDaysAgo = now()->subDays(30);

        foreach ($urls as $url) {
            if (!empty($url['lastmod'])) {
                $stats['with_lastmod']++;

                try {
                    $lastmod = \Carbon\Carbon::parse($url['lastmod']);
                    if ($lastmod->gt($thirtyDaysAgo)) {
                        $stats['recent_updates']++;
                    }
                } catch (Exception $e) {
                    // Invalid date format
                }
            }

            if (!empty($url['changefreq'])) {
                $stats['with_changefreq']++;
                $freq = $url['changefreq'];
                $stats['changefreq_distribution'][$freq] = ($stats['changefreq_distribution'][$freq] ?? 0) + 1;
            }

            if (!empty($url['priority'])) {
                $stats['with_priority']++;
                $priority = $url['priority'];
                $stats['priority_distribution'][$priority] = ($stats['priority_distribution'][$priority] ?? 0) + 1;
            }
        }

        return $stats;
    }

    /**
     * Validate URL data
     */
    private function validateUrlData(array $urls): array
    {
        $validation = [
            'errors' => [],
            'warnings' => []
        ];

        foreach ($urls as $index => $url) {
            // Validate URL
            if (!filter_var($url['loc'], FILTER_VALIDATE_URL)) {
                $validation['errors'][] = "URL {$index}: Invalid URL - {$url['loc']}";
            }

            // Validate changefreq
            if (!empty($url['changefreq'])) {
                $validChangefreqs = ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'];
                if (!in_array($url['changefreq'], $validChangefreqs)) {
                    $validation['warnings'][] = "URL {$index}: Invalid changefreq - {$url['changefreq']}";
                }
            }

            // Validate priority
            if (!empty($url['priority'])) {
                $priority = (float)$url['priority'];
                if ($priority < 0 || $priority > 1) {
                    $validation['warnings'][] = "URL {$index}: Priority out of range (0-1) - {$url['priority']}";
                }
            }

            // Validate lastmod
            if (!empty($url['lastmod'])) {
                try {
                    \Carbon\Carbon::parse($url['lastmod']);
                } catch (Exception $e) {
                    $validation['warnings'][] = "URL {$index}: Invalid lastmod format - {$url['lastmod']}";
                }
            }
        }

        return $validation;
    }

    /**
     * Analyze URL accessibility
     */
    private function analyzeUrlAccessibility(array $sitemaps, array $options): array
    {
        $analysis = [
            'total_urls' => 0,
            'tested_urls' => 0,
            'accessible_urls' => 0,
            'inaccessible_urls' => 0,
            'status_code_distribution' => [],
            'sample_inaccessible' => []
        ];

        $maxUrlsToTest = $options['max_accessibility_tests'] ?? 50;
        $testedCount = 0;

        foreach ($sitemaps as $sitemap) {
            if (!isset($sitemap['urls'])) continue;

            $analysis['total_urls'] += count($sitemap['urls']);

            // Test a sample of URLs
            $urlsToTest = array_slice($sitemap['urls'], 0, $maxUrlsToTest - $testedCount);

            foreach ($urlsToTest as $urlData) {
                if ($testedCount >= $maxUrlsToTest) break;

                try {
                    $response = Http::timeout(10)->head($urlData['loc']);
                    $statusCode = $response->status();

                    $analysis['tested_urls']++;
                    $analysis['status_code_distribution'][$statusCode] =
                        ($analysis['status_code_distribution'][$statusCode] ?? 0) + 1;

                    if ($response->successful()) {
                        $analysis['accessible_urls']++;
                    } else {
                        $analysis['inaccessible_urls']++;
                        if (count($analysis['sample_inaccessible']) < 10) {
                            $analysis['sample_inaccessible'][] = [
                                'url' => $urlData['loc'],
                                'status_code' => $statusCode
                            ];
                        }
                    }

                    $testedCount++;

                } catch (Exception $e) {
                    $analysis['tested_urls']++;
                    $analysis['inaccessible_urls']++;

                    if (count($analysis['sample_inaccessible']) < 10) {
                        $analysis['sample_inaccessible'][] = [
                            'url' => $urlData['loc'],
                            'error' => $e->getMessage()
                        ];
                    }

                    $testedCount++;
                }
            }
        }

        return $analysis;
    }

    /**
     * Analyze overall accessibility
     */
    private function analyzeOverallAccessibility(array $results): array
    {
        return [
            'robots_txt_accessible' => $results['robots_txt']['accessible'] ?? false,
            'robots_txt_has_sitemap' => !empty($results['robots_txt']['sitemaps'] ?? []),
            'total_sitemaps_found' => count($results['sitemaps']),
            'total_sitemap_indexes' => count($results['sitemap_index']),
            'sitemaps_accessible' => count(array_filter($results['sitemaps'], fn($s) => $s['accessible'])),
            'total_urls_in_sitemaps' => array_sum(array_map(fn($s) => $s['statistics']['total_urls'] ?? 0, $results['sitemaps'])),
            'accessibility_score' => $this->calculateAccessibilityScore($results)
        ];
    }

    /**
     * Calculate sitemap score
     */
    private function calculateSitemapScore(array $results): int
    {
        $score = 0;

        // Robots.txt presence and accessibility (20%)
        if ($results['robots_txt']['accessible'] ?? false) {
            $score += 20;

            // Bonus for sitemap reference in robots.txt
            if (!empty($results['robots_txt']['sitemaps'] ?? [])) {
                $score += 10;
            }
        }

        // Sitemap presence and accessibility (40%)
        $sitemapsCount = count($results['sitemaps']);
        if ($sitemapsCount > 0) {
            $score += 20;

            $accessibleSitemaps = count(array_filter($results['sitemaps'], fn($s) => $s['accessible']));
            if ($accessibleSitemaps === $sitemapsCount) {
                $score += 20; // All sitemaps accessible
            } else {
                $score += ($accessibleSitemaps / $sitemapsCount) * 20;
            }
        }

        // URL accessibility (25%)
        $urlAnalysis = $results['url_analysis'];
        if ($urlAnalysis['tested_urls'] > 0) {
            $accessibilityRate = $urlAnalysis['accessible_urls'] / $urlAnalysis['tested_urls'];
            $score += $accessibilityRate * 25;
        }

        // Sitemap quality (15%)
        $qualityScore = 0;
        foreach ($results['sitemaps'] as $sitemap) {
            if (isset($sitemap['statistics'])) {
                $stats = $sitemap['statistics'];

                // Bonus for lastmod usage
                if ($stats['with_lastmod'] > 0) {
                    $qualityScore += 5;
                }

                // Bonus for recent updates
                if ($stats['recent_updates'] > 0) {
                    $qualityScore += 5;
                }

                // Bonus for proper structure
                if (empty($sitemap['validation']['errors'] ?? [])) {
                    $qualityScore += 5;
                }
            }
        }
        $score += min(15, $qualityScore);

        return min(100, max(0, round($score)));
    }

    /**
     * Calculate accessibility score
     */
    private function calculateAccessibilityScore(array $results): int
    {
        $score = 0;

        if ($results['robots_txt']['accessible'] ?? false) $score += 25;
        if (($results['accessibility']['sitemaps_accessible'] ?? 0) > 0) $score += 35;

        $urlAnalysis = $results['url_analysis'];
        if ($urlAnalysis['tested_urls'] > 0) {
            $accessibilityRate = $urlAnalysis['accessible_urls'] / $urlAnalysis['tested_urls'];
            $score += $accessibilityRate * 40;
        }

        return min(100, max(0, round($score)));
    }

    // Helper methods

    private function getBaseUrl(string $url): string
    {
        $parts = parse_url($url);
        return $parts['scheme'] . '://' . $parts['host'] . ($parts['port'] ? ':' . $parts['port'] : '');
    }

    private function detectCompression(string $content, array $headers): ?string
    {
        $contentEncoding = $headers['Content-Encoding'][0] ?? '';

        if (str_contains(strtolower($contentEncoding), 'gzip')) {
            return 'gzip';
        }

        // Check magic bytes
        if (str_starts_with($content, "\x1f\x8b")) {
            return 'gzip';
        }

        return null;
    }

    private function decompressContent(string $content, string $compression): string
    {
        switch ($compression) {
            case 'gzip':
                return gzdecode($content) ?: $content;
            default:
                return $content;
        }
    }

    /**
     * Generate sitemap recommendations
     */
    private function generateSitemapRecommendations(array $results): array
    {
        $recommendations = [];

        // Robots.txt recommendations
        if (!($results['robots_txt']['accessible'] ?? false)) {
            $recommendations[] = [
                'type' => 'error',
                'message' => 'robots.txt file not accessible',
                'impact' => 'high',
                'fix' => 'Create and make accessible a robots.txt file at the root of your domain'
            ];
        } else {
            if (empty($results['robots_txt']['sitemaps'] ?? [])) {
                $recommendations[] = [
                    'type' => 'warning',
                    'message' => 'No sitemap reference in robots.txt',
                    'impact' => 'medium',
                    'fix' => 'Add sitemap URLs to your robots.txt file'
                ];
            }

            if (!empty($results['robots_txt']['syntax_errors'] ?? [])) {
                $recommendations[] = [
                    'type' => 'warning',
                    'message' => 'Syntax errors found in robots.txt',
                    'impact' => 'medium',
                    'fix' => 'Fix syntax errors in your robots.txt file'
                ];
            }
        }

        // Sitemap recommendations
        if (empty($results['sitemaps'])) {
            $recommendations[] = [
                'type' => 'error',
                'message' => 'No XML sitemap found',
                'impact' => 'high',
                'fix' => 'Create and submit an XML sitemap to help search engines crawl your site'
            ];
        } else {
            $inaccessibleSitemaps = array_filter($results['sitemaps'], fn($s) => !$s['accessible']);
            if (!empty($inaccessibleSitemaps)) {
                $count = count($inaccessibleSitemaps);
                $recommendations[] = [
                    'type' => 'warning',
                    'message' => "{$count} sitemap(s) not accessible",
                    'impact' => 'medium',
                    'fix' => 'Ensure all referenced sitemaps are accessible'
                ];
            }

            // Check sitemap quality
            foreach ($results['sitemaps'] as $sitemap) {
                if (!empty($sitemap['validation']['errors'] ?? [])) {
                    $recommendations[] = [
                        'type' => 'warning',
                        'message' => 'XML validation errors in sitemap',
                        'impact' => 'medium',
                        'fix' => 'Fix XML validation errors in your sitemap'
                    ];
                    break;
                }
            }
        }

        // URL accessibility recommendations
        $urlAnalysis = $results['url_analysis'];
        if ($urlAnalysis['inaccessible_urls'] > 0) {
            $percentage = round(($urlAnalysis['inaccessible_urls'] / $urlAnalysis['tested_urls']) * 100);
            $recommendations[] = [
                'type' => 'warning',
                'message' => "{$percentage}% of tested URLs are inaccessible",
                'impact' => 'medium',
                'fix' => 'Remove broken URLs from your sitemap or fix the pages'
            ];
        }

        // Large sitemap recommendations
        foreach ($results['sitemaps'] as $sitemap) {
            if (($sitemap['statistics']['total_urls'] ?? 0) > 50000) {
                $recommendations[] = [
                    'type' => 'info',
                    'message' => 'Sitemap contains more than 50,000 URLs',
                    'impact' => 'low',
                    'fix' => 'Consider splitting large sitemaps into multiple files'
                ];
                break;
            }
        }

        return $recommendations;
    }
}