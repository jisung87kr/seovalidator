<?php

namespace App\Services;

use App\Services\Crawler\CrawlerService;
use App\Services\Crawler\UrlValidator;
use App\Services\Crawler\PageAnalyzer;
use App\Services\Parser\HtmlParserService;
use App\Services\Score\ScoreCalculatorService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SeoAnalyzerService
{
    public function __construct(
        private CrawlerService $crawlerService,
        private UrlValidator $urlValidator,
        private HtmlParserService $htmlParserService,
        private ScoreCalculatorService $scoreCalculatorService,
        private PageAnalyzer $pageAnalyzer
    ) {}

    /**
     * Perform complete SEO analysis for a URL
     */
    public function analyze(string $url, array $options = []): array
    {
        Log::info('Starting SEO analysis', [
            'url' => $url,
            'options' => $options
        ]);

        // Check cache first
        $cacheKey = $this->getCacheKey($url, $options);
        $cached = $this->getCachedResult($cacheKey);

        if ($cached && !($options['force_refresh'] ?? false)) {
            Log::info('Returning cached SEO analysis', ['url' => $url]);
            return $cached;
        }

        try {
            // Step 1: Validate and preprocess URL
            $validatedUrl = $this->urlValidator->validate($url);

            // Step 2: Crawl the URL and extract content
            $crawlData = $this->crawlerService->crawl($validatedUrl, $options);

            // Step 3: Parse HTML and extract SEO elements
            $parsedData = $this->htmlParserService->parse($crawlData['html'], $validatedUrl);

            // Step 4: Calculate SEO scores
            $scores = $this->scoreCalculatorService->calculate($parsedData);

            // Step 5: Perform comprehensive page analysis (quality assessment)
            $pageAnalysis = null;
            if ($options['include_quality_analysis'] ?? true) {
                $pageAnalysis = $this->pageAnalyzer->analyze($crawlData['html'], $validatedUrl, $parsedData, $options);
            }

            // Step 6: Combine all analysis data
            $analysis = $this->buildAnalysisResult($validatedUrl, $crawlData, $parsedData, $scores, $pageAnalysis, $options);

            // Cache the result
            $this->cacheResult($cacheKey, $analysis);

            Log::info('SEO analysis completed successfully', [
                'url' => $url,
                'overall_score' => $scores['overall_score']
            ]);

            return $analysis;

        } catch (\Exception $e) {
            Log::error('SEO analysis failed', [
                'url' => $url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Analyze multiple URLs in batch
     */
    public function analyzeBatch(array $urls, array $options = []): array
    {
        $results = [];
        $errors = [];

        foreach ($urls as $url) {
            try {
                $results[$url] = $this->analyze($url, $options);
            } catch (\Exception $e) {
                $errors[$url] = $e->getMessage();
                Log::error('Batch analysis failed for URL', [
                    'url' => $url,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'results' => $results,
            'errors' => $errors,
            'summary' => [
                'total_urls' => count($urls),
                'successful' => count($results),
                'failed' => count($errors)
            ]
        ];
    }

    /**
     * Get analysis history for a URL
     */
    public function getAnalysisHistory(string $url, int $limit = 10): array
    {
        // This would typically query a database table
        // For now, return empty array as database layer is not yet implemented
        return [];
    }

    /**
     * Build the complete analysis result structure
     */
    private function buildAnalysisResult(
        string $url,
        array $crawlData,
        array $parsedData,
        array $scores,
        ?array $pageAnalysis,
        array $options
    ): array {
        return [
            'url' => $url,
            'analyzed_at' => Carbon::now()->toISOString(),
            'analysis_duration_ms' => $crawlData['duration_ms'] ?? 0,
            'status' => [
                'code' => $crawlData['status_code'],
                'success' => $crawlData['status_code'] >= 200 && $crawlData['status_code'] < 300
            ],
            'crawl_data' => [
                'html_size' => strlen($crawlData['html']),
                'load_time_ms' => $crawlData['load_time_ms'] ?? 0,
                'javascript_enabled' => $crawlData['javascript_enabled'] ?? false,
                'user_agent' => $crawlData['user_agent'] ?? '',
                'final_url' => $crawlData['final_url'] ?? $url
            ],
            'seo_elements' => $parsedData,
            'scores' => $scores,
            'page_analysis' => $pageAnalysis,
            'recommendations' => $this->generateRecommendations($parsedData, $scores, $pageAnalysis),
            'metadata' => [
                'analysis_version' => '1.0.0',
                'options' => $options
            ]
        ];
    }

    /**
     * Generate SEO recommendations based on analysis
     */
    private function generateRecommendations(array $parsedData, array $scores, ?array $pageAnalysis = null): array
    {
        $recommendations = [];

        // Title recommendations
        if (empty($parsedData['meta']['title'])) {
            $recommendations[] = [
                'type' => 'error',
                'category' => 'meta',
                'message' => __('analysis.recommendation_missing_title_tag'),
                'impact' => 'high',
                'fix' => __('analysis.recommendation_missing_title_tag_fix')
            ];
        } elseif (strlen($parsedData['meta']['title']) < 30) {
            $recommendations[] = [
                'type' => 'warning',
                'category' => 'meta',
                'message' => __('analysis.recommendation_title_too_short'),
                'impact' => 'medium',
                'fix' => __('analysis.recommendation_title_too_short_fix')
            ];
        } elseif (strlen($parsedData['meta']['title']) > 60) {
            $recommendations[] = [
                'type' => 'warning',
                'category' => 'meta',
                'message' => __('analysis.recommendation_title_too_long'),
                'impact' => 'medium',
                'fix' => __('analysis.recommendation_title_too_long_fix')
            ];
        }

        // Description recommendations
        if (empty($parsedData['meta']['description'])) {
            $recommendations[] = [
                'type' => 'error',
                'category' => 'meta',
                'message' => __('analysis.recommendation_missing_meta_description'),
                'impact' => 'high',
                'fix' => __('analysis.recommendation_missing_meta_description_fix')
            ];
        } elseif (strlen($parsedData['meta']['description']) < 120) {
            $recommendations[] = [
                'type' => 'warning',
                'category' => 'meta',
                'message' => __('analysis.recommendation_meta_description_too_short'),
                'impact' => 'medium',
                'fix' => __('analysis.recommendation_meta_description_too_short_fix')
            ];
        } elseif (strlen($parsedData['meta']['description']) > 160) {
            $recommendations[] = [
                'type' => 'warning',
                'category' => 'meta',
                'message' => __('analysis.recommendation_meta_description_too_long'),
                'impact' => 'medium',
                'fix' => __('analysis.recommendation_meta_description_too_long_fix')
            ];
        }

        // H1 recommendations
        $h1Count = count($parsedData['headings']['h1'] ?? []);
        if ($h1Count === 0) {
            $recommendations[] = [
                'type' => 'error',
                'category' => 'headings',
                'message' => __('analysis.recommendation_missing_h1'),
                'impact' => 'high',
                'fix' => __('analysis.recommendation_missing_h1_fix')
            ];
        } elseif ($h1Count > 1) {
            $recommendations[] = [
                'type' => 'warning',
                'category' => 'headings',
                'message' => __('analysis.recommendation_multiple_h1'),
                'impact' => 'medium',
                'fix' => __('analysis.recommendation_multiple_h1_fix')
            ];
        }

        // Images recommendations
        $imagesWithoutAlt = $parsedData['images']['without_alt_count'] ?? 0;
        if ($imagesWithoutAlt > 0) {
            $recommendations[] = [
                'type' => 'warning',
                'category' => 'images',
                'message' => __('analysis.recommendation_images_missing_alt', ['count' => $imagesWithoutAlt]),
                'impact' => 'medium',
                'fix' => __('analysis.recommendation_images_missing_alt_fix')
            ];
        }

        // Content length recommendations
        $wordCount = $parsedData['content']['word_count'] ?? 0;
        if ($wordCount < 300) {
            $recommendations[] = [
                'type' => 'warning',
                'category' => 'content',
                'message' => __('analysis.recommendation_content_too_short'),
                'impact' => 'medium',
                'fix' => __('analysis.recommendation_content_too_short_fix')
            ];
        }

        // Add page analysis recommendations if available
        if ($pageAnalysis && isset($pageAnalysis['recommendations'])) {
            $recommendations = array_merge($recommendations, $pageAnalysis['recommendations']);
        }

        return $recommendations;
    }

    /**
     * Generate cache key for analysis result
     */
    private function getCacheKey(string $url, array $options): string
    {
        $optionsHash = md5(serialize($options));
        return "seo_analysis:" . md5($url) . ":" . $optionsHash;
    }

    /**
     * Get cached analysis result
     */
    private function getCachedResult(string $cacheKey): ?array
    {
        try {
            return Cache::get($cacheKey);
        } catch (\Exception $e) {
            Log::warning('Failed to retrieve cached analysis result', [
                'cache_key' => $cacheKey,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Cache analysis result
     */
    private function cacheResult(string $cacheKey, array $analysis): void
    {
        try {
            // Cache for 1 hour by default
            $ttl = config('seo.cache_ttl', 3600);
            Cache::put($cacheKey, $analysis, $ttl);

            Log::debug('Analysis result cached', [
                'cache_key' => $cacheKey,
                'ttl' => $ttl
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to cache analysis result', [
                'cache_key' => $cacheKey,
                'error' => $e->getMessage()
            ]);
        }
    }
}