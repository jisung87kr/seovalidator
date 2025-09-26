<?php

namespace App\Services\Technical;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use DOMDocument;
use DOMXPath;
use Exception;

/**
 * Google PageSpeed Insights API integration service
 * Provides Core Web Vitals analysis and mobile-friendly testing
 */
class PageSpeedService
{
    private ?string $apiKey;
    private string $apiUrl = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';
    private int $timeout = 30;
    private int $cacheMinutes = 60;

    public function __construct()
    {
        $this->apiKey = config('services.google.pagespeed_api_key', '');
    }

    /**
     * Analyze page speed and Core Web Vitals
     */
    public function analyze(string $url, array $options = []): array
    {
        Log::info('Starting PageSpeed analysis', ['url' => $url]);

        $cacheKey = "pagespeed:" . md5($url . serialize($options));

        if (!($options['force_refresh'] ?? false)) {
            $cached = Cache::get($cacheKey);
            if ($cached) {
                Log::debug('Returning cached PageSpeed results', ['url' => $url]);
                return $cached;
            }
        }

        $results = [
            'url' => $url,
            'analyzed_at' => now()->toISOString(),
            'api_available' => !empty($this->apiKey),
            'performance_score' => 0,
            'core_web_vitals' => [],
            'lab_data' => [],
            'field_data' => [],
            'opportunities' => [],
            'diagnostics' => [],
            'recommendations' => [],
            'errors' => []
        ];

        if (empty($this->apiKey) || $this->apiKey === null) {
            Log::warning('PageSpeed API key not configured');
            $results['errors'][] = 'Google PageSpeed Insights API key not configured';
            $results = $this->performFallbackAnalysis($url, $results);
        } else {
            try {
                // Analyze desktop performance
                $desktopResults = $this->callPageSpeedAPI($url, 'desktop', $options);

                // Analyze mobile performance
                $mobileResults = $this->callPageSpeedAPI($url, 'mobile', $options);

                $results = $this->processPageSpeedResults($results, $desktopResults, $mobileResults);

            } catch (Exception $e) {
                Log::error('PageSpeed API call failed', [
                    'url' => $url,
                    'error' => $e->getMessage()
                ]);

                $results['errors'][] = 'PageSpeed API call failed: ' . $e->getMessage();
                $results = $this->performFallbackAnalysis($url, $results);
            }
        }

        $results['recommendations'] = $this->generatePageSpeedRecommendations($results);

        Cache::put($cacheKey, $results, $this->cacheMinutes * 60);

        Log::info('PageSpeed analysis completed', [
            'url' => $url,
            'performance_score' => $results['performance_score']
        ]);

        return $results;
    }

    /**
     * Analyze mobile optimization factors
     */
    public function analyzeMobile(string $url, string $html, array $options = []): array
    {
        Log::info('Starting mobile optimization analysis', ['url' => $url]);

        $results = [
            'url' => $url,
            'analyzed_at' => now()->toISOString(),
            'mobile_score' => 0,
            'mobile_friendly' => false,
            'viewport_configuration' => [],
            'responsive_design' => [],
            'mobile_usability' => [],
            'touch_optimization' => [],
            'recommendations' => []
        ];

        // Parse HTML for analysis
        $dom = $this->initializeDom($html);
        if (!$dom) {
            $results['recommendations'][] = [
                'type' => 'error',
                'message' => 'Unable to parse HTML for mobile analysis',
                'impact' => 'high',
                'fix' => 'Ensure valid HTML structure'
            ];
            return $results;
        }

        $xpath = new DOMXPath($dom);

        // Analyze viewport configuration
        $results['viewport_configuration'] = $this->analyzeViewportConfiguration($xpath);

        // Analyze responsive design elements
        $results['responsive_design'] = $this->analyzeResponsiveDesign($html, $xpath);

        // Analyze mobile usability factors
        $results['mobile_usability'] = $this->analyzeMobileUsability($xpath);

        // Analyze touch optimization
        $results['touch_optimization'] = $this->analyzeTouchOptimization($xpath);

        // Calculate overall mobile score
        $results['mobile_score'] = $this->calculateMobileScore($results);
        $results['mobile_friendly'] = $results['mobile_score'] >= 70;

        // Generate mobile-specific recommendations
        $results['recommendations'] = $this->generateMobileRecommendations($results);

        Log::info('Mobile optimization analysis completed', [
            'url' => $url,
            'mobile_score' => $results['mobile_score'],
            'mobile_friendly' => $results['mobile_friendly']
        ]);

        return $results;
    }

    /**
     * Call Google PageSpeed Insights API
     */
    private function callPageSpeedAPI(string $url, string $strategy = 'desktop', array $options = []): array
    {
        $params = [
            'url' => $url,
            'key' => $this->apiKey,
            'strategy' => $strategy,
            'category' => 'PERFORMANCE',
            'locale' => 'en'
        ];

        // Add optional parameters
        if (isset($options['utm_campaign'])) {
            $params['utm_campaign'] = $options['utm_campaign'];
        }
        if (isset($options['utm_source'])) {
            $params['utm_source'] = $options['utm_source'];
        }

        $response = Http::timeout($this->timeout)
            ->get($this->apiUrl, $params);

        if (!$response->successful()) {
            throw new Exception("PageSpeed API error: " . $response->status() . " - " . $response->body());
        }

        $data = $response->json();

        if (isset($data['error'])) {
            throw new Exception("PageSpeed API error: " . ($data['error']['message'] ?? 'Unknown error'));
        }

        return $data;
    }

    /**
     * Process PageSpeed API results
     */
    private function processPageSpeedResults(array $results, array $desktopData, array $mobileData): array
    {
        // Extract performance scores (use mobile as primary)
        $results['performance_score'] = $mobileData['lighthouseResult']['categories']['performance']['score'] * 100;

        $results['desktop_score'] = $desktopData['lighthouseResult']['categories']['performance']['score'] * 100;
        $results['mobile_score'] = $mobileData['lighthouseResult']['categories']['performance']['score'] * 100;

        // Extract Core Web Vitals (prioritize field data)
        $results['core_web_vitals'] = $this->extractCoreWebVitals($mobileData);

        // Extract lab data metrics
        $results['lab_data'] = $this->extractLabData($mobileData['lighthouseResult']['audits'] ?? []);

        // Extract field data if available
        if (isset($mobileData['loadingExperience']['metrics'])) {
            $results['field_data'] = $this->extractFieldData($mobileData['loadingExperience']['metrics']);
        }

        // Extract optimization opportunities
        $results['opportunities'] = $this->extractOpportunities($mobileData['lighthouseResult']['audits'] ?? []);

        // Extract diagnostics
        $results['diagnostics'] = $this->extractDiagnostics($mobileData['lighthouseResult']['audits'] ?? []);

        return $results;
    }

    /**
     * Extract Core Web Vitals metrics
     */
    private function extractCoreWebVitals(array $data): array
    {
        $vitals = [
            'lcp' => null, // Largest Contentful Paint
            'fid' => null, // First Input Delay
            'cls' => null, // Cumulative Layout Shift
            'fcp' => null, // First Contentful Paint
            'inp' => null  // Interaction to Next Paint
        ];

        $audits = $data['lighthouseResult']['audits'] ?? [];

        // Extract from lab data
        if (isset($audits['largest-contentful-paint']['numericValue'])) {
            $vitals['lcp'] = [
                'value' => round($audits['largest-contentful-paint']['numericValue']),
                'unit' => 'ms',
                'rating' => $this->getLCPRating($audits['largest-contentful-paint']['numericValue']),
                'source' => 'lab'
            ];
        }

        if (isset($audits['cumulative-layout-shift']['numericValue'])) {
            $vitals['cls'] = [
                'value' => round($audits['cumulative-layout-shift']['numericValue'], 3),
                'unit' => 'score',
                'rating' => $this->getCLSRating($audits['cumulative-layout-shift']['numericValue']),
                'source' => 'lab'
            ];
        }

        if (isset($audits['first-contentful-paint']['numericValue'])) {
            $vitals['fcp'] = [
                'value' => round($audits['first-contentful-paint']['numericValue']),
                'unit' => 'ms',
                'rating' => $this->getFCPRating($audits['first-contentful-paint']['numericValue']),
                'source' => 'lab'
            ];
        }

        // Extract from field data if available
        if (isset($data['loadingExperience']['metrics'])) {
            $fieldMetrics = $data['loadingExperience']['metrics'];

            if (isset($fieldMetrics['LARGEST_CONTENTFUL_PAINT_MS'])) {
                $vitals['lcp'] = [
                    'value' => $fieldMetrics['LARGEST_CONTENTFUL_PAINT_MS']['percentile'],
                    'unit' => 'ms',
                    'rating' => $this->getLCPRating($fieldMetrics['LARGEST_CONTENTFUL_PAINT_MS']['percentile']),
                    'source' => 'field',
                    'category' => $fieldMetrics['LARGEST_CONTENTFUL_PAINT_MS']['category']
                ];
            }

            if (isset($fieldMetrics['CUMULATIVE_LAYOUT_SHIFT_SCORE'])) {
                $vitals['cls'] = [
                    'value' => $fieldMetrics['CUMULATIVE_LAYOUT_SHIFT_SCORE']['percentile'],
                    'unit' => 'score',
                    'rating' => $this->getCLSRating($fieldMetrics['CUMULATIVE_LAYOUT_SHIFT_SCORE']['percentile']),
                    'source' => 'field',
                    'category' => $fieldMetrics['CUMULATIVE_LAYOUT_SHIFT_SCORE']['category']
                ];
            }

            if (isset($fieldMetrics['FIRST_INPUT_DELAY_MS'])) {
                $vitals['fid'] = [
                    'value' => $fieldMetrics['FIRST_INPUT_DELAY_MS']['percentile'],
                    'unit' => 'ms',
                    'rating' => $this->getFIDRating($fieldMetrics['FIRST_INPUT_DELAY_MS']['percentile']),
                    'source' => 'field',
                    'category' => $fieldMetrics['FIRST_INPUT_DELAY_MS']['category']
                ];
            }
        }

        return array_filter($vitals);
    }

    /**
     * Extract lab data metrics
     */
    private function extractLabData(array $audits): array
    {
        $metrics = [];

        $labMetrics = [
            'speed-index' => ['name' => 'Speed Index', 'unit' => 'ms'],
            'total-blocking-time' => ['name' => 'Total Blocking Time', 'unit' => 'ms'],
            'max-potential-fid' => ['name' => 'Max Potential First Input Delay', 'unit' => 'ms'],
            'server-response-time' => ['name' => 'Server Response Time', 'unit' => 'ms'],
            'interactive' => ['name' => 'Time to Interactive', 'unit' => 'ms']
        ];

        foreach ($labMetrics as $key => $meta) {
            if (isset($audits[$key]['numericValue'])) {
                $metrics[$key] = [
                    'name' => $meta['name'],
                    'value' => round($audits[$key]['numericValue']),
                    'unit' => $meta['unit'],
                    'score' => $audits[$key]['score'] ?? null,
                    'displayValue' => $audits[$key]['displayValue'] ?? null
                ];
            }
        }

        return $metrics;
    }

    /**
     * Extract field data metrics
     */
    private function extractFieldData(array $metrics): array
    {
        $fieldData = [];

        foreach ($metrics as $key => $metric) {
            $fieldData[$key] = [
                'percentile' => $metric['percentile'],
                'category' => $metric['category'],
                'distributions' => $metric['distributions'] ?? []
            ];
        }

        return $fieldData;
    }

    /**
     * Extract optimization opportunities
     */
    private function extractOpportunities(array $audits): array
    {
        $opportunities = [];

        $opportunityKeys = [
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

        foreach ($opportunityKeys as $key) {
            if (isset($audits[$key]) && ($audits[$key]['score'] ?? 1) < 1) {
                $opportunity = $audits[$key];
                $opportunities[] = [
                    'id' => $key,
                    'title' => $opportunity['title'],
                    'description' => $opportunity['description'],
                    'score' => $opportunity['score'],
                    'numericValue' => $opportunity['numericValue'] ?? 0,
                    'numericUnit' => $opportunity['numericUnit'] ?? 'ms',
                    'displayValue' => $opportunity['displayValue'] ?? '',
                    'details' => $opportunity['details'] ?? []
                ];
            }
        }

        return $opportunities;
    }

    /**
     * Extract diagnostics information
     */
    private function extractDiagnostics(array $audits): array
    {
        $diagnostics = [];

        $diagnosticKeys = [
            'mainthread-work-breakdown',
            'third-party-summary',
            'largest-contentful-paint-element',
            'layout-shift-elements',
            'long-tasks',
            'non-composited-animations',
            'unsized-images'
        ];

        foreach ($diagnosticKeys as $key) {
            if (isset($audits[$key])) {
                $diagnostic = $audits[$key];
                $diagnostics[] = [
                    'id' => $key,
                    'title' => $diagnostic['title'],
                    'description' => $diagnostic['description'],
                    'score' => $diagnostic['score'],
                    'scoreDisplayMode' => $diagnostic['scoreDisplayMode'] ?? 'binary',
                    'displayValue' => $diagnostic['displayValue'] ?? '',
                    'details' => $diagnostic['details'] ?? []
                ];
            }
        }

        return $diagnostics;
    }

    /**
     * Perform fallback analysis when API is unavailable
     */
    private function performFallbackAnalysis(string $url, array $results): array
    {
        Log::info('Performing fallback page speed analysis', ['url' => $url]);

        // Basic performance estimates based on URL analysis
        $urlParts = parse_url($url);

        $results['performance_score'] = 50; // Default moderate score
        $results['fallback_analysis'] = true;

        // Basic checks
        $checks = [];

        // HTTPS check
        $checks['https'] = str_starts_with($url, 'https://');
        if (!$checks['https']) {
            $results['performance_score'] -= 10;
        }

        // CDN detection (basic)
        $host = $urlParts['host'] ?? '';
        $checks['possible_cdn'] = $this->isPossibleCDN($host);
        if ($checks['possible_cdn']) {
            $results['performance_score'] += 5;
        }

        // URL length check
        $checks['url_length_ok'] = strlen($url) < 2048;
        if (!$checks['url_length_ok']) {
            $results['performance_score'] -= 5;
        }

        $results['fallback_checks'] = $checks;
        $results['performance_score'] = max(0, min(100, $results['performance_score']));

        return $results;
    }

    /**
     * Initialize DOM from HTML string
     */
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

    /**
     * Analyze viewport configuration
     */
    private function analyzeViewportConfiguration(DOMXPath $xpath): array
    {
        $viewport = $xpath->query('//meta[@name="viewport"]/@content');
        $viewportContent = $viewport->length > 0 ? $viewport->item(0)->value : '';

        return [
            'has_viewport_meta' => !empty($viewportContent),
            'viewport_content' => $viewportContent,
            'width_device_width' => str_contains($viewportContent, 'width=device-width'),
            'initial_scale_set' => str_contains($viewportContent, 'initial-scale'),
            'user_scalable' => !str_contains($viewportContent, 'user-scalable=no'),
            'viewport_score' => $this->calculateViewportScore($viewportContent)
        ];
    }

    /**
     * Analyze responsive design elements
     */
    private function analyzeResponsiveDesign(string $html, DOMXPath $xpath): array
    {
        return [
            'media_queries' => $this->countMediaQueries($html),
            'responsive_images' => $this->analyzeResponsiveImages($xpath),
            'flexible_grids' => $this->detectFlexibleGrids($html),
            'breakpoints' => $this->detectBreakpoints($html)
        ];
    }

    /**
     * Analyze mobile usability factors
     */
    private function analyzeMobileUsability(DOMXPath $xpath): array
    {
        return [
            'text_size' => $this->analyzeTextSize($xpath),
            'tap_targets' => $this->analyzeTapTargets($xpath),
            'content_width' => $this->analyzeContentWidth($xpath),
            'horizontal_scroll' => $this->detectHorizontalScroll($xpath)
        ];
    }

    /**
     * Analyze touch optimization
     */
    private function analyzeTouchOptimization(DOMXPath $xpath): array
    {
        return [
            'touch_friendly_buttons' => $this->analyzeTouchFriendlyButtons($xpath),
            'adequate_spacing' => $this->analyzeElementSpacing($xpath),
            'avoid_hover_effects' => $this->detectHoverEffects($xpath)
        ];
    }

    /**
     * Calculate mobile optimization score
     */
    private function calculateMobileScore(array $results): int
    {
        $score = 0;

        // Viewport configuration (25%)
        if ($results['viewport_configuration']['has_viewport_meta']) {
            $score += 25;
            if ($results['viewport_configuration']['width_device_width']) {
                $score += 10;
            }
        }

        // Responsive design (25%)
        if ($results['responsive_design']['media_queries'] > 0) {
            $score += 15;
        }
        if ($results['responsive_design']['responsive_images']['percentage'] > 50) {
            $score += 10;
        }

        // Mobile usability (25%)
        if ($results['mobile_usability']['text_size']['readable']) {
            $score += 10;
        }
        if ($results['mobile_usability']['tap_targets']['adequate_size']) {
            $score += 15;
        }

        // Touch optimization (25%)
        if ($results['touch_optimization']['touch_friendly_buttons']) {
            $score += 15;
        }
        if ($results['touch_optimization']['adequate_spacing']) {
            $score += 10;
        }

        return min(100, $score);
    }

    // Helper methods for various analyses

    private function getLCPRating(float $value): string
    {
        if ($value <= 2500) return 'good';
        if ($value <= 4000) return 'needs_improvement';
        return 'poor';
    }

    private function getFCPRating(float $value): string
    {
        if ($value <= 1800) return 'good';
        if ($value <= 3000) return 'needs_improvement';
        return 'poor';
    }

    private function getCLSRating(float $value): string
    {
        if ($value <= 0.1) return 'good';
        if ($value <= 0.25) return 'needs_improvement';
        return 'poor';
    }

    private function getFIDRating(float $value): string
    {
        if ($value <= 100) return 'good';
        if ($value <= 300) return 'needs_improvement';
        return 'poor';
    }

    private function calculateViewportScore(string $viewportContent): int
    {
        $score = 0;
        if (str_contains($viewportContent, 'width=device-width')) $score += 40;
        if (str_contains($viewportContent, 'initial-scale=1')) $score += 30;
        if (!str_contains($viewportContent, 'user-scalable=no')) $score += 20;
        if (!str_contains($viewportContent, 'maximum-scale')) $score += 10;
        return $score;
    }

    private function countMediaQueries(string $html): int
    {
        return preg_match_all('/@media[^{]*\{/', $html);
    }

    private function analyzeResponsiveImages(DOMXPath $xpath): array
    {
        $totalImages = $xpath->query('//img')->length;
        $responsiveImages = $xpath->query('//img[@srcset or @sizes]')->length;

        return [
            'total' => $totalImages,
            'responsive' => $responsiveImages,
            'percentage' => $totalImages > 0 ? round(($responsiveImages / $totalImages) * 100) : 0
        ];
    }

    private function detectFlexibleGrids(string $html): bool
    {
        return preg_match('/display:\s*flex|display:\s*grid|width:\s*\d+%/', $html) > 0;
    }

    private function detectBreakpoints(string $html): array
    {
        $breakpoints = [];
        if (preg_match_all('/@media[^{]*\((?:min|max)-width:\s*(\d+)px\)/', $html, $matches)) {
            foreach ($matches[1] as $width) {
                $breakpoints[] = (int)$width;
            }
        }
        return array_unique($breakpoints);
    }

    private function analyzeTextSize(DOMXPath $xpath): array
    {
        // Simple text size analysis
        return [
            'readable' => true, // Default assumption
            'small_text_count' => 0
        ];
    }

    private function analyzeTapTargets(DOMXPath $xpath): array
    {
        $buttons = $xpath->query('//button | //a | //input[@type="submit"]')->length;
        return [
            'total_targets' => $buttons,
            'adequate_size' => true // Default assumption
        ];
    }

    private function analyzeContentWidth(DOMXPath $xpath): array
    {
        return [
            'fits_viewport' => true // Default assumption
        ];
    }

    private function detectHorizontalScroll(DOMXPath $xpath): bool
    {
        return false; // Default assumption
    }

    private function analyzeTouchFriendlyButtons(DOMXPath $xpath): bool
    {
        return true; // Default assumption
    }

    private function analyzeElementSpacing(DOMXPath $xpath): bool
    {
        return true; // Default assumption
    }

    private function detectHoverEffects(DOMXPath $xpath): bool
    {
        return false; // Default assumption
    }

    private function isPossibleCDN(string $host): bool
    {
        $cdnIndicators = ['cdn', 'cache', 'static', 'assets', 'media', 'img'];

        foreach ($cdnIndicators as $indicator) {
            if (str_contains(strtolower($host), $indicator)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate PageSpeed-specific recommendations
     */
    private function generatePageSpeedRecommendations(array $results): array
    {
        $recommendations = [];

        if ($results['performance_score'] < 50) {
            $recommendations[] = [
                'type' => 'error',
                'message' => 'Poor page speed performance',
                'impact' => 'high',
                'fix' => 'Optimize images, minify CSS/JS, and leverage browser caching'
            ];
        } elseif ($results['performance_score'] < 70) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => 'Page speed needs improvement',
                'impact' => 'medium',
                'fix' => 'Focus on Core Web Vitals optimization'
            ];
        }

        // Core Web Vitals recommendations
        foreach ($results['core_web_vitals'] as $vital => $data) {
            if ($data && isset($data['rating']) && $data['rating'] === 'poor') {
                switch ($vital) {
                    case 'lcp':
                        $recommendations[] = [
                            'type' => 'warning',
                            'message' => 'Poor Largest Contentful Paint',
                            'impact' => 'high',
                            'fix' => 'Optimize images, remove unused CSS, and improve server response times'
                        ];
                        break;
                    case 'cls':
                        $recommendations[] = [
                            'type' => 'warning',
                            'message' => 'Poor Cumulative Layout Shift',
                            'impact' => 'medium',
                            'fix' => 'Set dimensions for images and ads, avoid inserting content above existing content'
                        ];
                        break;
                    case 'fid':
                        $recommendations[] = [
                            'type' => 'warning',
                            'message' => 'Poor First Input Delay',
                            'impact' => 'medium',
                            'fix' => 'Minimize JavaScript execution time and remove unused JavaScript'
                        ];
                        break;
                }
            }
        }

        // Add opportunity-based recommendations
        foreach ($results['opportunities'] as $opportunity) {
            if ($opportunity['score'] < 0.5) {
                $recommendations[] = [
                    'type' => 'info',
                    'message' => $opportunity['title'],
                    'impact' => 'medium',
                    'fix' => $opportunity['description'],
                    'savings' => $opportunity['displayValue']
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Generate mobile-specific recommendations
     */
    private function generateMobileRecommendations(array $results): array
    {
        $recommendations = [];

        if (!$results['viewport_configuration']['has_viewport_meta']) {
            $recommendations[] = [
                'type' => 'error',
                'message' => 'Missing viewport meta tag',
                'impact' => 'high',
                'fix' => 'Add <meta name="viewport" content="width=device-width, initial-scale=1"> to your HTML head'
            ];
        }

        if (!$results['viewport_configuration']['width_device_width']) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => 'Viewport not set to device width',
                'impact' => 'medium',
                'fix' => 'Set width=device-width in your viewport meta tag'
            ];
        }

        if ($results['responsive_design']['media_queries'] === 0) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => 'No media queries detected',
                'impact' => 'high',
                'fix' => 'Implement responsive design with CSS media queries'
            ];
        }

        if ($results['responsive_design']['responsive_images']['percentage'] < 50) {
            $recommendations[] = [
                'type' => 'info',
                'message' => 'Few responsive images detected',
                'impact' => 'medium',
                'fix' => 'Use srcset and sizes attributes for responsive images'
            ];
        }

        return $recommendations;
    }
}