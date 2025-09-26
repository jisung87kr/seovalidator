<?php

namespace App\Services\External;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Google PageSpeed Insights API Client
 * Provides comprehensive performance metrics with retry logic and caching
 */
class GooglePageSpeedClient
{
    private Client $client;
    private string $apiKey;
    private int $maxRetries;
    private int $retryDelay;
    private int $cacheTimeout;

    public function __construct()
    {
        $this->apiKey = config('services.google.pagespeed_api_key');
        $this->maxRetries = config('services.google.pagespeed_max_retries', 3);
        $this->retryDelay = config('services.google.pagespeed_retry_delay', 1000);
        $this->cacheTimeout = config('services.google.pagespeed_cache_timeout', 3600);

        if (empty($this->apiKey)) {
            throw new \InvalidArgumentException('Google PageSpeed API key not configured');
        }

        $this->client = $this->createHttpClient();
    }

    /**
     * Analyze URL performance for both mobile and desktop
     */
    public function analyzeUrl(string $url, array $options = []): array
    {
        $cacheKey = $this->getCacheKey($url, $options);

        return Cache::remember($cacheKey, $this->cacheTimeout, function () use ($url, $options) {
            Log::info('Starting Google PageSpeed analysis', ['url' => $url]);

            $results = [
                'mobile' => $this->performAnalysis($url, 'mobile', $options),
                'desktop' => $this->performAnalysis($url, 'desktop', $options),
                'analyzed_at' => now()->toISOString(),
                'cache_expires_at' => now()->addSeconds($this->cacheTimeout)->toISOString()
            ];

            Log::info('Google PageSpeed analysis completed', [
                'url' => $url,
                'mobile_score' => $results['mobile']['performance_score'] ?? null,
                'desktop_score' => $results['desktop']['performance_score'] ?? null
            ]);

            return $results;
        });
    }

    /**
     * Perform analysis for specific strategy (mobile/desktop)
     */
    private function performAnalysis(string $url, string $strategy, array $options = []): array
    {
        $categories = $options['categories'] ?? ['performance', 'accessibility', 'best-practices', 'seo'];

        $queryParams = [
            'url' => $url,
            'key' => $this->apiKey,
            'strategy' => $strategy,
            'category' => $categories,
            'locale' => $options['locale'] ?? 'en'
        ];

        try {
            $response = $this->client->get('https://www.googleapis.com/pagespeedonline/v5/runPagespeed', [
                'query' => $queryParams,
                'timeout' => 60,
                'connect_timeout' => 10
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Invalid JSON response from Google PageSpeed API');
            }

            return $this->parsePageSpeedResponse($data, $strategy);

        } catch (RequestException $e) {
            Log::error('Google PageSpeed API request failed', [
                'url' => $url,
                'strategy' => $strategy,
                'error' => $e->getMessage(),
                'response_code' => $e->getResponse() ? $e->getResponse()->getStatusCode() : null
            ]);

            if ($e->getResponse() && $e->getResponse()->getStatusCode() === 429) {
                throw new \RuntimeException('Google PageSpeed API rate limit exceeded', 429);
            }

            throw new \RuntimeException('Google PageSpeed API request failed: ' . $e->getMessage());
        }
    }

    /**
     * Parse and structure Google PageSpeed response
     */
    private function parsePageSpeedResponse(array $data, string $strategy): array
    {
        $lighthouseResult = $data['lighthouseResult'] ?? [];
        $categories = $lighthouseResult['categories'] ?? [];
        $audits = $lighthouseResult['audits'] ?? [];

        // Extract core scores
        $scores = [
            'performance_score' => isset($categories['performance']) ? round($categories['performance']['score'] * 100) : null,
            'accessibility_score' => isset($categories['accessibility']) ? round($categories['accessibility']['score'] * 100) : null,
            'best_practices_score' => isset($categories['best-practices']) ? round($categories['best-practices']['score'] * 100) : null,
            'seo_score' => isset($categories['seo']) ? round($categories['seo']['score'] * 100) : null
        ];

        // Extract Core Web Vitals
        $coreWebVitals = $this->extractCoreWebVitals($audits);

        // Extract performance metrics
        $performanceMetrics = $this->extractPerformanceMetrics($audits);

        // Extract opportunities and diagnostics
        $opportunities = $this->extractOpportunities($audits);
        $diagnostics = $this->extractDiagnostics($audits);

        // Extract accessibility issues
        $accessibilityIssues = $this->extractAccessibilityIssues($audits);

        // Extract SEO issues
        $seoIssues = $this->extractSeoIssues($audits);

        return [
            'strategy' => $strategy,
            'scores' => $scores,
            'core_web_vitals' => $coreWebVitals,
            'performance_metrics' => $performanceMetrics,
            'opportunities' => $opportunities,
            'diagnostics' => $diagnostics,
            'accessibility_issues' => $accessibilityIssues,
            'seo_issues' => $seoIssues,
            'overall_assessment' => $this->generateOverallAssessment($scores, $coreWebVitals),
            'lighthouse_version' => $lighthouseResult['lighthouseVersion'] ?? null
        ];
    }

    /**
     * Extract Core Web Vitals metrics
     */
    private function extractCoreWebVitals(array $audits): array
    {
        $vitals = [];

        // Largest Contentful Paint (LCP)
        if (isset($audits['largest-contentful-paint'])) {
            $lcp = $audits['largest-contentful-paint'];
            $vitals['lcp'] = [
                'value' => $lcp['numericValue'] ?? null,
                'display_value' => $lcp['displayValue'] ?? null,
                'score' => isset($lcp['score']) ? round($lcp['score'] * 100) : null,
                'assessment' => $this->assessLCP($lcp['numericValue'] ?? null)
            ];
        }

        // First Input Delay (FID) / Total Blocking Time (TBT)
        if (isset($audits['total-blocking-time'])) {
            $tbt = $audits['total-blocking-time'];
            $vitals['tbt'] = [
                'value' => $tbt['numericValue'] ?? null,
                'display_value' => $tbt['displayValue'] ?? null,
                'score' => isset($tbt['score']) ? round($tbt['score'] * 100) : null,
                'assessment' => $this->assessTBT($tbt['numericValue'] ?? null)
            ];
        }

        // Cumulative Layout Shift (CLS)
        if (isset($audits['cumulative-layout-shift'])) {
            $cls = $audits['cumulative-layout-shift'];
            $vitals['cls'] = [
                'value' => $cls['numericValue'] ?? null,
                'display_value' => $cls['displayValue'] ?? null,
                'score' => isset($cls['score']) ? round($cls['score'] * 100) : null,
                'assessment' => $this->assessCLS($cls['numericValue'] ?? null)
            ];
        }

        // First Contentful Paint (FCP)
        if (isset($audits['first-contentful-paint'])) {
            $fcp = $audits['first-contentful-paint'];
            $vitals['fcp'] = [
                'value' => $fcp['numericValue'] ?? null,
                'display_value' => $fcp['displayValue'] ?? null,
                'score' => isset($fcp['score']) ? round($fcp['score'] * 100) : null,
                'assessment' => $this->assessFCP($fcp['numericValue'] ?? null)
            ];
        }

        return $vitals;
    }

    /**
     * Extract performance metrics
     */
    private function extractPerformanceMetrics(array $audits): array
    {
        $metrics = [];

        $metricsToExtract = [
            'speed-index' => 'speed_index',
            'interactive' => 'time_to_interactive',
            'max-potential-fid' => 'max_potential_fid',
            'server-response-time' => 'server_response_time',
            'mainthread-work-breakdown' => 'main_thread_work',
            'bootup-time' => 'bootup_time'
        ];

        foreach ($metricsToExtract as $auditKey => $metricKey) {
            if (isset($audits[$auditKey])) {
                $audit = $audits[$auditKey];
                $metrics[$metricKey] = [
                    'value' => $audit['numericValue'] ?? null,
                    'display_value' => $audit['displayValue'] ?? null,
                    'score' => isset($audit['score']) ? round($audit['score'] * 100) : null
                ];
            }
        }

        return $metrics;
    }

    /**
     * Extract performance opportunities
     */
    private function extractOpportunities(array $audits): array
    {
        $opportunities = [];

        $opportunityAudits = [
            'unused-css-rules',
            'unused-javascript',
            'modern-image-formats',
            'offscreen-images',
            'render-blocking-resources',
            'unminified-css',
            'unminified-javascript',
            'efficient-animated-content',
            'duplicated-javascript',
            'legacy-javascript'
        ];

        foreach ($opportunityAudits as $auditKey) {
            if (isset($audits[$auditKey]) && isset($audits[$auditKey]['details'])) {
                $audit = $audits[$auditKey];
                $opportunities[] = [
                    'id' => $auditKey,
                    'title' => $audit['title'] ?? '',
                    'description' => $audit['description'] ?? '',
                    'score' => isset($audit['score']) ? round($audit['score'] * 100) : null,
                    'numericValue' => $audit['numericValue'] ?? null,
                    'displayValue' => $audit['displayValue'] ?? null,
                    'potential_savings' => $this->extractPotentialSavings($audit),
                    'details' => $audit['details'] ?? null
                ];
            }
        }

        return $opportunities;
    }

    /**
     * Extract diagnostic information
     */
    private function extractDiagnostics(array $audits): array
    {
        $diagnostics = [];

        $diagnosticAudits = [
            'uses-long-cache-ttl',
            'total-byte-weight',
            'dom-size',
            'critical-request-chains',
            'user-timings',
            'network-requests',
            'network-rtt',
            'network-server-latency'
        ];

        foreach ($diagnosticAudits as $auditKey) {
            if (isset($audits[$auditKey])) {
                $audit = $audits[$auditKey];
                $diagnostics[] = [
                    'id' => $auditKey,
                    'title' => $audit['title'] ?? '',
                    'description' => $audit['description'] ?? '',
                    'score' => isset($audit['score']) ? round($audit['score'] * 100) : null,
                    'numericValue' => $audit['numericValue'] ?? null,
                    'displayValue' => $audit['displayValue'] ?? null,
                    'details' => $audit['details'] ?? null
                ];
            }
        }

        return $diagnostics;
    }

    /**
     * Extract accessibility issues
     */
    private function extractAccessibilityIssues(array $audits): array
    {
        $issues = [];

        $accessibilityAudits = [
            'color-contrast',
            'image-alt',
            'label',
            'link-name',
            'aria-*',
            'heading-order',
            'landmark-one-main',
            'html-has-lang'
        ];

        foreach ($audits as $auditKey => $audit) {
            if (strpos($auditKey, 'aria-') === 0 || in_array($auditKey, $accessibilityAudits)) {
                if (isset($audit['score']) && $audit['score'] !== 1) {
                    $issues[] = [
                        'id' => $auditKey,
                        'title' => $audit['title'] ?? '',
                        'description' => $audit['description'] ?? '',
                        'score' => isset($audit['score']) ? round($audit['score'] * 100) : null,
                        'impact' => $this->getAccessibilityImpact($auditKey),
                        'details' => $audit['details'] ?? null
                    ];
                }
            }
        }

        return $issues;
    }

    /**
     * Extract SEO issues
     */
    private function extractSeoIssues(array $audits): array
    {
        $issues = [];

        $seoAudits = [
            'document-title',
            'meta-description',
            'http-status-code',
            'link-text',
            'crawlable-anchors',
            'is-crawlable',
            'robots-txt',
            'image-alt',
            'hreflang',
            'canonical'
        ];

        foreach ($seoAudits as $auditKey) {
            if (isset($audits[$auditKey])) {
                $audit = $audits[$auditKey];
                if (isset($audit['score']) && $audit['score'] !== 1) {
                    $issues[] = [
                        'id' => $auditKey,
                        'title' => $audit['title'] ?? '',
                        'description' => $audit['description'] ?? '',
                        'score' => isset($audit['score']) ? round($audit['score'] * 100) : null,
                        'details' => $audit['details'] ?? null
                    ];
                }
            }
        }

        return $issues;
    }

    /**
     * Generate overall assessment
     */
    private function generateOverallAssessment(array $scores, array $coreWebVitals): array
    {
        $performance = $scores['performance_score'] ?? 0;

        $assessment = 'Poor';
        if ($performance >= 90) $assessment = 'Excellent';
        elseif ($performance >= 70) $assessment = 'Good';
        elseif ($performance >= 50) $assessment = 'Fair';

        $criticalIssues = [];

        // Check Core Web Vitals
        foreach ($coreWebVitals as $vital => $data) {
            if (isset($data['assessment']) && $data['assessment'] === 'Poor') {
                $criticalIssues[] = strtoupper($vital) . ' needs improvement';
            }
        }

        return [
            'overall_grade' => $assessment,
            'performance_score' => $performance,
            'critical_issues' => $criticalIssues,
            'recommendations_priority' => $this->prioritizeRecommendations($scores, $coreWebVitals)
        ];
    }

    /**
     * Create HTTP client with retry logic
     */
    private function createHttpClient(): Client
    {
        $stack = HandlerStack::create();

        // Add retry middleware
        $stack->push(Middleware::retry(
            function ($retries, RequestInterface $request, ResponseInterface $response = null, RequestException $exception = null) {
                if ($retries >= $this->maxRetries) {
                    return false;
                }

                if ($exception) {
                    // Retry on connection errors
                    if ($exception instanceof \GuzzleHttp\Exception\ConnectException) {
                        usleep($this->retryDelay * 1000 * ($retries + 1)); // Exponential backoff
                        return true;
                    }

                    // Retry on server errors (5xx)
                    if ($exception->getResponse() && $exception->getResponse()->getStatusCode() >= 500) {
                        usleep($this->retryDelay * 1000 * ($retries + 1));
                        return true;
                    }
                }

                return false;
            }
        ));

        return new Client([
            'handler' => $stack,
            'timeout' => 60,
            'connect_timeout' => 10,
            'headers' => [
                'User-Agent' => 'SEO-Validator/1.0',
                'Accept' => 'application/json'
            ]
        ]);
    }

    /**
     * Helper methods for metric assessments
     */
    private function assessLCP(?float $value): string
    {
        if ($value === null) return 'Unknown';
        if ($value <= 2500) return 'Good';
        if ($value <= 4000) return 'Needs Improvement';
        return 'Poor';
    }

    private function assessTBT(?float $value): string
    {
        if ($value === null) return 'Unknown';
        if ($value <= 200) return 'Good';
        if ($value <= 600) return 'Needs Improvement';
        return 'Poor';
    }

    private function assessCLS(?float $value): string
    {
        if ($value === null) return 'Unknown';
        if ($value <= 0.1) return 'Good';
        if ($value <= 0.25) return 'Needs Improvement';
        return 'Poor';
    }

    private function assessFCP(?float $value): string
    {
        if ($value === null) return 'Unknown';
        if ($value <= 1800) return 'Good';
        if ($value <= 3000) return 'Needs Improvement';
        return 'Poor';
    }

    private function extractPotentialSavings(array $audit): ?array
    {
        if (isset($audit['details']['overallSavingsMs'])) {
            return [
                'time_ms' => $audit['details']['overallSavingsMs'],
                'bytes' => $audit['details']['overallSavingsBytes'] ?? null
            ];
        }
        return null;
    }

    private function getAccessibilityImpact(string $auditKey): string
    {
        $highImpact = ['color-contrast', 'image-alt', 'label', 'aria-required-attr'];
        $mediumImpact = ['heading-order', 'link-name', 'html-has-lang'];

        if (in_array($auditKey, $highImpact)) return 'high';
        if (in_array($auditKey, $mediumImpact)) return 'medium';
        return 'low';
    }

    private function prioritizeRecommendations(array $scores, array $coreWebVitals): array
    {
        $priorities = [];

        if (($scores['performance_score'] ?? 0) < 50) {
            $priorities[] = 'Focus on performance optimization';
        }

        if (($scores['accessibility_score'] ?? 0) < 80) {
            $priorities[] = 'Address accessibility issues';
        }

        foreach ($coreWebVitals as $vital => $data) {
            if (isset($data['assessment']) && $data['assessment'] === 'Poor') {
                $priorities[] = 'Improve ' . strtoupper($vital) . ' metric';
            }
        }

        return $priorities;
    }

    private function getCacheKey(string $url, array $options): string
    {
        return 'pagespeed:' . md5($url . serialize($options));
    }
}