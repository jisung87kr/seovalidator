<?php

namespace App\Services\Content;

use Illuminate\Support\Facades\Log;

/**
 * Heading hierarchy (H1-H6) structure validation service
 * Validates proper heading structure, accessibility compliance,
 * and SEO best practices for heading usage
 */
class HeadingHierarchyValidator
{
    /**
     * Validate heading hierarchy and structure
     */
    public function validate(array $headingsData, array $options = []): array
    {
        Log::debug('Starting heading hierarchy validation', [
            'headings_count' => array_sum(array_map('count', $headingsData))
        ]);

        $startTime = microtime(true);

        try {
            // Extract and organize heading data
            $headings = $this->organizeHeadings($headingsData);

            // Perform hierarchy validation
            $hierarchyValidation = $this->validateHierarchy($headings);

            // Check accessibility compliance
            $accessibilityCheck = $this->checkAccessibilityCompliance($headings);

            // Analyze SEO optimization
            $seoAnalysis = $this->analyzeSeoOptimization($headings);

            // Check heading content quality
            $contentQuality = $this->analyzeHeadingContent($headings);

            // Validate heading distribution
            $distributionAnalysis = $this->analyzeHeadingDistribution($headings);

            // Calculate overall heading score
            $overallScore = $this->calculateHeadingScore([
                'hierarchy' => $hierarchyValidation,
                'accessibility' => $accessibilityCheck,
                'seo' => $seoAnalysis,
                'content_quality' => $contentQuality,
                'distribution' => $distributionAnalysis
            ]);

            // Generate recommendations
            $recommendations = $this->generateHeadingRecommendations([
                'hierarchy' => $hierarchyValidation,
                'accessibility' => $accessibilityCheck,
                'seo' => $seoAnalysis,
                'content_quality' => $contentQuality,
                'distribution' => $distributionAnalysis,
                'overall_score' => $overallScore
            ]);

            $analysis = [
                'analyzed_at' => date('c'),
                'analysis_duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'overall_score' => $overallScore,
                'total_headings' => count($headings),
                'heading_summary' => $this->generateHeadingSummary($headings),
                'hierarchy_validation' => $hierarchyValidation,
                'accessibility_compliance' => $accessibilityCheck,
                'seo_analysis' => $seoAnalysis,
                'content_quality' => $contentQuality,
                'distribution_analysis' => $distributionAnalysis,
                'heading_outline' => $this->generateHeadingOutline($headings),
                'recommendations' => $recommendations,
                'heading_insights' => $this->generateHeadingInsights($overallScore, $hierarchyValidation, $headings)
            ];

            Log::info('Heading hierarchy validation completed', [
                'total_headings' => count($headings),
                'overall_score' => $overallScore,
                'hierarchy_issues' => count($hierarchyValidation['issues']),
                'accessibility_issues' => count($accessibilityCheck['issues'])
            ]);

            return $analysis;

        } catch (\Exception $e) {
            Log::error('Heading hierarchy validation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Organize headings into a flat list with levels
     */
    private function organizeHeadings(array $headingsData): array
    {
        $headings = [];
        $position = 0;

        for ($level = 1; $level <= 6; $level++) {
            $levelKey = "h{$level}";
            if (isset($headingsData[$levelKey])) {
                foreach ($headingsData[$levelKey] as $heading) {
                    $headings[] = [
                        'level' => $level,
                        'text' => $heading['text'],
                        'length' => $heading['length'],
                        'position' => $position++,
                        'level_position' => $heading['position'] ?? 1,
                        'word_count' => str_word_count($heading['text']),
                        'is_empty' => empty(trim($heading['text']))
                    ];
                }
            }
        }

        return $headings;
    }

    /**
     * Validate heading hierarchy structure
     */
    private function validateHierarchy(array $headings): array
    {
        $issues = [];
        $score = 100;

        if (empty($headings)) {
            return [
                'score' => 0,
                'is_valid' => false,
                'issues' => ['No headings found'],
                'hierarchy_structure' => [],
                'skipped_levels' => [],
                'multiple_h1' => false
            ];
        }

        // Check for H1 presence and multiplicity
        $h1Count = count(array_filter($headings, fn($h) => $h['level'] === 1));

        if ($h1Count === 0) {
            $issues[] = 'Missing H1 heading (critical for SEO and accessibility)';
            $score -= 30;
        } elseif ($h1Count > 1) {
            $issues[] = "Multiple H1 headings found ({$h1Count}). Only one H1 per page is recommended";
            $score -= 20;
        }

        // Check for logical hierarchy progression
        $previousLevel = 0;
        $skippedLevels = [];
        $hierarchyStructure = [];

        foreach ($headings as $index => $heading) {
            $currentLevel = $heading['level'];

            // Track hierarchy structure
            $hierarchyStructure[] = [
                'level' => $currentLevel,
                'text' => $heading['text'],
                'position' => $index + 1
            ];

            // Check for skipped levels
            if ($previousLevel > 0 && $currentLevel > $previousLevel + 1) {
                $skipped = range($previousLevel + 1, $currentLevel - 1);
                $skippedLevels = array_merge($skippedLevels, $skipped);
                $issues[] = "Skipped heading level(s) from H{$previousLevel} to H{$currentLevel} (position " . ($index + 1) . ")";
                $score -= 10;
            }

            $previousLevel = $currentLevel;
        }

        // Remove duplicate skipped levels
        $skippedLevels = array_unique($skippedLevels);

        // Check for proper heading progression (no deep nesting without intermediate levels)
        $deepNestingIssues = $this->checkDeepNesting($headings);
        if (!empty($deepNestingIssues)) {
            $issues = array_merge($issues, $deepNestingIssues);
            $score -= count($deepNestingIssues) * 5;
        }

        return [
            'score' => max(0, min(100, round($score, 1))),
            'is_valid' => $score >= 70,
            'issues' => $issues,
            'hierarchy_structure' => $hierarchyStructure,
            'skipped_levels' => $skippedLevels,
            'multiple_h1' => $h1Count > 1,
            'h1_count' => $h1Count,
            'progression_analysis' => $this->analyzeProgression($headings)
        ];
    }

    /**
     * Check accessibility compliance for headings
     */
    private function checkAccessibilityCompliance(array $headings): array
    {
        $issues = [];
        $score = 100;

        if (empty($headings)) {
            return [
                'score' => 0,
                'is_compliant' => false,
                'issues' => ['No headings found - critical accessibility issue'],
                'wcag_compliance' => 'Fail'
            ];
        }

        // Check for empty headings
        $emptyHeadings = array_filter($headings, fn($h) => $h['is_empty']);
        if (!empty($emptyHeadings)) {
            $count = count($emptyHeadings);
            $issues[] = "Found {$count} empty heading(s) - violates WCAG guidelines";
            $score -= $count * 15;
        }

        // Check heading length (too short or too long)
        foreach ($headings as $heading) {
            if (!$heading['is_empty']) {
                if ($heading['length'] < 3) {
                    $issues[] = "Heading too short: \"{$heading['text']}\" (minimum 3 characters recommended)";
                    $score -= 5;
                } elseif ($heading['length'] > 70) {
                    $issues[] = "Heading too long: \"{$heading['text']}\" ({$heading['length']} characters, maximum 70 recommended)";
                    $score -= 5;
                }
            }
        }

        // Check for descriptive content
        $nonDescriptiveHeadings = $this->findNonDescriptiveHeadings($headings);
        if (!empty($nonDescriptiveHeadings)) {
            foreach ($nonDescriptiveHeadings as $heading) {
                $issues[] = "Non-descriptive heading: \"{$heading['text']}\"";
                $score -= 10;
            }
        }

        // Check for screen reader compatibility
        $screenReaderIssues = $this->checkScreenReaderCompatibility($headings);
        if (!empty($screenReaderIssues)) {
            $issues = array_merge($issues, $screenReaderIssues);
            $score -= count($screenReaderIssues) * 8;
        }

        $wcagCompliance = $this->determineWCAGCompliance($score, $issues);

        return [
            'score' => max(0, min(100, round($score, 1))),
            'is_compliant' => $score >= 80,
            'issues' => $issues,
            'wcag_compliance' => $wcagCompliance,
            'empty_headings_count' => count($emptyHeadings),
            'accessibility_features' => $this->analyzeAccessibilityFeatures($headings)
        ];
    }

    /**
     * Analyze SEO optimization of headings
     */
    private function analyzeSeoOptimization(array $headings): array
    {
        $score = 100;
        $seoIssues = [];

        if (empty($headings)) {
            return [
                'score' => 0,
                'seo_optimized' => false,
                'issues' => ['No headings found - poor for SEO'],
                'keyword_optimization' => []
            ];
        }

        // H1 SEO analysis
        $h1Headings = array_filter($headings, fn($h) => $h['level'] === 1);
        if (empty($h1Headings)) {
            $seoIssues[] = 'Missing H1 heading - critical for SEO';
            $score -= 30;
        } else {
            $h1 = reset($h1Headings);
            if ($h1['word_count'] < 3) {
                $seoIssues[] = 'H1 heading is too short for SEO optimization';
                $score -= 15;
            } elseif ($h1['word_count'] > 15) {
                $seoIssues[] = 'H1 heading is too long for optimal SEO';
                $score -= 10;
            }
        }

        // Check for heading hierarchy depth (SEO best practice)
        $maxLevel = max(array_column($headings, 'level'));
        if ($maxLevel > 4) {
            $seoIssues[] = "Heading hierarchy too deep (H{$maxLevel}). Consider using H1-H4 for better SEO";
            $score -= 10;
        }

        // Check heading distribution
        $headingLevels = array_count_values(array_column($headings, 'level'));
        $distribution = $this->analyzeHeadingLevelDistribution($headingLevels);
        if ($distribution['is_poorly_distributed']) {
            $seoIssues[] = 'Poor heading level distribution affects content structure';
            $score -= 15;
        }

        // Keyword optimization analysis
        $keywordOptimization = $this->analyzeHeadingKeywords($headings);

        return [
            'score' => max(0, min(100, round($score, 1))),
            'seo_optimized' => $score >= 70,
            'issues' => $seoIssues,
            'keyword_optimization' => $keywordOptimization,
            'heading_distribution' => $distribution,
            'seo_recommendations' => $this->generateSeoRecommendations($headings, $score)
        ];
    }

    /**
     * Analyze heading content quality
     */
    private function analyzeHeadingContent(array $headings): array
    {
        $score = 100;
        $contentIssues = [];

        if (empty($headings)) {
            return [
                'score' => 0,
                'quality_level' => 'Poor',
                'issues' => ['No headings to analyze'],
                'content_analysis' => []
            ];
        }

        $contentAnalysis = [];

        foreach ($headings as $heading) {
            $analysis = [
                'text' => $heading['text'],
                'level' => $heading['level'],
                'word_count' => $heading['word_count'],
                'character_count' => $heading['length'],
                'is_descriptive' => $this->isDescriptiveHeading($heading['text']),
                'has_action_words' => $this->hasActionWords($heading['text']),
                'clarity_score' => $this->calculateClarityScore($heading['text'])
            ];

            // Check for quality issues
            if (!$analysis['is_descriptive']) {
                $contentIssues[] = "Heading not descriptive enough: \"{$heading['text']}\"";
                $score -= 8;
            }

            if ($heading['word_count'] === 1 && $heading['level'] <= 3) {
                $contentIssues[] = "Single-word heading may lack context: \"{$heading['text']}\"";
                $score -= 5;
            }

            if ($analysis['clarity_score'] < 60) {
                $contentIssues[] = "Heading clarity could be improved: \"{$heading['text']}\"";
                $score -= 5;
            }

            $contentAnalysis[] = $analysis;
        }

        // Check for duplicate headings
        $duplicates = $this->findDuplicateHeadings($headings);
        if (!empty($duplicates)) {
            foreach ($duplicates as $duplicate) {
                $contentIssues[] = "Duplicate heading text: \"{$duplicate}\"";
                $score -= 10;
            }
        }

        return [
            'score' => max(0, min(100, round($score, 1))),
            'quality_level' => $this->getQualityLevel($score),
            'issues' => $contentIssues,
            'content_analysis' => $contentAnalysis,
            'duplicate_headings' => $duplicates,
            'average_clarity_score' => $this->calculateAverageClarityScore($contentAnalysis)
        ];
    }

    /**
     * Analyze heading distribution throughout content
     */
    private function analyzeHeadingDistribution(array $headings): array
    {
        if (empty($headings)) {
            return [
                'score' => 0,
                'distribution_quality' => 'Poor',
                'level_distribution' => [],
                'spacing_analysis' => []
            ];
        }

        $levelDistribution = array_count_values(array_column($headings, 'level'));
        $spacingAnalysis = $this->analyzeHeadingSpacing($headings);

        $score = 100;

        // Check for reasonable distribution across levels
        $totalHeadings = count($headings);

        // Penalize if too many H1s
        if (($levelDistribution[1] ?? 0) > 1) {
            $score -= 20;
        }

        // Reward good use of H2s
        $h2Count = $levelDistribution[2] ?? 0;
        if ($h2Count >= 2 && $h2Count <= 6) {
            $score += 10;
        } elseif ($h2Count > 6) {
            $score -= 10;
        }

        // Check if headings are well-spaced
        if ($spacingAnalysis['has_clustering']) {
            $score -= 15;
        }

        return [
            'score' => max(0, min(100, round($score, 1))),
            'distribution_quality' => $this->getDistributionQuality($score),
            'level_distribution' => $levelDistribution,
            'spacing_analysis' => $spacingAnalysis,
            'total_headings' => $totalHeadings,
            'distribution_insights' => $this->generateDistributionInsights($levelDistribution, $totalHeadings)
        ];
    }

    /**
     * Calculate overall heading score
     */
    private function calculateHeadingScore(array $analyses): array
    {
        $weights = [
            'hierarchy' => 0.30,      // 30%
            'accessibility' => 0.25,   // 25%
            'seo' => 0.20,            // 20%
            'content_quality' => 0.15, // 15%
            'distribution' => 0.10     // 10%
        ];

        $totalScore = 0;
        $componentScores = [];

        foreach ($analyses as $type => $analysis) {
            $score = $analysis['score'] ?? 0;
            $weight = $weights[$type] ?? 0;
            $componentScores[$type] = $score;
            $totalScore += $score * $weight;
        }

        return [
            'overall' => round($totalScore, 1),
            'components' => $componentScores,
            'weights' => $weights,
            'grade' => $this->getHeadingGrade($totalScore)
        ];
    }

    // Helper methods

    private function checkDeepNesting(array $headings): array
    {
        $issues = [];
        $previousLevel = 0;

        foreach ($headings as $index => $heading) {
            $currentLevel = $heading['level'];

            // Check for jumps of more than 2 levels
            if ($previousLevel > 0 && $currentLevel > $previousLevel + 2) {
                $issues[] = "Deep nesting detected: jumped from H{$previousLevel} to H{$currentLevel} at position " . ($index + 1);
            }

            $previousLevel = $currentLevel;
        }

        return $issues;
    }

    private function analyzeProgression(array $headings): array
    {
        $progression = [];
        $previousLevel = 0;

        foreach ($headings as $heading) {
            $currentLevel = $heading['level'];
            $change = $previousLevel === 0 ? 'start' :
                     ($currentLevel > $previousLevel ? 'deeper' :
                     ($currentLevel < $previousLevel ? 'shallower' : 'same'));

            $progression[] = [
                'level' => $currentLevel,
                'change' => $change,
                'text' => $heading['text']
            ];

            $previousLevel = $currentLevel;
        }

        return $progression;
    }

    private function findNonDescriptiveHeadings(array $headings): array
    {
        $nonDescriptive = [];
        $vagueWords = ['click', 'here', 'more', 'read', 'info', 'content', 'section', 'page'];

        foreach ($headings as $heading) {
            $text = strtolower($heading['text']);

            if (in_array($text, $vagueWords) ||
                strlen($text) < 5 ||
                preg_match('/^(section|chapter|part)\s*\d*$/i', $text)) {
                $nonDescriptive[] = $heading;
            }
        }

        return $nonDescriptive;
    }

    private function checkScreenReaderCompatibility(array $headings): array
    {
        $issues = [];

        foreach ($headings as $heading) {
            // Check for special characters that might confuse screen readers
            if (preg_match('/[^\w\s\-\.,!?()]/', $heading['text'])) {
                $issues[] = "Heading contains special characters that may affect screen readers: \"{$heading['text']}\"";
            }

            // Check for all caps (not good for screen readers)
            if (ctype_upper($heading['text']) && strlen($heading['text']) > 5) {
                $issues[] = "Heading in all caps may be read as acronym by screen readers: \"{$heading['text']}\"";
            }
        }

        return $issues;
    }

    private function determineWCAGCompliance(float $score, array $issues): string
    {
        $criticalIssues = array_filter($issues, fn($issue) =>
            str_contains($issue, 'empty') ||
            str_contains($issue, 'Missing H1') ||
            str_contains($issue, 'violates WCAG')
        );

        if (!empty($criticalIssues)) {
            return 'Fail';
        } elseif ($score >= 80) {
            return 'Pass';
        } else {
            return 'Partial';
        }
    }

    private function analyzeAccessibilityFeatures(array $headings): array
    {
        return [
            'has_logical_hierarchy' => $this->hasLogicalHierarchy($headings),
            'descriptive_headings' => count(array_filter($headings, fn($h) => $this->isDescriptiveHeading($h['text']))),
            'appropriate_length' => count(array_filter($headings, fn($h) => $h['length'] >= 3 && $h['length'] <= 70)),
            'total_headings' => count($headings)
        ];
    }

    private function analyzeHeadingLevelDistribution(array $headingLevels): array
    {
        $totalHeadings = array_sum($headingLevels);
        $distribution = [];

        for ($i = 1; $i <= 6; $i++) {
            $count = $headingLevels[$i] ?? 0;
            $percentage = $totalHeadings > 0 ? ($count / $totalHeadings) * 100 : 0;
            $distribution["h{$i}"] = [
                'count' => $count,
                'percentage' => round($percentage, 1)
            ];
        }

        // Check if distribution is poor
        $h1Percentage = $distribution['h1']['percentage'];
        $h2Percentage = $distribution['h2']['percentage'];

        $isPoorlyDistributed =
            $h1Percentage > 20 || // Too many H1s
            ($h2Percentage < 30 && $totalHeadings > 3) || // Too few H2s for content size
            ($distribution['h5']['percentage'] > 20 || $distribution['h6']['percentage'] > 15); // Too many deep headings

        return [
            'distribution' => $distribution,
            'is_poorly_distributed' => $isPoorlyDistributed,
            'total_headings' => $totalHeadings
        ];
    }

    private function analyzeHeadingKeywords(array $headings): array
    {
        // Basic keyword analysis for headings
        $keywordAnalysis = [
            'h1_keywords' => [],
            'repeated_keywords' => [],
            'keyword_density' => 0
        ];

        $h1Headings = array_filter($headings, fn($h) => $h['level'] === 1);
        if (!empty($h1Headings)) {
            $h1 = reset($h1Headings);
            $keywordAnalysis['h1_keywords'] = array_filter(
                explode(' ', strtolower($h1['text'])),
                fn($word) => strlen($word) > 3
            );
        }

        // Find repeated keywords across headings
        $allWords = [];
        foreach ($headings as $heading) {
            $words = explode(' ', strtolower($heading['text']));
            foreach ($words as $word) {
                if (strlen($word) > 3) {
                    $allWords[] = $word;
                }
            }
        }

        $wordCounts = array_count_values($allWords);
        $keywordAnalysis['repeated_keywords'] = array_filter($wordCounts, fn($count) => $count > 1);

        return $keywordAnalysis;
    }

    private function generateSeoRecommendations(array $headings, float $score): array
    {
        $recommendations = [];

        if ($score < 70) {
            $recommendations[] = 'Consider optimizing heading structure for better SEO';
        }

        $h1Count = count(array_filter($headings, fn($h) => $h['level'] === 1));
        if ($h1Count === 0) {
            $recommendations[] = 'Add an H1 heading with your primary keyword';
        } elseif ($h1Count > 1) {
            $recommendations[] = 'Use only one H1 heading per page';
        }

        $h2Count = count(array_filter($headings, fn($h) => $h['level'] === 2));
        if ($h2Count < 2 && count($headings) > 3) {
            $recommendations[] = 'Add more H2 headings to break up content sections';
        }

        return $recommendations;
    }

    private function isDescriptiveHeading(string $text): bool
    {
        $vaguePatterns = [
            '/^(click|here|more|read|info|content|section|page)$/i',
            '/^(introduction|conclusion|overview)$/i',
            '/^section\s*\d*$/i'
        ];

        foreach ($vaguePatterns as $pattern) {
            if (preg_match($pattern, trim($text))) {
                return false;
            }
        }

        return strlen(trim($text)) >= 5 && str_word_count($text) >= 2;
    }

    private function hasActionWords(string $text): bool
    {
        $actionWords = ['how', 'what', 'why', 'when', 'where', 'guide', 'tips', 'steps', 'learn', 'create', 'build'];
        $text = strtolower($text);

        foreach ($actionWords as $word) {
            if (str_contains($text, $word)) {
                return true;
            }
        }

        return false;
    }

    private function calculateClarityScore(string $text): float
    {
        $score = 100;

        // Penalty for vague words
        $vagueWords = ['thing', 'stuff', 'something', 'various', 'many', 'some'];
        foreach ($vagueWords as $word) {
            if (str_contains(strtolower($text), $word)) {
                $score -= 20;
            }
        }

        // Bonus for specific words
        $specificWords = ['how', 'what', 'why', 'complete', 'ultimate', 'comprehensive'];
        foreach ($specificWords as $word) {
            if (str_contains(strtolower($text), $word)) {
                $score += 10;
            }
        }

        // Length considerations
        $wordCount = str_word_count($text);
        if ($wordCount < 2) {
            $score -= 30;
        } elseif ($wordCount > 10) {
            $score -= 20;
        }

        return max(0, min(100, $score));
    }

    private function findDuplicateHeadings(array $headings): array
    {
        $textCounts = array_count_values(array_column($headings, 'text'));
        return array_keys(array_filter($textCounts, fn($count) => $count > 1));
    }

    private function calculateAverageClarityScore(array $contentAnalysis): float
    {
        if (empty($contentAnalysis)) return 0;

        $total = array_sum(array_column($contentAnalysis, 'clarity_score'));
        return round($total / count($contentAnalysis), 1);
    }

    private function analyzeHeadingSpacing(array $headings): array
    {
        $positions = array_column($headings, 'position');
        $spacing = [];
        $hasClustering = false;

        for ($i = 1; $i < count($positions); $i++) {
            $gap = $positions[$i] - $positions[$i-1];
            $spacing[] = $gap;

            // If gap is 1, headings are clustered
            if ($gap === 1) {
                $hasClustering = true;
            }
        }

        return [
            'spacing' => $spacing,
            'has_clustering' => $hasClustering,
            'average_spacing' => !empty($spacing) ? round(array_sum($spacing) / count($spacing), 1) : 0
        ];
    }

    private function hasLogicalHierarchy(array $headings): bool
    {
        if (empty($headings)) return false;

        $h1Count = count(array_filter($headings, fn($h) => $h['level'] === 1));
        if ($h1Count !== 1) return false;

        // Check for logical progression
        $previousLevel = 0;
        foreach ($headings as $heading) {
            if ($previousLevel > 0 && $heading['level'] > $previousLevel + 1) {
                return false;
            }
            $previousLevel = $heading['level'];
        }

        return true;
    }

    private function getQualityLevel(float $score): string
    {
        if ($score >= 85) return 'Excellent';
        if ($score >= 70) return 'Good';
        if ($score >= 55) return 'Fair';
        if ($score >= 40) return 'Poor';
        return 'Very Poor';
    }

    private function getDistributionQuality(float $score): string
    {
        if ($score >= 80) return 'Well Distributed';
        if ($score >= 60) return 'Adequately Distributed';
        if ($score >= 40) return 'Poorly Distributed';
        return 'Very Poorly Distributed';
    }

    private function getHeadingGrade(float $score): string
    {
        if ($score >= 90) return 'A+';
        if ($score >= 85) return 'A';
        if ($score >= 80) return 'A-';
        if ($score >= 75) return 'B+';
        if ($score >= 70) return 'B';
        if ($score >= 65) return 'B-';
        if ($score >= 60) return 'C+';
        if ($score >= 55) return 'C';
        if ($score >= 50) return 'C-';
        return 'D or lower';
    }

    private function generateHeadingSummary(array $headings): array
    {
        $levelCounts = array_count_values(array_column($headings, 'level'));

        return [
            'total_headings' => count($headings),
            'by_level' => $levelCounts,
            'has_h1' => isset($levelCounts[1]) && $levelCounts[1] > 0,
            'multiple_h1' => isset($levelCounts[1]) && $levelCounts[1] > 1,
            'deepest_level' => !empty($headings) ? max(array_column($headings, 'level')) : 0,
            'average_length' => !empty($headings) ? round(array_sum(array_column($headings, 'length')) / count($headings), 1) : 0
        ];
    }

    private function generateHeadingOutline(array $headings): array
    {
        $outline = [];

        foreach ($headings as $heading) {
            $outline[] = [
                'level' => $heading['level'],
                'text' => $heading['text'],
                'indent' => str_repeat('  ', $heading['level'] - 1) . 'H' . $heading['level'] . ': ' . $heading['text']
            ];
        }

        return $outline;
    }

    private function generateDistributionInsights(array $levelDistribution, int $totalHeadings): array
    {
        $insights = [];

        if ($totalHeadings === 0) {
            $insights[] = 'No headings found';
            return $insights;
        }

        $h1Count = $levelDistribution[1] ?? 0;
        $h2Count = $levelDistribution[2] ?? 0;

        if ($h1Count === 1) {
            $insights[] = 'Good H1 usage (exactly one)';
        } elseif ($h1Count === 0) {
            $insights[] = 'Missing H1 heading';
        } else {
            $insights[] = "Multiple H1 headings ({$h1Count}) detected";
        }

        if ($h2Count >= 2) {
            $insights[] = 'Good content structure with multiple sections (H2s)';
        } elseif ($h2Count === 1) {
            $insights[] = 'Limited content sectioning (only one H2)';
        } else {
            $insights[] = 'No content sections defined (no H2 headings)';
        }

        $deepHeadings = ($levelDistribution[5] ?? 0) + ($levelDistribution[6] ?? 0);
        if ($deepHeadings > 0) {
            $insights[] = 'Deep heading hierarchy may indicate over-complex structure';
        }

        return $insights;
    }

    private function generateHeadingRecommendations(array $analyses): array
    {
        $recommendations = [];

        // Hierarchy recommendations
        $hierarchy = $analyses['hierarchy'];
        if (!$hierarchy['is_valid']) {
            foreach ($hierarchy['issues'] as $issue) {
                $recommendations[] = [
                    'type' => str_contains($issue, 'Missing H1') ? 'error' : 'warning',
                    'category' => 'hierarchy',
                    'message' => $issue,
                    'impact' => str_contains($issue, 'H1') ? 'high' : 'medium',
                    'fix' => $this->getHierarchyFix($issue)
                ];
            }
        }

        // Accessibility recommendations
        $accessibility = $analyses['accessibility'];
        if (!$accessibility['is_compliant']) {
            foreach ($accessibility['issues'] as $issue) {
                $recommendations[] = [
                    'type' => str_contains($issue, 'violates WCAG') ? 'error' : 'warning',
                    'category' => 'accessibility',
                    'message' => $issue,
                    'impact' => str_contains($issue, 'empty') ? 'high' : 'medium',
                    'fix' => $this->getAccessibilityFix($issue)
                ];
            }
        }

        // SEO recommendations
        $seo = $analyses['seo'];
        if (!$seo['seo_optimized']) {
            foreach ($seo['issues'] as $issue) {
                $recommendations[] = [
                    'type' => str_contains($issue, 'Missing H1') ? 'error' : 'suggestion',
                    'category' => 'seo',
                    'message' => $issue,
                    'impact' => str_contains($issue, 'H1') ? 'high' : 'medium',
                    'fix' => $this->getSeoFix($issue)
                ];
            }
        }

        return $recommendations;
    }

    private function getHierarchyFix(string $issue): string
    {
        if (str_contains($issue, 'Missing H1')) {
            return 'Add a single H1 heading at the beginning of your content';
        } elseif (str_contains($issue, 'Multiple H1')) {
            return 'Convert additional H1 headings to H2 or lower levels';
        } elseif (str_contains($issue, 'Skipped heading level')) {
            return 'Ensure heading levels progress logically (H1 → H2 → H3, etc.)';
        }
        return 'Review and fix heading hierarchy structure';
    }

    private function getAccessibilityFix(string $issue): string
    {
        if (str_contains($issue, 'empty')) {
            return 'Add descriptive text to empty headings or remove them';
        } elseif (str_contains($issue, 'too short')) {
            return 'Expand heading text to be more descriptive (minimum 3 characters)';
        } elseif (str_contains($issue, 'too long')) {
            return 'Shorten heading text to 70 characters or less';
        } elseif (str_contains($issue, 'Non-descriptive')) {
            return 'Make heading text more specific and descriptive';
        }
        return 'Improve heading accessibility compliance';
    }

    private function getSeoFix(string $issue): string
    {
        if (str_contains($issue, 'Missing H1')) {
            return 'Add an H1 heading with your target keyword';
        } elseif (str_contains($issue, 'too deep')) {
            return 'Simplify heading structure, avoid going deeper than H4';
        } elseif (str_contains($issue, 'distribution')) {
            return 'Better distribute headings across different levels';
        }
        return 'Optimize heading structure for SEO';
    }

    private function generateHeadingInsights(array $overallScore, array $hierarchyValidation, array $headings): array
    {
        $insights = [];

        if ($overallScore['overall'] >= 85) {
            $insights[] = 'Excellent heading structure that supports both SEO and accessibility';
        } elseif ($overallScore['overall'] >= 70) {
            $insights[] = 'Good heading structure with room for minor improvements';
        } else {
            $insights[] = 'Heading structure needs significant improvement';
        }

        if ($hierarchyValidation['is_valid']) {
            $insights[] = 'Heading hierarchy follows logical progression';
        } else {
            $insights[] = 'Heading hierarchy has structural issues that affect usability';
        }

        if (count($headings) > 10) {
            $insights[] = 'Rich heading structure indicates well-organized content';
        } elseif (count($headings) < 3) {
            $insights[] = 'Limited heading structure may affect content scannability';
        }

        return $insights;
    }
}