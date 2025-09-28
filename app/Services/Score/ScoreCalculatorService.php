<?php

namespace App\Services\Score;

use App\Services\Analysis\SeoMetrics;
use App\Services\Cache\AnalysisCache;
use App\Services\Analysis\RecommendationEngine;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ScoreCalculatorService
{
    private SeoMetrics $seoMetrics;
    private AnalysisCache $cache;
    private RecommendationEngine $recommendationEngine;

    /**
     * Legacy weights - now managed by SeoMetrics service
     * @deprecated Use SeoMetrics service for weight management
     */
    private array $weights = [
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

    public function __construct(
        SeoMetrics $seoMetrics,
        AnalysisCache $cache,
        RecommendationEngine $recommendationEngine
    ) {
        $this->seoMetrics = $seoMetrics;
        $this->cache = $cache;
        $this->recommendationEngine = $recommendationEngine;
    }

    /**
     * Calculate comprehensive SEO scores with caching and advanced algorithms
     */
    public function calculate(array $parsedData, array $context = []): array
    {
        Log::debug('Starting advanced SEO score calculation');

        $startTime = microtime(true);
        $url = $context['url'] ?? 'unknown';
        $cacheKey = $this->generateScoreCacheKey($parsedData, $context);

        try {
            // Try to get cached results first
            if ($this->shouldUseCache($context)) {
                $cachedResult = $this->cache->getAnalysis($url, ['type' => 'score_calculation']);
                if ($cachedResult !== null) {
                    Log::debug('Returning cached score calculation');
                    return $cachedResult;
                }
            }

            // Use advanced metrics for scoring
            $industry = $context['industry'] ?? 'general';
            $weights = $this->seoMetrics->getIndustryWeights($industry);

            // Calculate scores using advanced algorithms
            $scores = $this->calculateAdvancedScores($parsedData, $context, $weights);

            // Calculate weighted overall score with competitive factors
            $overallScore = $this->calculateAdvancedOverallScore($scores, $context);

            // Generate detailed breakdown
            $breakdown = $this->generateAdvancedScoreBreakdown($scores, $weights);

            // Determine grade and performance metrics
            $grade = $this->calculateGrade($overallScore);
            $performanceMetrics = $this->calculatePerformanceMetrics($startTime);

            $result = [
                'overall_score' => $overallScore,
                'grade' => $grade,
                'category_scores' => $scores,
                'breakdown' => $breakdown,
                'max_possible_score' => 100,
                'scoring_version' => '2.0.0', // Updated version with advanced algorithms
                'scoring_method' => 'advanced_weighted_algorithm',
                'industry' => $industry,
                'weights_used' => $weights,
                'performance_metrics' => $performanceMetrics,
                'competitive_factors' => $this->getCompetitiveFactors($context),
                'calculated_at' => now()->toISOString(),
                'cache_key' => $cacheKey
            ];

            // Cache the results for future use
            if ($this->shouldUseCache($context)) {
                $this->cache->storeAnalysis($url, $result, 'score_only', $context);
            }

            Log::debug('Advanced SEO score calculation completed', [
                'overall_score' => $overallScore,
                'grade' => $grade,
                'calculation_time_ms' => $performanceMetrics['calculation_time_ms'],
                'industry' => $industry
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('SEO score calculation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new ScoreCalculationException('Failed to calculate SEO scores: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Score title tag optimization
     */
    private function scoreTitleOptimization(array $metaData): array
    {
        $score = 0;
        $maxScore = 100;
        $issues = [];
        $recommendations = [];

        $title = $metaData['title'] ?? '';
        $titleLength = $metaData['title_length'] ?? 0;

        if (empty($title)) {
            $issues[] = __('analysis.score_missing_title_tag');
            $recommendations[] = __('analysis.score_add_descriptive_title_tag');
        } else {
            $score += 40; // Base score for having a title

            // Length optimization
            if ($titleLength >= 30 && $titleLength <= 60) {
                $score += 30; // Optimal length
            } elseif ($titleLength >= 20 && $titleLength <= 70) {
                $score += 20; // Acceptable length
            } elseif ($titleLength < 30) {
                $issues[] = __('analysis.score_title_too_short');
                $recommendations[] = __('analysis.score_expand_title_30_60_chars');
            } elseif ($titleLength > 60) {
                $issues[] = __('analysis.score_title_too_long');
                $recommendations[] = __('analysis.score_shorten_title_under_60_chars');
            }

            // Check for keywords (simplified - would need actual target keywords)
            if ($this->hasVariedWords($title)) {
                $score += 15;
            } else {
                $issues[] = __('analysis.score_title_lacks_keyword_variety');
                $recommendations[] = __('analysis.score_include_relevant_keywords');
            }

            // Brand mention (if title ends with brand name pattern)
            if ($this->hasBrandPattern($title)) {
                $score += 10;
            }

            // Duplicate words penalty
            if ($this->hasDuplicateWords($title)) {
                $score -= 5;
                $issues[] = __('analysis.score_duplicate_words_in_title');
                $recommendations[] = __('analysis.score_remove_duplicate_words_title');
            }
        }

        return [
            'score' => min($score, $maxScore),
            'max_score' => $maxScore,
            'weight' => $this->weights['title'],
            'issues' => $issues,
            'recommendations' => $recommendations,
            'metrics' => [
                'length' => $titleLength,
                'has_title' => !empty($title),
                'optimal_length' => $titleLength >= 30 && $titleLength <= 60
            ]
        ];
    }

    /**
     * Score meta description optimization
     */
    private function scoreMetaDescription(array $metaData): array
    {
        $score = 0;
        $maxScore = 100;
        $issues = [];
        $recommendations = [];

        $description = $metaData['description'] ?? '';
        $descriptionLength = $metaData['description_length'] ?? 0;

        if (empty($description)) {
            $issues[] = __('analysis.score_missing_meta_description');
            $recommendations[] = __('analysis.score_add_compelling_meta_description');
        } else {
            $score += 50; // Base score for having a description

            // Length optimization
            if ($descriptionLength >= 120 && $descriptionLength <= 160) {
                $score += 35; // Optimal length
            } elseif ($descriptionLength >= 100 && $descriptionLength <= 170) {
                $score += 25; // Acceptable length
            } elseif ($descriptionLength < 120) {
                $issues[] = __('analysis.score_description_too_short');
                $recommendations[] = __('analysis.score_expand_meta_description_120_160');
            } elseif ($descriptionLength > 160) {
                $issues[] = __('analysis.score_description_too_long');
                $recommendations[] = __('analysis.score_shorten_meta_description_160');
            }

            // Call-to-action check
            if ($this->hasCallToAction($description)) {
                $score += 10;
            } else {
                $recommendations[] = __('analysis.score_consider_adding_cta');
            }

            // Uniqueness check (simplified)
            if ($this->isDescriptive($description)) {
                $score += 5;
            }
        }

        return [
            'score' => min($score, $maxScore),
            'max_score' => $maxScore,
            'weight' => $this->weights['meta_description'],
            'issues' => $issues,
            'recommendations' => $recommendations,
            'metrics' => [
                'length' => $descriptionLength,
                'has_description' => !empty($description),
                'optimal_length' => $descriptionLength >= 120 && $descriptionLength <= 160
            ]
        ];
    }

    /**
     * Score heading structure
     */
    private function scoreHeadingsStructure(array $headingsData): array
    {
        $score = 0;
        $maxScore = 100;
        $issues = [];
        $recommendations = [];

        $h1Count = count($headingsData['h1'] ?? []);
        $h2Count = count($headingsData['h2'] ?? []);
        $h3Count = count($headingsData['h3'] ?? []);

        // H1 analysis
        if ($h1Count === 0) {
            $issues[] = __('analysis.score_missing_h1_tag');
            $recommendations[] = __('analysis.score_add_h1_main_topic');
        } elseif ($h1Count === 1) {
            $score += 40; // Perfect H1 structure
        } else {
            $score += 20; // Multiple H1s are acceptable but not optimal
            $issues[] = __('analysis.score_multiple_h1_tags_found');
            $recommendations[] = __('analysis.score_use_only_one_h1_tag');
        }

        // H2-H6 structure
        if ($h2Count > 0) {
            $score += 25; // Good use of subheadings
            if ($h3Count > 0 && $h3Count <= $h2Count * 3) {
                $score += 15; // Good hierarchical structure
            }
        } else {
            $issues[] = __('analysis.score_no_h2_headings_found');
            $recommendations[] = __('analysis.score_use_h2_headings_structure');
        }

        // Heading content quality
        $headingQualityScore = $this->assessHeadingQuality($headingsData);
        $score += $headingQualityScore;

        // Hierarchy check
        if ($this->hasProperHierarchy($headingsData)) {
            $score += 10;
        } else {
            $issues[] = __('analysis.score_improper_heading_hierarchy');
            $recommendations[] = __('analysis.score_maintain_proper_heading_hierarchy');
        }

        return [
            'score' => min($score, $maxScore),
            'max_score' => $maxScore,
            'weight' => $this->weights['headings'],
            'issues' => $issues,
            'recommendations' => $recommendations,
            'metrics' => [
                'h1_count' => $h1Count,
                'h2_count' => $h2Count,
                'h3_count' => $h3Count,
                'total_headings' => array_sum(array_map('count', $headingsData)),
                'has_h1' => $h1Count > 0,
                'has_structure' => $h2Count > 0
            ]
        ];
    }

    /**
     * Score content quality
     */
    private function scoreContentQuality(array $contentData): array
    {
        $score = 0;
        $maxScore = 100;
        $issues = [];
        $recommendations = [];

        $wordCount = $contentData['word_count'] ?? 0;
        $textToHtmlRatio = $contentData['text_to_html_ratio'] ?? 0;
        $readingTime = $contentData['reading_time_minutes'] ?? 0;

        // Word count scoring
        if ($wordCount >= 300) {
            if ($wordCount >= 1000) {
                $score += 40; // Comprehensive content
            } elseif ($wordCount >= 600) {
                $score += 35; // Good content length
            } else {
                $score += 25; // Acceptable content length
            }
        } else {
            $issues[] = __('analysis.score_content_too_short');
            $recommendations[] = __('analysis.score_expand_content_300_words');
        }

        // Text-to-HTML ratio
        if ($textToHtmlRatio >= 25) {
            $score += 25; // Good content density
        } elseif ($textToHtmlRatio >= 15) {
            $score += 15; // Acceptable content density
        } else {
            $issues[] = __('analysis.score_low_text_html_ratio');
            $recommendations[] = __('analysis.score_increase_text_content_ratio');
        }

        // Reading time assessment
        if ($readingTime >= 2 && $readingTime <= 10) {
            $score += 20; // Good reading length
        } elseif ($readingTime > 10) {
            $score += 15; // Long content (can be good for detailed topics)
        }

        // Content structure (paragraphs)
        $paragraphs = $contentData['paragraphs'] ?? 0;
        if ($paragraphs >= 3) {
            $score += 10; // Well-structured content
        }

        return [
            'score' => min($score, $maxScore),
            'max_score' => $maxScore,
            'weight' => $this->weights['content'],
            'issues' => $issues,
            'recommendations' => $recommendations,
            'metrics' => [
                'word_count' => $wordCount,
                'text_to_html_ratio' => $textToHtmlRatio,
                'reading_time_minutes' => $readingTime,
                'paragraphs' => $paragraphs,
                'sufficient_content' => $wordCount >= 300
            ]
        ];
    }

    /**
     * Score image optimization
     */
    private function scoreImageOptimization(array $imagesData): array
    {
        $score = 100; // Start with perfect score
        $maxScore = 100;
        $issues = [];
        $recommendations = [];

        $totalImages = $imagesData['total_count'] ?? 0;
        $withoutAlt = $imagesData['without_alt_count'] ?? 0;

        if ($totalImages === 0) {
            return [
                'score' => $score,
                'max_score' => $maxScore,
                'weight' => $this->weights['images'],
                'issues' => [__('analysis.score_no_images_found')],
                'recommendations' => [__('analysis.score_consider_adding_relevant_images')],
                'metrics' => ['total_images' => 0]
            ];
        }

        // Alt text penalty
        if ($withoutAlt > 0) {
            $penaltyPercent = ($withoutAlt / $totalImages) * 100;
            $score -= $penaltyPercent;
            $issues[] = __('analysis.score_images_missing_alt_text', ['count' => $withoutAlt]);
            $recommendations[] = __('analysis.score_add_alt_text_all_images', ['count' => $withoutAlt]);
        }

        // Image title attributes (optional but beneficial)
        $withoutTitle = $imagesData['without_title_count'] ?? 0;
        if ($withoutTitle > 0 && $withoutTitle === $totalImages) {
            $score -= 10; // Minor penalty for no title attributes
            $recommendations[] = __('analysis.score_consider_adding_title_attributes');
        }

        return [
            'score' => max($score, 0),
            'max_score' => $maxScore,
            'weight' => $this->weights['images'],
            'issues' => $issues,
            'recommendations' => $recommendations,
            'metrics' => [
                'total_images' => $totalImages,
                'without_alt' => $withoutAlt,
                'alt_text_coverage' => $totalImages > 0 ? round((($totalImages - $withoutAlt) / $totalImages) * 100, 1) : 0
            ]
        ];
    }

    /**
     * Score link structure
     */
    private function scoreLinkStructure(array $linksData): array
    {
        $score = 0;
        $maxScore = 100;
        $issues = [];
        $recommendations = [];

        $totalLinks = $linksData['total_count'] ?? 0;
        $internalLinks = $linksData['internal_count'] ?? 0;
        $externalLinks = $linksData['external_count'] ?? 0;
        $emptyAnchorCount = $linksData['empty_anchor_count'] ?? 0;

        if ($totalLinks === 0) {
            $issues[] = __('analysis.score_no_links_found');
            $recommendations[] = __('analysis.score_add_internal_external_links');
            return [
                'score' => 0,
                'max_score' => $maxScore,
                'weight' => $this->weights['links'],
                'issues' => $issues,
                'recommendations' => $recommendations,
                'metrics' => ['total_links' => 0]
            ];
        }

        // Internal linking
        if ($internalLinks > 0) {
            $score += 40; // Good internal linking
            if ($internalLinks >= 3) {
                $score += 10; // Excellent internal linking
            }
        } else {
            $issues[] = __('analysis.score_no_internal_links_found');
            $recommendations[] = __('analysis.score_add_internal_links');
        }

        // External linking
        if ($externalLinks > 0) {
            $score += 20; // Good for credibility
            if ($externalLinks <= $internalLinks) {
                $score += 10; // Good balance
            }
        } else {
            $recommendations[] = __('analysis.score_consider_external_links');
        }

        // Anchor text quality
        if ($emptyAnchorCount === 0) {
            $score += 20; // All links have anchor text
        } else {
            $penaltyPercent = min(($emptyAnchorCount / $totalLinks) * 50, 20);
            $score -= $penaltyPercent;
            $issues[] = __('analysis.score_links_empty_anchor_text', ['count' => $emptyAnchorCount]);
            $recommendations[] = __('analysis.score_add_descriptive_anchor_text');
        }

        // Link ratio analysis
        $linkRatio = $totalLinks > 0 ? $internalLinks / $totalLinks : 0;
        if ($linkRatio >= 0.6 && $linkRatio <= 0.8) {
            $score += 10; // Good internal/external ratio
        }

        return [
            'score' => min($score, $maxScore),
            'max_score' => $maxScore,
            'weight' => $this->weights['links'],
            'issues' => $issues,
            'recommendations' => $recommendations,
            'metrics' => [
                'total_links' => $totalLinks,
                'internal_links' => $internalLinks,
                'external_links' => $externalLinks,
                'empty_anchor_count' => $emptyAnchorCount,
                'internal_ratio' => round($linkRatio * 100, 1)
            ]
        ];
    }

    /**
     * Score technical SEO aspects
     */
    private function scoreTechnicalSeo(array $technicalData): array
    {
        $score = 0;
        $maxScore = 100;
        $issues = [];
        $recommendations = [];

        // HTML5 DOCTYPE
        $doctype = $technicalData['doctype'] ?? '';
        if (str_contains(strtolower($doctype), 'html')) {
            $score += 15;
        } else {
            $issues[] = __('analysis.score_missing_invalid_doctype');
            $recommendations[] = __('analysis.score_add_html5_doctype');
        }

        // Language attribute
        $langAttribute = $technicalData['lang_attribute'] ?? '';
        if (!empty($langAttribute)) {
            $score += 15;
        } else {
            $issues[] = __('analysis.score_missing_lang_attribute');
            $recommendations[] = __('analysis.score_add_lang_attribute_html');
        }

        // SSL/HTTPS
        $sslRequired = $technicalData['ssl_required'] ?? false;
        if ($sslRequired) {
            $score += 20;
        } else {
            $issues[] = __('analysis.score_not_using_https');
            $recommendations[] = __('analysis.score_implement_https');
        }

        // Meta viewport (mobile-friendly)
        // This would be checked in meta tags parsing
        $score += 15; // Assume present for now

        // Schema markup presence
        $schemaPresent = $technicalData['schema_markup_present'] ?? false;
        if ($schemaPresent) {
            $score += 20;
        } else {
            $recommendations[] = __('analysis.score_add_json_ld_structured_data');
        }

        // Open Graph presence
        $ogPresent = $technicalData['open_graph_present'] ?? false;
        if ($ogPresent) {
            $score += 10;
        } else {
            $recommendations[] = __('analysis.score_complete_open_graph_setup');
        }

        // Performance hints
        $inlineStyles = $technicalData['inline_styles_count'] ?? 0;
        $inlineScripts = $technicalData['inline_scripts_count'] ?? 0;

        if ($inlineStyles === 0 && $inlineScripts === 0) {
            $score += 5; // Clean separation of concerns
        } elseif ($inlineStyles + $inlineScripts > 10) {
            $issues[] = __('analysis.score_too_many_inline_styles_scripts');
            $recommendations[] = __('analysis.score_move_inline_to_external');
        }

        return [
            'score' => min($score, $maxScore),
            'max_score' => $maxScore,
            'weight' => $this->weights['technical'],
            'issues' => $issues,
            'recommendations' => $recommendations,
            'metrics' => [
                'has_doctype' => !empty($doctype),
                'has_lang_attribute' => !empty($langAttribute),
                'uses_https' => $sslRequired,
                'has_schema' => $schemaPresent,
                'has_open_graph' => $ogPresent,
                'inline_styles' => $inlineStyles,
                'inline_scripts' => $inlineScripts
            ]
        ];
    }

    /**
     * Score social media tags
     */
    private function scoreSocialMediaTags(array $socialMediaData): array
    {
        $score = 0;
        $maxScore = 100;
        $recommendations = [];

        $openGraph = $socialMediaData['open_graph'] ?? [];
        $twitterCards = $socialMediaData['twitter_cards'] ?? [];

        // Open Graph scoring
        $ogScore = 0;
        $requiredOgTags = ['title', 'description', 'image', 'url'];
        foreach ($requiredOgTags as $tag) {
            if (!empty($openGraph[$tag])) {
                $ogScore += 15;
            }
        }
        if ($ogScore < 60) {
            $recommendations[] = __('analysis.score_complete_open_graph_setup');
        }

        // Twitter Cards scoring
        $twitterScore = 0;
        if (!empty($twitterCards['card'])) {
            $twitterScore += 20;
            if (!empty($twitterCards['title']) && !empty($twitterCards['description'])) {
                $twitterScore += 20;
            }
        } else {
            $recommendations[] = __('analysis.score_add_twitter_card_tags');
        }

        $score = ($ogScore * 0.7) + ($twitterScore * 0.3);

        return [
            'score' => min($score, $maxScore),
            'max_score' => $maxScore,
            'weight' => $this->weights['social_media'],
            'issues' => [],
            'recommendations' => $recommendations,
            'metrics' => [
                'open_graph_tags' => count($openGraph),
                'twitter_card_tags' => count($twitterCards),
                'has_og_image' => !empty($openGraph['image']),
                'has_twitter_card' => !empty($twitterCards['card'])
            ]
        ];
    }

    /**
     * Score structured data
     */
    private function scoreStructuredData(array $structuredData): array
    {
        $score = 0;
        $maxScore = 100;
        $recommendations = [];

        $jsonLd = $structuredData['json_ld'] ?? [];
        $microdata = $structuredData['microdata'] ?? [];
        $rdfa = $structuredData['rdfa'] ?? [];

        // JSON-LD scoring (preferred)
        if (!empty($jsonLd)) {
            $score += 60;
            if (count($jsonLd) >= 2) {
                $score += 20; // Multiple schema types
            }
        } else {
            $recommendations[] = __('analysis.score_add_json_ld_structured_data');
        }

        // Microdata/RDFa as alternatives
        if (!empty($microdata) || !empty($rdfa)) {
            $score += 20;
        }

        if ($score === 0) {
            $recommendations[] = __('analysis.score_implement_structured_data');
        }

        return [
            'score' => min($score, $maxScore),
            'max_score' => $maxScore,
            'weight' => $this->weights['structured_data'],
            'issues' => [],
            'recommendations' => $recommendations,
            'metrics' => [
                'json_ld_schemas' => count($jsonLd),
                'microdata_schemas' => count($microdata),
                'rdfa_schemas' => count($rdfa),
                'total_schemas' => count($jsonLd) + count($microdata) + count($rdfa),
                'has_structured_data' => !empty($jsonLd) || !empty($microdata) || !empty($rdfa)
            ]
        ];
    }

    /**
     * Calculate overall weighted score
     */
    private function calculateOverallScore(array $scores): int
    {
        $totalWeightedScore = 0;
        $totalWeight = array_sum($this->weights);

        foreach ($scores as $category => $scoreData) {
            $categoryScore = $scoreData['score'] ?? 0;
            $weight = $this->weights[$category] ?? 0;
            $weightedScore = ($categoryScore / 100) * $weight;
            $totalWeightedScore += $weightedScore;
        }

        return round(($totalWeightedScore / $totalWeight) * 100);
    }

    /**
     * Generate detailed score breakdown
     */
    private function generateScoreBreakdown(array $scores): array
    {
        $breakdown = [];
        $totalWeight = array_sum($this->weights);

        foreach ($scores as $category => $scoreData) {
            $score = $scoreData['score'] ?? 0;
            $weight = $this->weights[$category] ?? 0;
            $contribution = round(($weight / $totalWeight) * ($score / 100) * 100, 1);

            $breakdown[$category] = [
                'score' => $score,
                'weight_percentage' => round(($weight / $totalWeight) * 100, 1),
                'contribution_to_overall' => $contribution,
                'status' => $this->getScoreStatus($score)
            ];
        }

        return $breakdown;
    }

    /**
     * Calculate letter grade based on overall score
     */
    private function calculateGrade(int $overallScore): string
    {
        if ($overallScore >= 90) return 'A';
        if ($overallScore >= 80) return 'B';
        if ($overallScore >= 70) return 'C';
        if ($overallScore >= 60) return 'D';
        return 'F';
    }

    /**
     * Get score status description
     */
    private function getScoreStatus(int $score): string
    {
        if ($score >= 90) return 'Excellent';
        if ($score >= 80) return 'Good';
        if ($score >= 70) return 'Average';
        if ($score >= 60) return 'Below Average';
        return 'Poor';
    }

    /**
     * Helper methods for text analysis
     */

    private function hasVariedWords(string $text): bool
    {
        $words = str_word_count(strtolower($text), 1);
        $uniqueWords = array_unique($words);
        return count($uniqueWords) >= max(3, count($words) * 0.7);
    }

    private function hasBrandPattern(string $title): bool
    {
        // Simple brand pattern detection (last word separated by | or -)
        return preg_match('/[\|\-]\s*[A-Z][a-zA-Z]+\s*$/', $title);
    }

    private function hasDuplicateWords(string $text): bool
    {
        $words = str_word_count(strtolower($text), 1);
        return count($words) !== count(array_unique($words));
    }

    private function hasCallToAction(string $description): bool
    {
        $ctaPatterns = [
            '/\b(learn more|read more|discover|explore|find out|get started|try now|shop now|buy now|order now|download|sign up|contact us)\b/i',
            '/[!]$/' // Ends with exclamation
        ];

        foreach ($ctaPatterns as $pattern) {
            if (preg_match($pattern, $description)) {
                return true;
            }
        }

        return false;
    }

    private function isDescriptive(string $description): bool
    {
        // Check if description has good variety of words and isn't just generic
        $words = str_word_count(strtolower($description), 1);
        $commonWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were'];
        $descriptiveWords = array_diff($words, $commonWords);

        return count($descriptiveWords) >= count($words) * 0.6;
    }

    private function assessHeadingQuality(array $headingsData): int
    {
        $qualityScore = 0;

        foreach ($headingsData as $level => $headings) {
            foreach ($headings as $heading) {
                $text = $heading['text'] ?? $heading;
                $length = $heading['length'] ?? strlen($text);

                // Good length range for headings
                if ($length >= 20 && $length <= 70) {
                    $qualityScore += 2;
                } elseif ($length >= 10) {
                    $qualityScore += 1;
                }
            }
        }

        return min($qualityScore, 10);
    }

    private function hasProperHierarchy(array $headingsData): bool
    {
        // Simplified hierarchy check - ensure we don't skip levels drastically
        $levels = [];
        for ($i = 1; $i <= 6; $i++) {
            if (!empty($headingsData["h{$i}"])) {
                $levels[] = $i;
            }
        }

        if (empty($levels)) return true;

        // Check if levels follow a reasonable sequence
        for ($i = 1; $i < count($levels); $i++) {
            if ($levels[$i] - $levels[$i-1] > 2) {
                return false; // Skipping more than one level is not ideal
            }
        }

        return true;
    }

    /**
     * Calculate advanced scores using SeoMetrics service
     */
    private function calculateAdvancedScores(array $parsedData, array $context, array $weights): array
    {
        $scores = [];

        // Use SeoMetrics for advanced calculations
        if (isset($parsedData['meta'])) {
            $titleData = [
                'title' => $parsedData['meta']['title'] ?? '',
                'title_length' => $parsedData['meta']['title_length'] ?? 0
            ];
            $scores['title'] = $this->seoMetrics->calculateAdvancedTitleScore($titleData, $context);
        } else {
            // Fallback to legacy method
            $scores['title'] = $this->scoreTitleOptimization($parsedData['meta'] ?? []);
        }

        // Meta description
        if (isset($parsedData['meta'])) {
            $scores['meta_description'] = $this->scoreMetaDescription($parsedData['meta']);
        }

        // Content with advanced metrics
        if (isset($parsedData['content'])) {
            $scores['content'] = $this->seoMetrics->calculateAdvancedContentScore($parsedData['content'], $context);
        } else {
            $scores['content'] = $this->scoreContentQuality($parsedData['content'] ?? []);
        }

        // Headings
        if (isset($parsedData['headings'])) {
            $scores['headings'] = $this->scoreHeadingsStructure($parsedData['headings']);
        }

        // Images
        if (isset($parsedData['images'])) {
            $scores['images'] = $this->scoreImageOptimization($parsedData['images']);
        }

        // Links
        if (isset($parsedData['links'])) {
            $scores['links'] = $this->scoreLinkStructure($parsedData['links']);
        }

        // Technical with performance integration
        if (isset($parsedData['technical'])) {
            $performanceData = $parsedData['performance'] ?? [];
            $scores['technical'] = $this->seoMetrics->calculateTechnicalPerformanceScore($parsedData['technical'], $performanceData);
        } else {
            $scores['technical'] = $this->scoreTechnicalSeo($parsedData['technical'] ?? []);
        }

        // Social media
        if (isset($parsedData['social_media'])) {
            $scores['social_media'] = $this->scoreSocialMediaTags($parsedData['social_media']);
        }

        // Structured data
        if (isset($parsedData['structured_data'])) {
            $scores['structured_data'] = $this->scoreStructuredData($parsedData['structured_data']);
        }

        return $scores;
    }

    /**
     * Calculate advanced overall score with competitive factors
     */
    private function calculateAdvancedOverallScore(array $scores, array $context): int
    {
        $industry = $context['industry'] ?? 'general';
        $weights = $this->seoMetrics->getIndustryWeights($industry);

        $totalWeightedScore = 0;
        $totalWeight = array_sum($weights);

        foreach ($scores as $category => $scoreData) {
            $categoryScore = $scoreData['score'] ?? 0;
            $weight = $weights[$category] ?? 0;
            $weightedScore = ($categoryScore / 100) * $weight;
            $totalWeightedScore += $weightedScore;
        }

        $baseScore = ($totalWeightedScore / $totalWeight) * 100;

        // Apply competitive factors
        $competitiveFactor = $this->getCompetitiveDifficultyMultiplier($context);
        $adjustedScore = $baseScore * $competitiveFactor;

        // Apply freshness factor if content age is provided
        if (isset($context['content_age_months'])) {
            $contentType = $context['content_type'] ?? 'evergreen';
            $freshnessFactor = $this->seoMetrics->getFreshnessFactor($contentType, $context['content_age_months']);
            $adjustedScore = $adjustedScore * $freshnessFactor;
        }

        return min(100, max(0, round($adjustedScore)));
    }

    /**
     * Generate advanced score breakdown with detailed analytics
     */
    private function generateAdvancedScoreBreakdown(array $scores, array $weights): array
    {
        $breakdown = [];
        $totalWeight = array_sum($weights);

        foreach ($scores as $category => $scoreData) {
            $score = $scoreData['score'] ?? 0;
            $weight = $weights[$category] ?? 0;
            $contribution = round(($weight / $totalWeight) * ($score / 100) * 100, 1);

            $breakdown[$category] = [
                'score' => $score,
                'weight_percentage' => round(($weight / $totalWeight) * 100, 1),
                'contribution_to_overall' => $contribution,
                'status' => $this->getScoreStatus($score),
                'impact_level' => $this->getImpactLevel($score, $weight),
                'recommendations_count' => count($scoreData['recommendations'] ?? []),
                'issues_count' => count($scoreData['issues'] ?? []),
                'metrics' => $scoreData['metrics'] ?? []
            ];
        }

        return $breakdown;
    }

    /**
     * Calculate performance metrics for scoring process
     */
    private function calculatePerformanceMetrics(float $startTime): array
    {
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        return [
            'calculation_time_ms' => round($executionTime, 2),
            'calculation_time_human' => $this->formatExecutionTime($executionTime),
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Get competitive factors from context
     */
    private function getCompetitiveFactors(array $context): array
    {
        $keywordDifficulty = $context['keyword_difficulty'] ?? 'medium';
        $searchVolume = $context['search_volume'] ?? 'medium';
        $competitorCount = $context['competitor_count'] ?? 0;

        return [
            'keyword_difficulty' => $keywordDifficulty,
            'search_volume' => $searchVolume,
            'competitor_count' => $competitorCount,
            'difficulty_multiplier' => $this->getCompetitiveDifficultyMultiplier($context),
            'market_saturation' => $this->calculateMarketSaturation($context)
        ];
    }

    /**
     * Get competitive difficulty multiplier
     */
    private function getCompetitiveDifficultyMultiplier(array $context): float
    {
        $keywordDifficulty = $context['keyword_difficulty'] ?? 'medium';
        $searchVolume = $context['search_volume'] ?? 'medium';

        return $this->seoMetrics->getCompetitiveDifficultyMultiplier($keywordDifficulty, $searchVolume);
    }

    /**
     * Calculate market saturation level
     */
    private function calculateMarketSaturation(array $context): string
    {
        $competitorCount = $context['competitor_count'] ?? 0;
        $keywordDifficulty = $context['keyword_difficulty'] ?? 'medium';

        if ($competitorCount > 100 && $keywordDifficulty === 'very_high') {
            return 'oversaturated';
        } elseif ($competitorCount > 50 && in_array($keywordDifficulty, ['high', 'very_high'])) {
            return 'saturated';
        } elseif ($competitorCount > 20) {
            return 'competitive';
        } else {
            return 'open';
        }
    }

    /**
     * Generate cache key for scoring results
     */
    private function generateScoreCacheKey(array $parsedData, array $context): string
    {
        $keyData = [
            'data_hash' => md5(serialize($parsedData)),
            'context_hash' => md5(serialize($context)),
            'version' => '2.0.0'
        ];

        return 'score_calc_' . md5(serialize($keyData));
    }

    /**
     * Check if caching should be used
     */
    private function shouldUseCache(array $context): bool
    {
        $cacheDisabled = $context['disable_cache'] ?? false;
        $isRealTime = $context['real_time'] ?? false;

        return !$cacheDisabled && !$isRealTime;
    }

    /**
     * Get impact level based on score and weight
     */
    private function getImpactLevel(int $score, float $weight): string
    {
        $impactScore = ($score / 100) * $weight;

        if ($impactScore >= 15) return 'critical';
        if ($impactScore >= 10) return 'high';
        if ($impactScore >= 5) return 'medium';
        return 'low';
    }

    /**
     * Format execution time for human readability
     */
    private function formatExecutionTime(float $milliseconds): string
    {
        if ($milliseconds < 1000) {
            return round($milliseconds, 1) . ' ms';
        }

        $seconds = $milliseconds / 1000;
        if ($seconds < 60) {
            return round($seconds, 2) . ' seconds';
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        return $minutes . 'm ' . round($remainingSeconds, 1) . 's';
    }
}

/**
 * Custom exception for score calculation errors
 */
class ScoreCalculationException extends \Exception
{
    //
}