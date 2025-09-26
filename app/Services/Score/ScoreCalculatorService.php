<?php

namespace App\Services\Score;

use Illuminate\Support\Facades\Log;

class ScoreCalculatorService
{
    /**
     * Scoring weights configuration
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

    /**
     * Calculate comprehensive SEO scores
     */
    public function calculate(array $parsedData): array
    {
        Log::debug('Starting SEO score calculation');

        try {
            $scores = [
                'title' => $this->scoreTitleOptimization($parsedData['meta'] ?? []),
                'meta_description' => $this->scoreMetaDescription($parsedData['meta'] ?? []),
                'headings' => $this->scoreHeadingsStructure($parsedData['headings'] ?? []),
                'content' => $this->scoreContentQuality($parsedData['content'] ?? []),
                'images' => $this->scoreImageOptimization($parsedData['images'] ?? []),
                'links' => $this->scoreLinkStructure($parsedData['links'] ?? []),
                'technical' => $this->scoreTechnicalSeo($parsedData['technical'] ?? []),
                'social_media' => $this->scoreSocialMediaTags($parsedData['social_media'] ?? []),
                'structured_data' => $this->scoreStructuredData($parsedData['structured_data'] ?? [])
            ];

            // Calculate weighted overall score
            $overallScore = $this->calculateOverallScore($scores);

            // Generate detailed breakdown
            $breakdown = $this->generateScoreBreakdown($scores);

            // Determine grade and recommendations
            $grade = $this->calculateGrade($overallScore);

            $result = [
                'overall_score' => $overallScore,
                'grade' => $grade,
                'category_scores' => $scores,
                'breakdown' => $breakdown,
                'max_possible_score' => 100,
                'scoring_version' => '1.0.0',
                'calculated_at' => now()->toISOString()
            ];

            Log::debug('SEO score calculation completed', [
                'overall_score' => $overallScore,
                'grade' => $grade
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
            $issues[] = 'Missing title tag';
            $recommendations[] = 'Add a descriptive title tag to your page';
        } else {
            $score += 40; // Base score for having a title

            // Length optimization
            if ($titleLength >= 30 && $titleLength <= 60) {
                $score += 30; // Optimal length
            } elseif ($titleLength >= 20 && $titleLength <= 70) {
                $score += 20; // Acceptable length
            } elseif ($titleLength < 30) {
                $issues[] = 'Title too short';
                $recommendations[] = 'Expand your title to 30-60 characters for better visibility';
            } elseif ($titleLength > 60) {
                $issues[] = 'Title too long';
                $recommendations[] = 'Shorten your title to under 60 characters to prevent truncation';
            }

            // Check for keywords (simplified - would need actual target keywords)
            if ($this->hasVariedWords($title)) {
                $score += 15;
            } else {
                $issues[] = 'Title lacks keyword variety';
                $recommendations[] = 'Include relevant keywords in your title';
            }

            // Brand mention (if title ends with brand name pattern)
            if ($this->hasBrandPattern($title)) {
                $score += 10;
            }

            // Duplicate words penalty
            if ($this->hasDuplicateWords($title)) {
                $score -= 5;
                $issues[] = 'Duplicate words in title';
                $recommendations[] = 'Remove duplicate words from title for clarity';
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
            $issues[] = 'Missing meta description';
            $recommendations[] = 'Add a compelling meta description to improve click-through rates';
        } else {
            $score += 50; // Base score for having a description

            // Length optimization
            if ($descriptionLength >= 120 && $descriptionLength <= 160) {
                $score += 35; // Optimal length
            } elseif ($descriptionLength >= 100 && $descriptionLength <= 170) {
                $score += 25; // Acceptable length
            } elseif ($descriptionLength < 120) {
                $issues[] = 'Description too short';
                $recommendations[] = 'Expand your meta description to 120-160 characters';
            } elseif ($descriptionLength > 160) {
                $issues[] = 'Description too long';
                $recommendations[] = 'Shorten your meta description to under 160 characters';
            }

            // Call-to-action check
            if ($this->hasCallToAction($description)) {
                $score += 10;
            } else {
                $recommendations[] = 'Consider adding a call-to-action in your meta description';
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
            $issues[] = 'Missing H1 tag';
            $recommendations[] = 'Add an H1 heading to clearly define the main topic of your page';
        } elseif ($h1Count === 1) {
            $score += 40; // Perfect H1 structure
        } else {
            $score += 20; // Multiple H1s are acceptable but not optimal
            $issues[] = 'Multiple H1 tags found';
            $recommendations[] = 'Use only one H1 tag per page for better SEO structure';
        }

        // H2-H6 structure
        if ($h2Count > 0) {
            $score += 25; // Good use of subheadings
            if ($h3Count > 0 && $h3Count <= $h2Count * 3) {
                $score += 15; // Good hierarchical structure
            }
        } else {
            $issues[] = 'No H2 headings found';
            $recommendations[] = 'Use H2 headings to structure your content into sections';
        }

        // Heading content quality
        $headingQualityScore = $this->assessHeadingQuality($headingsData);
        $score += $headingQualityScore;

        // Hierarchy check
        if ($this->hasProperHierarchy($headingsData)) {
            $score += 10;
        } else {
            $issues[] = 'Improper heading hierarchy';
            $recommendations[] = 'Maintain proper heading hierarchy (H1 → H2 → H3...)';
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
            $issues[] = 'Content too short';
            $recommendations[] = 'Expand your content to at least 300 words for better SEO value';
        }

        // Text-to-HTML ratio
        if ($textToHtmlRatio >= 25) {
            $score += 25; // Good content density
        } elseif ($textToHtmlRatio >= 15) {
            $score += 15; // Acceptable content density
        } else {
            $issues[] = 'Low text-to-HTML ratio';
            $recommendations[] = 'Increase the amount of text content relative to HTML markup';
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
                'issues' => ['No images found'],
                'recommendations' => ['Consider adding relevant images to enhance user experience'],
                'metrics' => ['total_images' => 0]
            ];
        }

        // Alt text penalty
        if ($withoutAlt > 0) {
            $penaltyPercent = ($withoutAlt / $totalImages) * 100;
            $score -= $penaltyPercent;
            $issues[] = "{$withoutAlt} images missing alt text";
            $recommendations[] = "Add descriptive alt text to all {$withoutAlt} images missing alt attributes";
        }

        // Image title attributes (optional but beneficial)
        $withoutTitle = $imagesData['without_title_count'] ?? 0;
        if ($withoutTitle > 0 && $withoutTitle === $totalImages) {
            $score -= 10; // Minor penalty for no title attributes
            $recommendations[] = 'Consider adding title attributes to images for better accessibility';
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
            $issues[] = 'No links found';
            $recommendations[] = 'Add both internal and external links to improve SEO and user experience';
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
            $issues[] = 'No internal links found';
            $recommendations[] = 'Add internal links to related pages on your website';
        }

        // External linking
        if ($externalLinks > 0) {
            $score += 20; // Good for credibility
            if ($externalLinks <= $internalLinks) {
                $score += 10; // Good balance
            }
        } else {
            $recommendations[] = 'Consider adding external links to authoritative sources';
        }

        // Anchor text quality
        if ($emptyAnchorCount === 0) {
            $score += 20; // All links have anchor text
        } else {
            $penaltyPercent = min(($emptyAnchorCount / $totalLinks) * 50, 20);
            $score -= $penaltyPercent;
            $issues[] = "{$emptyAnchorCount} links with empty anchor text";
            $recommendations[] = 'Add descriptive anchor text to all links';
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
            $issues[] = 'Missing or invalid DOCTYPE';
            $recommendations[] = 'Add proper HTML5 DOCTYPE declaration';
        }

        // Language attribute
        $langAttribute = $technicalData['lang_attribute'] ?? '';
        if (!empty($langAttribute)) {
            $score += 15;
        } else {
            $issues[] = 'Missing lang attribute';
            $recommendations[] = 'Add lang attribute to HTML tag for accessibility';
        }

        // SSL/HTTPS
        $sslRequired = $technicalData['ssl_required'] ?? false;
        if ($sslRequired) {
            $score += 20;
        } else {
            $issues[] = 'Not using HTTPS';
            $recommendations[] = 'Implement HTTPS for better security and SEO';
        }

        // Meta viewport (mobile-friendly)
        // This would be checked in meta tags parsing
        $score += 15; // Assume present for now

        // Schema markup presence
        $schemaPresent = $technicalData['schema_markup_present'] ?? false;
        if ($schemaPresent) {
            $score += 20;
        } else {
            $recommendations[] = 'Add structured data markup to enhance search results';
        }

        // Open Graph presence
        $ogPresent = $technicalData['open_graph_present'] ?? false;
        if ($ogPresent) {
            $score += 10;
        } else {
            $recommendations[] = 'Add Open Graph tags for better social media sharing';
        }

        // Performance hints
        $inlineStyles = $technicalData['inline_styles_count'] ?? 0;
        $inlineScripts = $technicalData['inline_scripts_count'] ?? 0;

        if ($inlineStyles === 0 && $inlineScripts === 0) {
            $score += 5; // Clean separation of concerns
        } elseif ($inlineStyles + $inlineScripts > 10) {
            $issues[] = 'Too many inline styles/scripts';
            $recommendations[] = 'Move inline styles and scripts to external files';
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
            $recommendations[] = 'Complete Open Graph tags setup for better social sharing';
        }

        // Twitter Cards scoring
        $twitterScore = 0;
        if (!empty($twitterCards['card'])) {
            $twitterScore += 20;
            if (!empty($twitterCards['title']) && !empty($twitterCards['description'])) {
                $twitterScore += 20;
            }
        } else {
            $recommendations[] = 'Add Twitter Card tags for better Twitter sharing';
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
            $recommendations[] = 'Add JSON-LD structured data for better search engine understanding';
        }

        // Microdata/RDFa as alternatives
        if (!empty($microdata) || !empty($rdfa)) {
            $score += 20;
        }

        if ($score === 0) {
            $recommendations[] = 'Implement structured data markup to help search engines understand your content';
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
}

/**
 * Custom exception for score calculation errors
 */
class ScoreCalculationException extends \Exception
{
    //
}