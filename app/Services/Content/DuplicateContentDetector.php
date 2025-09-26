<?php

namespace App\Services\Content;

use Illuminate\Support\Facades\Log;

/**
 * Duplicate content detection service
 * Analyzes content for potential duplication issues including
 * exact matches, near-duplicates, and content similarity patterns
 */
class DuplicateContentDetector
{
    private float $similarityThreshold = 0.85; // 85% similarity threshold
    private int $minChunkSize = 50; // Minimum words for chunk analysis
    private int $chunkSize = 100; // Words per chunk for analysis

    /**
     * Detect duplicate and near-duplicate content
     */
    public function detect(string $textContent, string $html = '', string $url = '', array $options = []): array
    {
        Log::debug('Starting duplicate content detection', [
            'url' => $url,
            'content_length' => strlen($textContent),
            'word_count' => str_word_count($textContent)
        ]);

        $startTime = microtime(true);

        try {
            if (empty($textContent) || str_word_count($textContent) < $this->minChunkSize) {
                return $this->generateInsufficientContentAnalysis($textContent);
            }

            // Internal duplicate detection
            $internalDuplicates = $this->detectInternalDuplicates($textContent);

            // Content fingerprinting for external comparison
            $contentFingerprint = $this->generateContentFingerprint($textContent);

            // Pattern-based duplicate detection
            $patternAnalysis = $this->analyzeContentPatterns($textContent);

            // Boilerplate and template content detection
            $boilerplateAnalysis = $this->detectBoilerplateContent($html, $textContent);

            // Thin content analysis
            $thinContentAnalysis = $this->analyzeThinContent($textContent, $html);

            // Content uniqueness assessment
            $uniquenessAnalysis = $this->assessContentUniqueness($textContent, $internalDuplicates, $patternAnalysis);

            // SEO impact analysis
            $seoImpactAnalysis = $this->analyzeSeoImpact($internalDuplicates, $patternAnalysis, $thinContentAnalysis);

            // Calculate overall duplicate content score
            $overallScore = $this->calculateDuplicateContentScore([
                'internal_duplicates' => $internalDuplicates,
                'content_patterns' => $patternAnalysis,
                'boilerplate' => $boilerplateAnalysis,
                'thin_content' => $thinContentAnalysis,
                'uniqueness' => $uniquenessAnalysis,
                'seo_impact' => $seoImpactAnalysis
            ]);

            // Generate recommendations
            $recommendations = $this->generateDuplicateContentRecommendations([
                'internal_duplicates' => $internalDuplicates,
                'content_patterns' => $patternAnalysis,
                'boilerplate' => $boilerplateAnalysis,
                'thin_content' => $thinContentAnalysis,
                'uniqueness' => $uniquenessAnalysis,
                'seo_impact' => $seoImpactAnalysis,
                'overall_score' => $overallScore
            ]);

            $analysis = [
                'analyzed_at' => date('c'),
                'analysis_duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'overall_score' => $overallScore,
                'content_fingerprint' => $contentFingerprint,
                'word_count' => str_word_count($textContent),
                'character_count' => strlen($textContent),
                'internal_duplicates' => $internalDuplicates,
                'content_patterns' => $patternAnalysis,
                'boilerplate_analysis' => $boilerplateAnalysis,
                'thin_content_analysis' => $thinContentAnalysis,
                'uniqueness_analysis' => $uniquenessAnalysis,
                'seo_impact_analysis' => $seoImpactAnalysis,
                'recommendations' => $recommendations,
                'duplicate_insights' => $this->generateDuplicateInsights($overallScore, $internalDuplicates, $uniquenessAnalysis)
            ];

            Log::info('Duplicate content detection completed', [
                'url' => $url,
                'overall_score' => $overallScore,
                'internal_duplicates_found' => count($internalDuplicates['duplicates']),
                'uniqueness_score' => $uniquenessAnalysis['score']
            ]);

            return $analysis;

        } catch (\Exception $e) {
            Log::error('Duplicate content detection failed', [
                'url' => $url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Detect internal duplicates within the content
     */
    private function detectInternalDuplicates(string $textContent): array
    {
        $words = explode(' ', preg_replace('/\s+/', ' ', $textContent));
        $totalWords = count($words);

        if ($totalWords < $this->chunkSize) {
            return [
                'score' => 100,
                'duplicates' => [],
                'duplicate_percentage' => 0,
                'analysis_method' => 'content_too_short'
            ];
        }

        $chunks = [];
        $duplicates = [];
        $chunkHashes = [];

        // Create overlapping chunks for better detection
        $step = intval($this->chunkSize * 0.5); // 50% overlap

        for ($i = 0; $i <= $totalWords - $this->chunkSize; $i += $step) {
            $chunk = array_slice($words, $i, $this->chunkSize);
            $chunkText = implode(' ', $chunk);
            $chunkHash = $this->generateTextHash($chunkText);

            if (isset($chunkHashes[$chunkHash])) {
                // Found duplicate chunk
                $duplicates[] = [
                    'original_position' => $chunkHashes[$chunkHash],
                    'duplicate_position' => $i,
                    'text' => $chunkText,
                    'word_count' => count($chunk),
                    'similarity' => 1.0, // Exact match
                    'hash' => $chunkHash
                ];
            } else {
                $chunkHashes[$chunkHash] = $i;
            }

            $chunks[] = [
                'position' => $i,
                'text' => $chunkText,
                'hash' => $chunkHash
            ];
        }

        // Also check for near-duplicates using similarity
        $nearDuplicates = $this->findNearDuplicateChunks($chunks);
        $duplicates = array_merge($duplicates, $nearDuplicates);

        $duplicateWords = count($duplicates) * $this->chunkSize;
        $duplicatePercentage = $totalWords > 0 ? ($duplicateWords / $totalWords) * 100 : 0;

        $score = 100 - ($duplicatePercentage * 2); // 2% penalty per 1% duplication

        return [
            'score' => max(0, min(100, round($score, 1))),
            'duplicates' => $duplicates,
            'duplicate_count' => count($duplicates),
            'duplicate_percentage' => round($duplicatePercentage, 2),
            'total_chunks_analyzed' => count($chunks),
            'analysis_method' => 'chunk_based_with_similarity'
        ];
    }

    /**
     * Generate content fingerprint for external comparison
     */
    private function generateContentFingerprint(string $textContent): array
    {
        // Clean and normalize text
        $normalizedText = $this->normalizeText($textContent);

        // Generate multiple types of fingerprints
        $fingerprints = [
            'md5' => md5($normalizedText),
            'sha256' => hash('sha256', $normalizedText),
            'content_hash' => $this->generateContentHash($normalizedText),
            'semantic_hash' => $this->generateSemanticHash($normalizedText),
            'structure_hash' => $this->generateStructureHash($textContent)
        ];

        return [
            'fingerprints' => $fingerprints,
            'content_length' => strlen($normalizedText),
            'word_count' => str_word_count($normalizedText),
            'unique_words' => count(array_unique(explode(' ', $normalizedText))),
            'fingerprint_quality' => $this->assessFingerprintQuality($normalizedText)
        ];
    }

    /**
     * Analyze content patterns that might indicate duplication
     */
    private function analyzeContentPatterns(string $textContent): array
    {
        $patterns = [
            'repeated_phrases' => $this->detectRepeatedPhrases($textContent),
            'boilerplate_patterns' => $this->detectBoilerplatePatterns($textContent),
            'template_patterns' => $this->detectTemplatePatterns($textContent),
            'copy_paste_indicators' => $this->detectCopyPasteIndicators($textContent)
        ];

        $totalIssues = array_sum(array_map('count', $patterns));
        $score = max(0, 100 - ($totalIssues * 5)); // 5 points per pattern issue

        return [
            'score' => round($score, 1),
            'patterns' => $patterns,
            'total_pattern_issues' => $totalIssues,
            'pattern_analysis' => $this->analyzePatternSeverity($patterns),
            'content_authenticity' => $this->assessContentAuthenticity($patterns, $textContent)
        ];
    }

    /**
     * Detect boilerplate and template content
     */
    private function detectBoilerplateContent(string $html, string $textContent): array
    {
        $boilerplateElements = [
            'navigation_text' => $this->extractNavigationText($html),
            'footer_text' => $this->extractFooterText($html),
            'sidebar_text' => $this->extractSidebarText($html),
            'header_text' => $this->extractHeaderText($html)
        ];

        $boilerplateWordCount = 0;
        foreach ($boilerplateElements as $element) {
            $boilerplateWordCount += str_word_count($element);
        }

        $totalWordCount = str_word_count($textContent);
        $boilerplatePercentage = $totalWordCount > 0 ? ($boilerplateWordCount / $totalWordCount) * 100 : 0;

        $score = 100 - ($boilerplatePercentage * 1.5); // 1.5% penalty per 1% boilerplate

        return [
            'score' => max(0, min(100, round($score, 1))),
            'boilerplate_elements' => $boilerplateElements,
            'boilerplate_word_count' => $boilerplateWordCount,
            'total_word_count' => $totalWordCount,
            'boilerplate_percentage' => round($boilerplatePercentage, 2),
            'content_ratio' => $this->calculateContentToBoilerplateRatio($totalWordCount, $boilerplateWordCount)
        ];
    }

    /**
     * Analyze thin content issues
     */
    private function analyzeThinContent(string $textContent, string $html): array
    {
        $wordCount = str_word_count($textContent);
        $characterCount = strlen($textContent);
        $uniqueWords = count(array_unique(explode(' ', strtolower($textContent))));
        $htmlSize = strlen($html);

        $metrics = [
            'word_count' => $wordCount,
            'character_count' => $characterCount,
            'unique_words' => $uniqueWords,
            'html_size' => $htmlSize,
            'text_to_html_ratio' => $htmlSize > 0 ? round(($characterCount / $htmlSize) * 100, 2) : 0,
            'word_diversity' => $wordCount > 0 ? round(($uniqueWords / $wordCount) * 100, 2) : 0
        ];

        $thinContentIssues = [];
        $score = 100;

        // Word count analysis
        if ($wordCount < 150) {
            $score -= 40;
            $thinContentIssues[] = 'Very low word count (under 150 words)';
        } elseif ($wordCount < 300) {
            $score -= 20;
            $thinContentIssues[] = 'Low word count (under 300 words)';
        }

        // Text to HTML ratio analysis
        if ($metrics['text_to_html_ratio'] < 10) {
            $score -= 25;
            $thinContentIssues[] = 'Very low text-to-HTML ratio (under 10%)';
        } elseif ($metrics['text_to_html_ratio'] < 20) {
            $score -= 15;
            $thinContentIssues[] = 'Low text-to-HTML ratio (under 20%)';
        }

        // Word diversity analysis
        if ($metrics['word_diversity'] < 30) {
            $score -= 20;
            $thinContentIssues[] = 'Low word diversity (high repetition)';
        }

        return [
            'score' => max(0, min(100, round($score, 1))),
            'is_thin_content' => $score < 60,
            'metrics' => $metrics,
            'issues' => $thinContentIssues,
            'content_quality_level' => $this->getContentQualityLevel($score),
            'recommendations' => $this->generateThinContentRecommendations($metrics, $thinContentIssues)
        ];
    }

    /**
     * Assess overall content uniqueness
     */
    private function assessContentUniqueness(string $textContent, array $internalDuplicates, array $patternAnalysis): array
    {
        $uniquenessFactors = [
            'internal_duplication' => 100 - $internalDuplicates['duplicate_percentage'],
            'pattern_originality' => $patternAnalysis['score'],
            'content_depth' => $this->assessContentDepth($textContent),
            'vocabulary_richness' => $this->assessVocabularyRichness($textContent),
            'structural_uniqueness' => $this->assessStructuralUniqueness($textContent)
        ];

        $weights = [
            'internal_duplication' => 0.30,
            'pattern_originality' => 0.25,
            'content_depth' => 0.20,
            'vocabulary_richness' => 0.15,
            'structural_uniqueness' => 0.10
        ];

        $overallScore = 0;
        foreach ($uniquenessFactors as $factor => $score) {
            $overallScore += $score * $weights[$factor];
        }

        return [
            'score' => round($overallScore, 1),
            'uniqueness_level' => $this->getUniquenessLevel($overallScore),
            'factors' => $uniquenessFactors,
            'weights' => $weights,
            'uniqueness_insights' => $this->generateUniquenessInsights($uniquenessFactors),
            'improvement_areas' => $this->identifyUniquenessImprovementAreas($uniquenessFactors)
        ];
    }

    /**
     * Analyze SEO impact of duplicate content
     */
    private function analyzeSeoImpact(array $internalDuplicates, array $patternAnalysis, array $thinContentAnalysis): array
    {
        $impactFactors = [];
        $riskLevel = 'low';
        $seoScore = 100;

        // Internal duplication impact
        if ($internalDuplicates['duplicate_percentage'] > 20) {
            $impactFactors[] = 'High internal duplication may confuse search engines';
            $riskLevel = 'high';
            $seoScore -= 30;
        } elseif ($internalDuplicates['duplicate_percentage'] > 10) {
            $impactFactors[] = 'Moderate internal duplication detected';
            $riskLevel = 'medium';
            $seoScore -= 15;
        }

        // Pattern-based issues impact
        if ($patternAnalysis['total_pattern_issues'] > 5) {
            $impactFactors[] = 'Multiple content patterns suggest non-original content';
            $riskLevel = 'high';
            $seoScore -= 25;
        }

        // Thin content impact
        if ($thinContentAnalysis['is_thin_content']) {
            $impactFactors[] = 'Thin content provides little value to users';
            $riskLevel = $riskLevel === 'high' ? 'high' : 'medium';
            $seoScore -= 20;
        }

        return [
            'score' => max(0, min(100, round($seoScore, 1))),
            'risk_level' => $riskLevel,
            'impact_factors' => $impactFactors,
            'seo_recommendations' => $this->generateSeoRecommendations($riskLevel, $impactFactors),
            'ranking_impact' => $this->assessRankingImpact($riskLevel, $seoScore),
            'indexing_concerns' => $this->assessIndexingConcerns($internalDuplicates, $thinContentAnalysis)
        ];
    }

    /**
     * Calculate overall duplicate content score
     */
    private function calculateDuplicateContentScore(array $analyses): array
    {
        $weights = [
            'internal_duplicates' => 0.25,
            'content_patterns' => 0.20,
            'uniqueness' => 0.20,
            'thin_content' => 0.15,
            'boilerplate' => 0.10,
            'seo_impact' => 0.10
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
            'grade' => $this->getDuplicateContentGrade($totalScore),
            'originality_level' => $this->getOriginalityLevel($totalScore)
        ];
    }

    // Helper methods

    private function generateInsufficientContentAnalysis(string $textContent): array
    {
        return [
            'analyzed_at' => date('c'),
            'analysis_duration_ms' => 0,
            'overall_score' => ['overall' => 0, 'grade' => 'Insufficient'],
            'word_count' => str_word_count($textContent),
            'recommendations' => [[
                'type' => 'error',
                'category' => 'content_length',
                'message' => 'Insufficient content for duplicate analysis',
                'impact' => 'high',
                'fix' => 'Add more substantial content (minimum 50 words required for analysis)'
            ]],
            'duplicate_insights' => ['Content too short for meaningful duplicate detection']
        ];
    }

    private function findNearDuplicateChunks(array $chunks): array
    {
        $nearDuplicates = [];

        for ($i = 0; $i < count($chunks); $i++) {
            for ($j = $i + 1; $j < count($chunks); $j++) {
                $similarity = $this->calculateTextSimilarity($chunks[$i]['text'], $chunks[$j]['text']);

                if ($similarity >= $this->similarityThreshold && $similarity < 1.0) {
                    $nearDuplicates[] = [
                        'original_position' => $chunks[$i]['position'],
                        'duplicate_position' => $chunks[$j]['position'],
                        'text' => $chunks[$j]['text'],
                        'word_count' => str_word_count($chunks[$j]['text']),
                        'similarity' => $similarity,
                        'type' => 'near_duplicate'
                    ];
                }
            }
        }

        return $nearDuplicates;
    }

    private function calculateTextSimilarity(string $text1, string $text2): float
    {
        // Simple Jaccard similarity
        $words1 = array_unique(explode(' ', strtolower($text1)));
        $words2 = array_unique(explode(' ', strtolower($text2)));

        $intersection = count(array_intersect($words1, $words2));
        $union = count(array_unique(array_merge($words1, $words2)));

        return $union > 0 ? $intersection / $union : 0;
    }

    private function generateTextHash(string $text): string
    {
        // Normalize text for consistent hashing
        $normalized = strtolower(preg_replace('/[^\w\s]/', '', $text));
        return hash('sha256', $normalized);
    }

    private function normalizeText(string $text): string
    {
        // Remove extra whitespace and normalize
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        return strtolower($text);
    }

    private function generateContentHash(string $text): string
    {
        // Generate hash based on significant words only
        $words = explode(' ', $text);
        $significantWords = array_filter($words, fn($word) => strlen($word) > 3);
        return hash('sha256', implode(' ', $significantWords));
    }

    private function generateSemanticHash(string $text): string
    {
        // Generate hash based on semantic content (simplified)
        $words = explode(' ', $text);
        $nouns = array_filter($words, fn($word) => strlen($word) > 4); // Rough noun approximation
        sort($nouns);
        return hash('sha256', implode('', array_slice($nouns, 0, 20)));
    }

    private function generateStructureHash(string $text): string
    {
        // Generate hash based on content structure
        $sentences = preg_split('/[.!?]+/', $text);
        $structure = array_map('str_word_count', $sentences);
        return hash('sha256', implode(',', $structure));
    }

    private function assessFingerprintQuality(string $text): string
    {
        $wordCount = str_word_count($text);
        $uniqueWords = count(array_unique(explode(' ', $text)));

        if ($wordCount < 100) return 'Low';
        if ($uniqueWords / $wordCount < 0.5) return 'Poor';
        if ($wordCount > 500 && $uniqueWords / $wordCount > 0.7) return 'Excellent';
        return 'Good';
    }

    private function detectRepeatedPhrases(string $text): array
    {
        $phrases = [];
        $words = explode(' ', $text);

        // Check for 3-word phrases
        for ($i = 0; $i <= count($words) - 3; $i++) {
            $phrase = implode(' ', array_slice($words, $i, 3));
            $phrases[] = $phrase;
        }

        $phraseCounts = array_count_values($phrases);
        return array_filter($phraseCounts, fn($count) => $count > 2);
    }

    private function detectBoilerplatePatterns(string $text): array
    {
        $patterns = [];
        $boilerplateIndicators = [
            'copyright', 'all rights reserved', 'terms of service',
            'privacy policy', 'cookie policy', 'disclaimer'
        ];

        foreach ($boilerplateIndicators as $indicator) {
            if (str_contains(strtolower($text), $indicator)) {
                $patterns[] = $indicator;
            }
        }

        return $patterns;
    }

    private function detectTemplatePatterns(string $text): array
    {
        $patterns = [];
        $templateIndicators = [
            '/\[.*?\]/', // Square brackets
            '/\{.*?\}/', // Curly braces
            '/\$\w+/', // Variable patterns
            '/lorem ipsum/i' // Lorem ipsum text
        ];

        foreach ($templateIndicators as $pattern) {
            if (preg_match($pattern, $text)) {
                $patterns[] = $pattern;
            }
        }

        return $patterns;
    }

    private function detectCopyPasteIndicators(string $text): array
    {
        $indicators = [];

        // Check for inconsistent formatting
        if (preg_match('/\s{4,}/', $text)) {
            $indicators[] = 'Inconsistent spacing detected';
        }

        // Check for mixed quote styles
        if (str_contains($text, '"') && str_contains($text, '"')) {
            $indicators[] = 'Mixed quote styles detected';
        }

        // Check for unusual line breaks
        if (preg_match('/\n{3,}/', $text)) {
            $indicators[] = 'Unusual line break patterns';
        }

        return $indicators;
    }

    private function analyzePatternSeverity(array $patterns): array
    {
        $totalPatterns = array_sum(array_map('count', $patterns));

        if ($totalPatterns > 10) {
            $severity = 'high';
        } elseif ($totalPatterns > 5) {
            $severity = 'medium';
        } elseif ($totalPatterns > 0) {
            $severity = 'low';
        } else {
            $severity = 'none';
        }

        return [
            'severity_level' => $severity,
            'total_patterns' => $totalPatterns,
            'pattern_distribution' => array_map('count', $patterns)
        ];
    }

    private function assessContentAuthenticity(array $patterns, string $text): string
    {
        $totalIssues = array_sum(array_map('count', $patterns));
        $wordCount = str_word_count($text);

        $issueRatio = $wordCount > 0 ? $totalIssues / ($wordCount / 100) : 0;

        if ($issueRatio > 5) return 'Questionable';
        if ($issueRatio > 2) return 'Potentially Unoriginal';
        if ($issueRatio > 0.5) return 'Mostly Original';
        return 'Highly Original';
    }

    private function extractNavigationText(string $html): string
    {
        preg_match_all('/<nav[^>]*>(.*?)<\/nav>/is', $html, $matches);
        return implode(' ', array_map('strip_tags', $matches[1] ?? []));
    }

    private function extractFooterText(string $html): string
    {
        preg_match_all('/<footer[^>]*>(.*?)<\/footer>/is', $html, $matches);
        return implode(' ', array_map('strip_tags', $matches[1] ?? []));
    }

    private function extractSidebarText(string $html): string
    {
        preg_match_all('/<aside[^>]*>(.*?)<\/aside>|<div[^>]*class="[^"]*sidebar[^"]*"[^>]*>(.*?)<\/div>/is', $html, $matches);
        return implode(' ', array_map('strip_tags', array_merge($matches[1] ?? [], $matches[2] ?? [])));
    }

    private function extractHeaderText(string $html): string
    {
        preg_match_all('/<header[^>]*>(.*?)<\/header>/is', $html, $matches);
        return implode(' ', array_map('strip_tags', $matches[1] ?? []));
    }

    private function calculateContentToBoilerplateRatio(int $totalWords, int $boilerplateWords): array
    {
        $contentWords = $totalWords - $boilerplateWords;
        $ratio = $boilerplateWords > 0 ? $contentWords / $boilerplateWords : ($contentWords > 0 ? 99 : 0);

        return [
            'content_words' => $contentWords,
            'boilerplate_words' => $boilerplateWords,
            'ratio' => round($ratio, 2),
            'quality_level' => $this->getContentBoilerplateQuality($ratio)
        ];
    }

    private function getContentBoilerplateQuality(float $ratio): string
    {
        if ($ratio > 10) return 'Excellent';
        if ($ratio > 5) return 'Good';
        if ($ratio > 2) return 'Fair';
        if ($ratio > 1) return 'Poor';
        return 'Very Poor';
    }

    private function getContentQualityLevel(float $score): string
    {
        if ($score >= 85) return 'High Quality';
        if ($score >= 70) return 'Good Quality';
        if ($score >= 55) return 'Fair Quality';
        if ($score >= 40) return 'Poor Quality';
        return 'Very Poor Quality';
    }

    private function generateThinContentRecommendations(array $metrics, array $issues): array
    {
        $recommendations = [];

        if ($metrics['word_count'] < 300) {
            $recommendations[] = 'Expand content to at least 300 words for better SEO value';
        }

        if ($metrics['text_to_html_ratio'] < 20) {
            $recommendations[] = 'Reduce HTML markup or add more text content to improve text-to-HTML ratio';
        }

        if ($metrics['word_diversity'] < 40) {
            $recommendations[] = 'Use more varied vocabulary to reduce repetition';
        }

        return $recommendations;
    }

    private function assessContentDepth(string $text): float
    {
        $wordCount = str_word_count($text);
        $sentenceCount = preg_match_all('/[.!?]+/', $text);
        $avgWordsPerSentence = $sentenceCount > 0 ? $wordCount / $sentenceCount : 0;

        $score = 50; // Base score

        // Word count factor
        if ($wordCount > 1000) $score += 25;
        elseif ($wordCount > 500) $score += 15;
        elseif ($wordCount > 300) $score += 10;

        // Sentence complexity factor
        if ($avgWordsPerSentence > 15 && $avgWordsPerSentence < 25) $score += 15;
        elseif ($avgWordsPerSentence > 25) $score -= 10;

        // Technical terms (approximation)
        $technicalTerms = preg_match_all('/\b\w{8,}\b/', $text);
        if ($technicalTerms > $wordCount * 0.1) $score += 10;

        return max(0, min(100, $score));
    }

    private function assessVocabularyRichness(string $text): float
    {
        $words = explode(' ', strtolower($text));
        $uniqueWords = array_unique($words);
        $totalWords = count($words);

        if ($totalWords === 0) return 0;

        $diversityRatio = count($uniqueWords) / $totalWords;
        return min(100, $diversityRatio * 150); // Scale to 0-100
    }

    private function assessStructuralUniqueness(string $text): float
    {
        $sentences = preg_split('/[.!?]+/', $text);
        $sentenceLengths = array_map('str_word_count', array_filter($sentences));

        if (empty($sentenceLengths)) return 50;

        $avgLength = array_sum($sentenceLengths) / count($sentenceLengths);
        $variance = 0;

        foreach ($sentenceLengths as $length) {
            $variance += pow($length - $avgLength, 2);
        }

        $variance /= count($sentenceLengths);
        $stdDev = sqrt($variance);

        // Higher variance indicates more structural variety
        return min(100, ($stdDev / $avgLength) * 200);
    }

    private function getUniquenessLevel(float $score): string
    {
        if ($score >= 90) return 'Highly Unique';
        if ($score >= 75) return 'Very Unique';
        if ($score >= 60) return 'Moderately Unique';
        if ($score >= 40) return 'Somewhat Unique';
        if ($score >= 25) return 'Low Uniqueness';
        return 'Poor Uniqueness';
    }

    private function generateUniquenessInsights(array $factors): array
    {
        $insights = [];

        foreach ($factors as $factor => $score) {
            if ($score < 50) {
                $insights[] = ucfirst(str_replace('_', ' ', $factor)) . ' needs improvement';
            } elseif ($score > 80) {
                $insights[] = ucfirst(str_replace('_', ' ', $factor)) . ' is strong';
            }
        }

        return $insights;
    }

    private function identifyUniquenessImprovementAreas(array $factors): array
    {
        $improvements = [];

        arsort($factors); // Sort by score descending
        $weakestAreas = array_slice(array_keys($factors), -2, 2, true);

        foreach ($weakestAreas as $area) {
            $improvements[] = [
                'area' => $area,
                'score' => $factors[$area],
                'recommendation' => $this->getUniquenessRecommendation($area)
            ];
        }

        return $improvements;
    }

    private function getUniquenessRecommendation(string $area): string
    {
        return match($area) {
            'internal_duplication' => 'Reduce repetitive content within the page',
            'pattern_originality' => 'Create more original content patterns and avoid templates',
            'content_depth' => 'Add more detailed explanations and examples',
            'vocabulary_richness' => 'Use more varied vocabulary and synonyms',
            'structural_uniqueness' => 'Vary sentence length and structure for better flow',
            default => 'Improve content originality in this area'
        };
    }

    private function generateSeoRecommendations(string $riskLevel, array $impactFactors): array
    {
        $recommendations = [];

        if ($riskLevel === 'high') {
            $recommendations[] = 'Immediate action required - rewrite duplicate content sections';
            $recommendations[] = 'Consider using canonical tags if content must remain similar';
        } elseif ($riskLevel === 'medium') {
            $recommendations[] = 'Review and improve content originality';
            $recommendations[] = 'Add unique value propositions to differentiate content';
        } else {
            $recommendations[] = 'Continue creating original, valuable content';
        }

        return $recommendations;
    }

    private function assessRankingImpact(string $riskLevel, float $seoScore): string
    {
        if ($riskLevel === 'high' || $seoScore < 40) {
            return 'High negative impact - may significantly hurt rankings';
        } elseif ($riskLevel === 'medium' || $seoScore < 70) {
            return 'Medium impact - may affect ranking potential';
        } else {
            return 'Low impact - minimal effect on rankings expected';
        }
    }

    private function assessIndexingConcerns(array $internalDuplicates, array $thinContentAnalysis): array
    {
        $concerns = [];

        if ($internalDuplicates['duplicate_percentage'] > 30) {
            $concerns[] = 'High duplication may confuse search engine crawlers';
        }

        if ($thinContentAnalysis['is_thin_content']) {
            $concerns[] = 'Thin content may be devalued or not indexed';
        }

        return [
            'has_concerns' => !empty($concerns),
            'concerns' => $concerns,
            'indexing_likelihood' => $this->calculateIndexingLikelihood($internalDuplicates, $thinContentAnalysis)
        ];
    }

    private function calculateIndexingLikelihood(array $internalDuplicates, array $thinContentAnalysis): string
    {
        $score = 100;

        $score -= $internalDuplicates['duplicate_percentage'] * 2;
        if ($thinContentAnalysis['is_thin_content']) $score -= 30;

        if ($score >= 80) return 'High';
        if ($score >= 60) return 'Medium';
        if ($score >= 40) return 'Low';
        return 'Very Low';
    }

    private function getDuplicateContentGrade(float $score): string
    {
        if ($score >= 90) return 'A+ (Highly Original)';
        if ($score >= 85) return 'A (Very Original)';
        if ($score >= 80) return 'A- (Original)';
        if ($score >= 75) return 'B+ (Mostly Original)';
        if ($score >= 70) return 'B (Good Originality)';
        if ($score >= 65) return 'B- (Fair Originality)';
        if ($score >= 60) return 'C+ (Some Duplication)';
        if ($score >= 55) return 'C (Moderate Duplication)';
        if ($score >= 50) return 'C- (Concerning Duplication)';
        return 'D or lower (High Duplication)';
    }

    private function getOriginalityLevel(float $score): string
    {
        if ($score >= 85) return 'Highly Original';
        if ($score >= 70) return 'Very Original';
        if ($score >= 55) return 'Mostly Original';
        if ($score >= 40) return 'Somewhat Original';
        if ($score >= 25) return 'Limited Originality';
        return 'Poor Originality';
    }

    private function generateDuplicateContentRecommendations(array $analyses): array
    {
        $recommendations = [];

        // Internal duplicates
        $internalDuplicates = $analyses['internal_duplicates'];
        if ($internalDuplicates['duplicate_percentage'] > 10) {
            $recommendations[] = [
                'type' => 'error',
                'category' => 'duplicate_content',
                'message' => "High internal duplication detected ({$internalDuplicates['duplicate_percentage']}%)",
                'impact' => 'high',
                'fix' => 'Rewrite duplicated sections to provide unique value and information'
            ];
        }

        // Thin content
        $thinContent = $analyses['thin_content'];
        if ($thinContent['is_thin_content']) {
            $recommendations[] = [
                'type' => 'warning',
                'category' => 'content_quality',
                'message' => 'Content appears to be thin and may lack sufficient value',
                'impact' => 'medium',
                'fix' => 'Expand content with more detailed information, examples, and unique insights'
            ];
        }

        // Pattern issues
        $patterns = $analyses['content_patterns'];
        if ($patterns['total_pattern_issues'] > 5) {
            $recommendations[] = [
                'type' => 'warning',
                'category' => 'content_originality',
                'message' => 'Multiple patterns suggest content may not be original',
                'impact' => 'medium',
                'fix' => 'Review content sources and ensure all content is original or properly attributed'
            ];
        }

        // Boilerplate content
        $boilerplate = $analyses['boilerplate'];
        if ($boilerplate['boilerplate_percentage'] > 40) {
            $recommendations[] = [
                'type' => 'suggestion',
                'category' => 'content_ratio',
                'message' => 'High percentage of boilerplate content detected',
                'impact' => 'low',
                'fix' => 'Increase unique content or reduce repetitive boilerplate elements'
            ];
        }

        return $recommendations;
    }

    private function generateDuplicateInsights(array $overallScore, array $internalDuplicates, array $uniquenessAnalysis): array
    {
        $insights = [];

        if ($overallScore['overall'] >= 85) {
            $insights[] = 'Content demonstrates high originality with minimal duplication concerns';
        } elseif ($overallScore['overall'] >= 70) {
            $insights[] = 'Content is mostly original with some areas for improvement';
        } else {
            $insights[] = 'Content has significant duplication or originality issues that need attention';
        }

        if ($internalDuplicates['duplicate_count'] > 0) {
            $insights[] = "Found {$internalDuplicates['duplicate_count']} instances of internal content duplication";
        } else {
            $insights[] = 'No internal content duplication detected';
        }

        if ($uniquenessAnalysis['score'] < 60) {
            $insights[] = 'Content uniqueness score indicates potential originality concerns';
        }

        return $insights;
    }
}