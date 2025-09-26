<?php

namespace App\Services\Analysis;

use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Advanced SEO Metrics and Scoring Weights Configuration
 *
 * Provides comprehensive SEO scoring metrics with weighted algorithms,
 * configurable scoring parameters, and advanced metric calculations.
 */
class SeoMetrics
{
    /**
     * Primary scoring weights (must sum to 100)
     */
    private array $primaryWeights = [
        'title' => 20,           // Title tag optimization
        'meta_description' => 15, // Meta description optimization
        'headings' => 15,        // Heading structure (H1-H6)
        'content' => 20,         // Content quality and length
        'images' => 10,          // Image optimization (alt tags, etc.)
        'links' => 8,            // Internal/external link structure
        'technical' => 7,        // Technical SEO aspects
        'social_media' => 3,     // Social media tags (OG, Twitter)
        'structured_data' => 2   // Schema markup, JSON-LD
    ];

    /**
     * Advanced metric thresholds and scoring parameters
     */
    private array $metricThresholds = [
        'title' => [
            'optimal_length_min' => 30,
            'optimal_length_max' => 60,
            'acceptable_length_min' => 20,
            'acceptable_length_max' => 70,
            'keyword_density_min' => 0.5,
            'keyword_density_max' => 3.0,
            'duplicate_penalty' => 10
        ],
        'meta_description' => [
            'optimal_length_min' => 120,
            'optimal_length_max' => 160,
            'acceptable_length_min' => 100,
            'acceptable_length_max' => 170,
            'cta_bonus' => 10,
            'uniqueness_bonus' => 5
        ],
        'headings' => [
            'h1_optimal_count' => 1,
            'h1_multiple_penalty' => 20,
            'h2_minimum_recommended' => 2,
            'heading_quality_weight' => 0.2,
            'hierarchy_bonus' => 10,
            'optimal_length_min' => 20,
            'optimal_length_max' => 70
        ],
        'content' => [
            'minimum_words' => 300,
            'optimal_words_min' => 600,
            'comprehensive_words_min' => 1000,
            'text_to_html_ratio_min' => 15,
            'text_to_html_ratio_optimal' => 25,
            'reading_time_optimal_min' => 2,
            'reading_time_optimal_max' => 10,
            'paragraph_minimum' => 3,
            'keyword_density_optimal' => 1.5
        ],
        'images' => [
            'alt_text_penalty_rate' => 1.0, // 1% penalty per missing alt
            'title_attribute_bonus' => 5,
            'lazy_loading_bonus' => 3,
            'webp_format_bonus' => 2,
            'size_optimization_threshold' => 100000 // 100KB
        ],
        'links' => [
            'internal_minimum' => 3,
            'external_balance_ratio' => 0.7, // 70% internal, 30% external optimal
            'empty_anchor_penalty_rate' => 2.0,
            'follow_nofollow_balance' => 0.8,
            'deep_linking_bonus' => 5
        ],
        'technical' => [
            'ssl_weight' => 25,
            'mobile_friendly_weight' => 20,
            'page_speed_weight' => 20,
            'schema_markup_weight' => 15,
            'sitemap_bonus' => 5,
            'robots_txt_bonus' => 5,
            'canonical_url_bonus' => 5,
            'hreflang_bonus' => 5
        ],
        'performance' => [
            'load_time_excellent' => 2.0, // seconds
            'load_time_good' => 3.0,
            'load_time_acceptable' => 5.0,
            'core_web_vitals_weight' => 40,
            'lighthouse_score_weight' => 30
        ]
    ];

    /**
     * Industry-specific weight adjustments
     */
    private array $industryWeights = [
        'e-commerce' => [
            'images' => 15,      // Higher importance for product images
            'structured_data' => 8, // Product schema critical
            'technical' => 12,    // Site speed crucial for conversions
            'content' => 15       // Reduce content weight
        ],
        'blog' => [
            'content' => 30,      // Content is king for blogs
            'headings' => 20,     // Structure very important
            'social_media' => 8,  // Social sharing important
            'images' => 5         // Less critical than content
        ],
        'local-business' => [
            'structured_data' => 15, // Local business schema critical
            'technical' => 15,       // Local SEO technical factors
            'social_media' => 8,     // Reviews and social proof
            'content' => 15          // Less content focused
        ],
        'news' => [
            'technical' => 15,    // Speed critical for news
            'structured_data' => 10, // Article schema important
            'headings' => 20,     // News structure important
            'social_media' => 10  // Social sharing critical
        ]
    ];

    /**
     * Competitive analysis factors
     */
    private array $competitiveFactors = [
        'keyword_difficulty' => [
            'low' => 1.0,      // No adjustment
            'medium' => 1.2,   // 20% higher standards
            'high' => 1.5,     // 50% higher standards
            'very_high' => 2.0 // Double standards
        ],
        'search_volume' => [
            'low' => 0.9,      // Slightly easier standards
            'medium' => 1.0,   // No adjustment
            'high' => 1.1,     // 10% higher standards
            'very_high' => 1.2 // 20% higher standards
        ]
    ];

    /**
     * Time-decay factors for content freshness
     */
    private array $freshnessFactors = [
        'news' => 0.95,     // 5% decay per month
        'blog' => 0.98,     // 2% decay per month
        'evergreen' => 0.995, // 0.5% decay per month
        'product' => 0.99   // 1% decay per month
    ];

    public function __construct()
    {
        $this->validateWeights();
    }

    /**
     * Get primary scoring weights
     */
    public function getPrimaryWeights(): array
    {
        return $this->primaryWeights;
    }

    /**
     * Get industry-adjusted weights
     */
    public function getIndustryWeights(string $industry = 'general'): array
    {
        if (!isset($this->industryWeights[$industry])) {
            return $this->primaryWeights;
        }

        $adjustedWeights = $this->primaryWeights;
        $industryAdjustments = $this->industryWeights[$industry];

        foreach ($industryAdjustments as $category => $newWeight) {
            if (isset($adjustedWeights[$category])) {
                $adjustedWeights[$category] = $newWeight;
            }
        }

        // Normalize to ensure they sum to 100
        return $this->normalizeWeights($adjustedWeights);
    }

    /**
     * Get metric thresholds for a category
     */
    public function getThresholds(string $category): array
    {
        return $this->metricThresholds[$category] ?? [];
    }

    /**
     * Calculate competitive difficulty multiplier
     */
    public function getCompetitiveDifficultyMultiplier(string $keywordDifficulty, string $searchVolume): float
    {
        $difficultyFactor = $this->competitiveFactors['keyword_difficulty'][$keywordDifficulty] ?? 1.0;
        $volumeFactor = $this->competitiveFactors['search_volume'][$searchVolume] ?? 1.0;

        return ($difficultyFactor + $volumeFactor) / 2;
    }

    /**
     * Calculate content freshness factor
     */
    public function getFreshnessFactor(string $contentType, int $ageInMonths): float
    {
        $decayRate = $this->freshnessFactors[$contentType] ?? $this->freshnessFactors['evergreen'];
        return pow($decayRate, $ageInMonths);
    }

    /**
     * Calculate advanced title score with enhanced algorithms
     */
    public function calculateAdvancedTitleScore(array $titleData, array $context = []): array
    {
        $score = 0;
        $maxScore = 100;
        $issues = [];
        $recommendations = [];
        $metrics = [];

        $title = $titleData['title'] ?? '';
        $titleLength = $titleData['title_length'] ?? 0;
        $keywords = $context['target_keywords'] ?? [];
        $industry = $context['industry'] ?? 'general';

        $thresholds = $this->getThresholds('title');

        // Base presence score
        if (empty($title)) {
            $issues[] = 'Missing title tag';
            $recommendations[] = 'Add a descriptive title tag to your page';
            return $this->buildScoreResult($score, $maxScore, 'title', $issues, $recommendations, $metrics);
        }

        $score += 30; // Base score for having a title

        // Length optimization with industry context
        $lengthScore = $this->calculateLengthScore($titleLength, $thresholds, $industry);
        $score += $lengthScore['score'];
        $issues = array_merge($issues, $lengthScore['issues']);
        $recommendations = array_merge($recommendations, $lengthScore['recommendations']);

        // Keyword optimization
        if (!empty($keywords)) {
            $keywordScore = $this->calculateKeywordScore($title, $keywords);
            $score += $keywordScore['score'];
            $issues = array_merge($issues, $keywordScore['issues']);
            $recommendations = array_merge($recommendations, $keywordScore['recommendations']);
            $metrics['keyword_density'] = $keywordScore['density'];
        }

        // Readability and structure
        $readabilityScore = $this->calculateTitleReadability($title);
        $score += $readabilityScore['score'];
        if (!empty($readabilityScore['issues'])) {
            $issues = array_merge($issues, $readabilityScore['issues']);
            $recommendations = array_merge($recommendations, $readabilityScore['recommendations']);
        }

        // Brand optimization
        $brandScore = $this->calculateBrandOptimization($title, $context);
        $score += $brandScore;

        // Uniqueness check
        $uniquenessScore = $this->calculateUniquenessScore($title, $context);
        $score += $uniquenessScore;

        $metrics['length'] = $titleLength;
        $metrics['word_count'] = str_word_count($title);
        $metrics['has_brand'] = $this->hasBrandPattern($title);
        $metrics['readability_score'] = $readabilityScore['score'];

        return $this->buildScoreResult($score, $maxScore, 'title', $issues, $recommendations, $metrics);
    }

    /**
     * Calculate advanced content quality metrics
     */
    public function calculateAdvancedContentScore(array $contentData, array $context = []): array
    {
        $score = 0;
        $maxScore = 100;
        $issues = [];
        $recommendations = [];
        $metrics = [];

        $wordCount = $contentData['word_count'] ?? 0;
        $textToHtmlRatio = $contentData['text_to_html_ratio'] ?? 0;
        $readingTime = $contentData['reading_time_minutes'] ?? 0;
        $keywords = $context['target_keywords'] ?? [];
        $contentType = $context['content_type'] ?? 'evergreen';

        $thresholds = $this->getThresholds('content');

        // Word count scoring with advanced thresholds
        $wordScore = $this->calculateWordCountScore($wordCount, $thresholds);
        $score += $wordScore['score'];
        $issues = array_merge($issues, $wordScore['issues']);
        $recommendations = array_merge($recommendations, $wordScore['recommendations']);

        // Content density and quality
        $densityScore = $this->calculateContentDensity($textToHtmlRatio, $thresholds);
        $score += $densityScore['score'];

        // Reading time optimization
        $timeScore = $this->calculateReadingTimeScore($readingTime, $thresholds);
        $score += $timeScore['score'];

        // Keyword density analysis
        if (!empty($keywords)) {
            $keywordAnalysis = $this->analyzeKeywordDensity($contentData['text'] ?? '', $keywords);
            $score += $keywordAnalysis['score'];
            $metrics['keyword_density'] = $keywordAnalysis['density'];

            if (!empty($keywordAnalysis['issues'])) {
                $issues = array_merge($issues, $keywordAnalysis['issues']);
                $recommendations = array_merge($recommendations, $keywordAnalysis['recommendations']);
            }
        }

        // Content freshness factor
        $ageInMonths = $context['content_age_months'] ?? 0;
        if ($ageInMonths > 0) {
            $freshnessFactor = $this->getFreshnessFactor($contentType, $ageInMonths);
            $score = $score * $freshnessFactor;
            $metrics['freshness_factor'] = $freshnessFactor;

            if ($freshnessFactor < 0.9) {
                $recommendations[] = 'Content is aging - consider updating to maintain SEO value';
            }
        }

        $metrics['word_count'] = $wordCount;
        $metrics['text_to_html_ratio'] = $textToHtmlRatio;
        $metrics['reading_time_minutes'] = $readingTime;
        $metrics['content_quality_score'] = min($score, $maxScore);

        return $this->buildScoreResult($score, $maxScore, 'content', $issues, $recommendations, $metrics);
    }

    /**
     * Calculate performance-weighted technical score
     */
    public function calculateTechnicalPerformanceScore(array $technicalData, array $performanceData = []): array
    {
        $score = 0;
        $maxScore = 100;
        $issues = [];
        $recommendations = [];
        $metrics = [];

        $thresholds = $this->getThresholds('technical');
        $perfThresholds = $this->getThresholds('performance');

        // Core technical factors
        $coreScore = $this->calculateCoreTechnicalFactors($technicalData, $thresholds);
        $score += $coreScore['score'];
        $issues = array_merge($issues, $coreScore['issues']);
        $recommendations = array_merge($recommendations, $coreScore['recommendations']);

        // Performance metrics integration
        if (!empty($performanceData)) {
            $perfScore = $this->calculatePerformanceMetrics($performanceData, $perfThresholds);
            $score += $perfScore['score'];
            $issues = array_merge($issues, $perfScore['issues']);
            $recommendations = array_merge($recommendations, $perfScore['recommendations']);
            $metrics['performance_score'] = $perfScore['score'];
        }

        // Core Web Vitals
        if (isset($performanceData['core_web_vitals'])) {
            $webVitalsScore = $this->calculateCoreWebVitals($performanceData['core_web_vitals']);
            $score += $webVitalsScore['score'];
            $metrics['core_web_vitals_score'] = $webVitalsScore['score'];
        }

        return $this->buildScoreResult($score, $maxScore, 'technical', $issues, $recommendations, $metrics);
    }

    /**
     * Private helper methods
     */

    private function validateWeights(): void
    {
        $sum = array_sum($this->primaryWeights);
        if ($sum !== 100) {
            Log::warning("SEO scoring weights sum to {$sum}, expected 100");
        }
    }

    private function normalizeWeights(array $weights): array
    {
        $sum = array_sum($weights);
        if ($sum === 0) {
            throw new InvalidArgumentException('Weight sum cannot be zero');
        }

        return array_map(fn($weight) => round(($weight / $sum) * 100, 1), $weights);
    }

    private function buildScoreResult(float $score, int $maxScore, string $category, array $issues, array $recommendations, array $metrics): array
    {
        return [
            'score' => min(round($score), $maxScore),
            'max_score' => $maxScore,
            'weight' => $this->primaryWeights[$category] ?? 0,
            'issues' => $issues,
            'recommendations' => $recommendations,
            'metrics' => $metrics,
            'category' => $category,
            'calculated_at' => now()->toISOString()
        ];
    }

    private function calculateLengthScore(int $length, array $thresholds, string $industry): array
    {
        $score = 0;
        $issues = [];
        $recommendations = [];

        $optimalMin = $thresholds['optimal_length_min'];
        $optimalMax = $thresholds['optimal_length_max'];
        $acceptableMin = $thresholds['acceptable_length_min'];
        $acceptableMax = $thresholds['acceptable_length_max'];

        // Industry-specific adjustments
        if ($industry === 'e-commerce') {
            $optimalMax += 10; // E-commerce titles can be slightly longer
        } elseif ($industry === 'news') {
            $optimalMin -= 5; // News titles can be more concise
        }

        if ($length >= $optimalMin && $length <= $optimalMax) {
            $score = 25; // Optimal length
        } elseif ($length >= $acceptableMin && $length <= $acceptableMax) {
            $score = 15; // Acceptable length
        } elseif ($length < $optimalMin) {
            $score = max(0, 10 - ($optimalMin - $length));
            $issues[] = 'Title too short for optimal SEO';
            $recommendations[] = "Expand title to {$optimalMin}-{$optimalMax} characters for better visibility";
        } elseif ($length > $optimalMax) {
            $penalty = min(15, ($length - $optimalMax) * 0.5);
            $score = max(0, 15 - $penalty);
            $issues[] = 'Title may be truncated in search results';
            $recommendations[] = "Shorten title to under {$optimalMax} characters to prevent truncation";
        }

        return ['score' => $score, 'issues' => $issues, 'recommendations' => $recommendations];
    }

    private function calculateKeywordScore(string $title, array $keywords): array
    {
        $score = 0;
        $issues = [];
        $recommendations = [];
        $totalDensity = 0;

        $titleWords = str_word_count(strtolower($title), 1);
        $titleLength = count($titleWords);

        foreach ($keywords as $keyword) {
            $keywordWords = str_word_count(strtolower($keyword), 1);
            $matches = 0;

            foreach ($keywordWords as $keywordWord) {
                $matches += count(array_keys($titleWords, $keywordWord));
            }

            $density = $titleLength > 0 ? ($matches / $titleLength) * 100 : 0;
            $totalDensity += $density;

            if ($density > 0) {
                $score += min(10, $density * 2); // Up to 10 points per keyword
            }
        }

        if ($totalDensity === 0) {
            $issues[] = 'No target keywords found in title';
            $recommendations[] = 'Include primary keywords in your title tag';
        } elseif ($totalDensity > 20) {
            $issues[] = 'Keyword stuffing detected in title';
            $recommendations[] = 'Reduce keyword density for more natural title';
            $score *= 0.5; // Penalty for over-optimization
        }

        return [
            'score' => min($score, 20),
            'issues' => $issues,
            'recommendations' => $recommendations,
            'density' => round($totalDensity, 2)
        ];
    }

    private function calculateTitleReadability(string $title): array
    {
        $score = 0;
        $issues = [];
        $recommendations = [];

        // Check for varied vocabulary
        if ($this->hasVariedWords($title)) {
            $score += 5;
        } else {
            $issues[] = 'Title lacks word variety';
            $recommendations[] = 'Use more diverse vocabulary in title';
        }

        // Check for duplicate words
        if (!$this->hasDuplicateWords($title)) {
            $score += 5;
        } else {
            $issues[] = 'Duplicate words found in title';
            $recommendations[] = 'Remove duplicate words for clarity';
        }

        // Check readability (simple heuristic)
        $avgWordLength = $this->calculateAverageWordLength($title);
        if ($avgWordLength >= 4 && $avgWordLength <= 6) {
            $score += 5; // Good readability
        }

        return ['score' => $score, 'issues' => $issues, 'recommendations' => $recommendations];
    }

    private function calculateBrandOptimization(string $title, array $context): int
    {
        $brandName = $context['brand_name'] ?? '';

        if (empty($brandName)) {
            return 0;
        }

        if ($this->hasBrandPattern($title) || str_contains(strtolower($title), strtolower($brandName))) {
            return 5; // Brand included bonus
        }

        return 0;
    }

    private function calculateUniquenessScore(string $title, array $context): int
    {
        // This would typically check against a database of existing titles
        // For now, we'll use a simple heuristic
        $commonPhrases = ['welcome to', 'home page', 'untitled', 'new page', 'page title'];

        foreach ($commonPhrases as $phrase) {
            if (str_contains(strtolower($title), $phrase)) {
                return 0; // Generic title penalty
            }
        }

        return 3; // Uniqueness bonus
    }

    private function hasVariedWords(string $text): bool
    {
        $words = str_word_count(strtolower($text), 1);
        $uniqueWords = array_unique($words);
        return count($uniqueWords) >= max(3, count($words) * 0.7);
    }

    private function hasDuplicateWords(string $text): bool
    {
        $words = str_word_count(strtolower($text), 1);
        return count($words) !== count(array_unique($words));
    }

    private function hasBrandPattern(string $title): bool
    {
        return preg_match('/[\|\-]\s*[A-Z][a-zA-Z]+\s*$/', $title);
    }

    private function calculateAverageWordLength(string $text): float
    {
        $words = str_word_count($text, 1);
        if (empty($words)) return 0;

        $totalLength = array_sum(array_map('strlen', $words));
        return $totalLength / count($words);
    }

    private function calculateWordCountScore(int $wordCount, array $thresholds): array
    {
        $score = 0;
        $issues = [];
        $recommendations = [];

        if ($wordCount >= $thresholds['comprehensive_words_min']) {
            $score = 35; // Comprehensive content
        } elseif ($wordCount >= $thresholds['optimal_words_min']) {
            $score = 25; // Good content length
        } elseif ($wordCount >= $thresholds['minimum_words']) {
            $score = 15; // Acceptable content length
        } else {
            $issues[] = 'Content too short for optimal SEO';
            $recommendations[] = "Expand content to at least {$thresholds['minimum_words']} words";
        }

        return ['score' => $score, 'issues' => $issues, 'recommendations' => $recommendations];
    }

    private function calculateContentDensity(float $textToHtmlRatio, array $thresholds): array
    {
        $score = 0;

        if ($textToHtmlRatio >= $thresholds['text_to_html_ratio_optimal']) {
            $score = 20; // Optimal content density
        } elseif ($textToHtmlRatio >= $thresholds['text_to_html_ratio_min']) {
            $score = 12; // Acceptable content density
        }

        return ['score' => $score];
    }

    private function calculateReadingTimeScore(float $readingTime, array $thresholds): array
    {
        $score = 0;
        $optimalMin = $thresholds['reading_time_optimal_min'];
        $optimalMax = $thresholds['reading_time_optimal_max'];

        if ($readingTime >= $optimalMin && $readingTime <= $optimalMax) {
            $score = 15; // Optimal reading time
        } elseif ($readingTime > $optimalMax) {
            $score = 10; // Long content can still be valuable
        } elseif ($readingTime >= 1) {
            $score = 8; // Short but readable
        }

        return ['score' => $score];
    }

    private function analyzeKeywordDensity(string $content, array $keywords): array
    {
        $score = 0;
        $issues = [];
        $recommendations = [];
        $totalDensity = 0;

        $contentWords = str_word_count(strtolower($content), 1);
        $contentLength = count($contentWords);

        foreach ($keywords as $keyword) {
            $keywordCount = substr_count(strtolower($content), strtolower($keyword));
            $density = $contentLength > 0 ? ($keywordCount / $contentLength) * 100 : 0;
            $totalDensity += $density;

            if ($density >= 0.5 && $density <= 2.5) {
                $score += 5; // Optimal keyword density
            } elseif ($density > 2.5) {
                $issues[] = "Keyword '{$keyword}' may be over-optimized";
                $recommendations[] = "Reduce density of '{$keyword}' to 1-2%";
            }
        }

        return [
            'score' => min($score, 15),
            'issues' => $issues,
            'recommendations' => $recommendations,
            'density' => round($totalDensity, 2)
        ];
    }

    private function calculateCoreTechnicalFactors(array $technicalData, array $thresholds): array
    {
        $score = 0;
        $issues = [];
        $recommendations = [];

        // SSL/HTTPS
        if ($technicalData['ssl_required'] ?? false) {
            $score += 15;
        } else {
            $issues[] = 'Site not using HTTPS';
            $recommendations[] = 'Implement SSL certificate for security and SEO benefits';
        }

        // Mobile-friendly
        if ($technicalData['mobile_friendly'] ?? false) {
            $score += 15;
        } else {
            $issues[] = 'Site not mobile-friendly';
            $recommendations[] = 'Implement responsive design for mobile optimization';
        }

        // Schema markup
        if ($technicalData['schema_markup_present'] ?? false) {
            $score += 10;
        } else {
            $recommendations[] = 'Add structured data markup to enhance search results';
        }

        return ['score' => min($score, 50), 'issues' => $issues, 'recommendations' => $recommendations];
    }

    private function calculatePerformanceMetrics(array $performanceData, array $thresholds): array
    {
        $score = 0;
        $issues = [];
        $recommendations = [];

        $loadTime = $performanceData['load_time'] ?? 0;

        if ($loadTime <= $thresholds['load_time_excellent']) {
            $score += 20;
        } elseif ($loadTime <= $thresholds['load_time_good']) {
            $score += 15;
        } elseif ($loadTime <= $thresholds['load_time_acceptable']) {
            $score += 10;
        } else {
            $issues[] = 'Page load time is too slow';
            $recommendations[] = 'Optimize images, minify CSS/JS, and use caching to improve load times';
        }

        return ['score' => $score, 'issues' => $issues, 'recommendations' => $recommendations];
    }

    private function calculateCoreWebVitals(array $webVitals): array
    {
        $score = 0;

        // Simplified Core Web Vitals scoring
        $lcp = $webVitals['lcp'] ?? 0; // Largest Contentful Paint
        $fid = $webVitals['fid'] ?? 0; // First Input Delay
        $cls = $webVitals['cls'] ?? 0; // Cumulative Layout Shift

        // LCP scoring (good: <2.5s, needs improvement: 2.5-4s, poor: >4s)
        if ($lcp <= 2.5) {
            $score += 15;
        } elseif ($lcp <= 4.0) {
            $score += 8;
        }

        // FID scoring (good: <100ms, needs improvement: 100-300ms, poor: >300ms)
        if ($fid <= 100) {
            $score += 10;
        } elseif ($fid <= 300) {
            $score += 5;
        }

        // CLS scoring (good: <0.1, needs improvement: 0.1-0.25, poor: >0.25)
        if ($cls <= 0.1) {
            $score += 10;
        } elseif ($cls <= 0.25) {
            $score += 5;
        }

        return ['score' => $score];
    }
}