<?php

namespace App\Services\Content;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Keyword density analysis service with NLP basics
 * Analyzes keyword distribution, provides density recommendations,
 * and identifies potential keyword stuffing issues
 */
class KeywordDensityAnalyzer
{
    private array $stopWords = [
        'a', 'an', 'and', 'are', 'as', 'at', 'be', 'by', 'for', 'from',
        'has', 'he', 'in', 'is', 'it', 'its', 'of', 'on', 'that', 'the',
        'to', 'was', 'were', 'will', 'with', 'would', 'you', 'your',
        'this', 'they', 'them', 'their', 'we', 'us', 'our', 'ours',
        'me', 'my', 'mine', 'him', 'his', 'her', 'hers', 'she', 'but',
        'or', 'if', 'because', 'as', 'until', 'while', 'so', 'than',
        'too', 'very', 'can', 'will', 'just', 'should', 'now'
    ];

    /**
     * Analyze keyword density and distribution
     */
    public function analyze(string $textContent, array $parsedData = [], array $options = []): array
    {
        Log::debug('Starting keyword density analysis', [
            'text_length' => strlen($textContent),
            'word_count' => str_word_count($textContent)
        ]);

        $startTime = microtime(true);

        try {
            // Extract words and clean text
            $words = $this->extractWords($textContent);
            $totalWords = count($words);

            // Analyze different keyword types
            $singleWordAnalysis = $this->analyzeSingleWords($words);
            $twoWordPhrases = $this->analyzeTwoWordPhrases($words);
            $threeWordPhrases = $this->analyzeThreeWordPhrases($words);

            // Analyze title and heading keywords
            $titleKeywords = $this->analyzeTitleKeywords($parsedData['meta']['title'] ?? '', $words);
            $headingKeywords = $this->analyzeHeadingKeywords($parsedData['headings'] ?? [], $words);

            // Calculate keyword density scores
            $densityScore = $this->calculateDensityScore($singleWordAnalysis, $twoWordPhrases, $threeWordPhrases);

            // Check for keyword stuffing
            $stuffingAnalysis = $this->detectKeywordStuffing($singleWordAnalysis, $twoWordPhrases, $totalWords);

            // Generate keyword recommendations
            $recommendations = $this->generateKeywordRecommendations(
                $singleWordAnalysis,
                $twoWordPhrases,
                $threeWordPhrases,
                $titleKeywords,
                $headingKeywords,
                $stuffingAnalysis,
                $totalWords
            );

            $analysis = [
                'analyzed_at' => date('c'),
                'analysis_duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'overall_score' => $densityScore,
                'total_words' => $totalWords,
                'unique_words' => count($singleWordAnalysis),
                'single_words' => [
                    'top_keywords' => array_slice($singleWordAnalysis, 0, 20),
                    'total_analyzed' => count($singleWordAnalysis)
                ],
                'two_word_phrases' => [
                    'top_phrases' => array_slice($twoWordPhrases, 0, 15),
                    'total_analyzed' => count($twoWordPhrases)
                ],
                'three_word_phrases' => [
                    'top_phrases' => array_slice($threeWordPhrases, 0, 10),
                    'total_analyzed' => count($threeWordPhrases)
                ],
                'title_keywords' => $titleKeywords,
                'heading_keywords' => $headingKeywords,
                'keyword_stuffing' => $stuffingAnalysis,
                'keyword_distribution' => $this->analyzeKeywordDistribution($textContent, $singleWordAnalysis),
                'semantic_analysis' => $this->performSemanticAnalysis($singleWordAnalysis, $twoWordPhrases),
                'recommendations' => $recommendations,
                'density_insights' => $this->generateDensityInsights($densityScore, $stuffingAnalysis, $totalWords)
            ];

            Log::info('Keyword density analysis completed', [
                'total_words' => $totalWords,
                'unique_words' => count($singleWordAnalysis),
                'density_score' => $densityScore,
                'stuffing_detected' => $stuffingAnalysis['has_stuffing']
            ]);

            return $analysis;

        } catch (\Exception $e) {
            Log::error('Keyword density analysis failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Extract and clean words from text
     */
    private function extractWords(string $text): array
    {
        // Convert to lowercase and remove special characters
        $text = strtolower($text);
        $text = preg_replace('/[^\w\s]/u', ' ', $text);

        // Split into words
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        // Filter out stop words and short words
        $filteredWords = array_filter($words, function($word) {
            return strlen($word) >= 2 && !in_array($word, $this->stopWords);
        });

        return array_values($filteredWords);
    }

    /**
     * Analyze single word frequency
     */
    private function analyzeSingleWords(array $words): array
    {
        $wordCounts = array_count_values($words);
        $totalWords = count($words);

        $analysis = [];
        foreach ($wordCounts as $word => $count) {
            if (strlen($word) >= 3) { // Focus on meaningful words
                $density = ($count / $totalWords) * 100;
                $analysis[$word] = [
                    'word' => $word,
                    'count' => $count,
                    'density' => round($density, 2),
                    'optimal_range' => $this->getOptimalDensityRange('single'),
                    'status' => $this->getDensityStatus($density, 'single')
                ];
            }
        }

        // Sort by count descending
        uasort($analysis, fn($a, $b) => $b['count'] - $a['count']);

        return $analysis;
    }

    /**
     * Analyze two-word phrase frequency
     */
    private function analyzeTwoWordPhrases(array $words): array
    {
        $phrases = [];
        $totalWords = count($words);

        for ($i = 0; $i < count($words) - 1; $i++) {
            $phrase = $words[$i] . ' ' . $words[$i + 1];
            $phrases[] = $phrase;
        }

        $phraseCounts = array_count_values($phrases);
        $analysis = [];

        foreach ($phraseCounts as $phrase => $count) {
            if ($count >= 2) { // Only include phrases that appear at least twice
                $density = ($count / $totalWords) * 100;
                $analysis[$phrase] = [
                    'phrase' => $phrase,
                    'count' => $count,
                    'density' => round($density, 2),
                    'optimal_range' => $this->getOptimalDensityRange('phrase'),
                    'status' => $this->getDensityStatus($density, 'phrase')
                ];
            }
        }

        // Sort by count descending
        uasort($analysis, fn($a, $b) => $b['count'] - $a['count']);

        return $analysis;
    }

    /**
     * Analyze three-word phrase frequency
     */
    private function analyzeThreeWordPhrases(array $words): array
    {
        $phrases = [];
        $totalWords = count($words);

        for ($i = 0; $i < count($words) - 2; $i++) {
            $phrase = $words[$i] . ' ' . $words[$i + 1] . ' ' . $words[$i + 2];
            $phrases[] = $phrase;
        }

        $phraseCounts = array_count_values($phrases);
        $analysis = [];

        foreach ($phraseCounts as $phrase => $count) {
            if ($count >= 2) { // Only include phrases that appear at least twice
                $density = ($count / $totalWords) * 100;
                $analysis[$phrase] = [
                    'phrase' => $phrase,
                    'count' => $count,
                    'density' => round($density, 2),
                    'optimal_range' => $this->getOptimalDensityRange('long_phrase'),
                    'status' => $this->getDensityStatus($density, 'long_phrase')
                ];
            }
        }

        // Sort by count descending
        uasort($analysis, fn($a, $b) => $b['count'] - $a['count']);

        return $analysis;
    }

    /**
     * Analyze keywords in title
     */
    private function analyzeTitleKeywords(string $title, array $contentWords): array
    {
        if (empty($title)) {
            return ['analysis' => [], 'score' => 0, 'issues' => ['Missing title']];
        }

        $titleWords = $this->extractWords($title);
        $titleKeywords = [];
        $score = 0;

        foreach ($titleWords as $word) {
            $contentCount = array_count_values($contentWords)[$word] ?? 0;
            $titleKeywords[$word] = [
                'word' => $word,
                'in_content' => $contentCount > 0,
                'content_frequency' => $contentCount,
                'relevance_score' => $this->calculateRelevanceScore($word, $contentCount, count($contentWords))
            ];

            if ($contentCount > 0) {
                $score += 10; // Bonus for title keywords appearing in content
            }
        }

        return [
            'analysis' => $titleKeywords,
            'score' => min(100, $score),
            'keywords_in_content' => count(array_filter($titleKeywords, fn($k) => $k['in_content'])),
            'total_title_keywords' => count($titleKeywords),
            'issues' => $this->identifyTitleKeywordIssues($titleKeywords)
        ];
    }

    /**
     * Analyze keywords in headings
     */
    private function analyzeHeadingKeywords(array $headings, array $contentWords): array
    {
        $headingKeywords = [];
        $totalScore = 0;

        foreach ($headings as $level => $headingData) {
            $levelKeywords = [];
            foreach ($headingData as $heading) {
                $headingWords = $this->extractWords($heading['text']);
                foreach ($headingWords as $word) {
                    $contentCount = array_count_values($contentWords)[$word] ?? 0;
                    $levelKeywords[$word] = [
                        'word' => $word,
                        'heading_level' => $level,
                        'in_content' => $contentCount > 0,
                        'content_frequency' => $contentCount,
                        'weight' => $this->getHeadingWeight($level)
                    ];

                    if ($contentCount > 0) {
                        $totalScore += $this->getHeadingWeight($level);
                    }
                }
            }
            $headingKeywords[$level] = $levelKeywords;
        }

        return [
            'analysis' => $headingKeywords,
            'score' => min(100, $totalScore),
            'total_heading_keywords' => array_sum(array_map('count', $headingKeywords)),
            'keywords_in_content_count' => $this->countHeadingKeywordsInContent($headingKeywords),
            'distribution' => $this->analyzeHeadingKeywordDistribution($headingKeywords)
        ];
    }

    /**
     * Calculate overall keyword density score
     */
    private function calculateDensityScore(array $singleWords, array $twoWordPhrases, array $threeWordPhrases): float
    {
        $score = 100; // Start with perfect score

        // Check for over-optimization (keyword stuffing)
        $overOptimized = 0;
        foreach ($singleWords as $analysis) {
            if ($analysis['status'] === 'over_optimized') {
                $overOptimized++;
            }
        }

        foreach ($twoWordPhrases as $analysis) {
            if ($analysis['status'] === 'over_optimized') {
                $overOptimized++;
            }
        }

        // Penalize for over-optimization
        $score -= $overOptimized * 10;

        // Check for under-optimization (too many low-density keywords)
        $underOptimized = 0;
        foreach (array_slice($singleWords, 0, 10) as $analysis) {
            if ($analysis['status'] === 'under_optimized') {
                $underOptimized++;
            }
        }

        // Minor penalty for under-optimization
        $score -= $underOptimized * 3;

        // Bonus for good phrase usage
        $goodPhrases = count(array_filter($twoWordPhrases, fn($p) => $p['status'] === 'optimal'));
        $score += min(20, $goodPhrases * 2);

        return max(0, min(100, round($score, 1)));
    }

    /**
     * Detect keyword stuffing patterns
     */
    private function detectKeywordStuffing(array $singleWords, array $phrases, int $totalWords): array
    {
        $stuffingIndicators = [];
        $hasStuffing = false;

        // Check single words for excessive density
        foreach (array_slice($singleWords, 0, 10) as $word => $analysis) {
            if ($analysis['density'] > 5) { // More than 5% density is suspicious
                $stuffingIndicators[] = [
                    'type' => 'single_word',
                    'keyword' => $word,
                    'density' => $analysis['density'],
                    'count' => $analysis['count'],
                    'severity' => $this->getStuffingSeverity($analysis['density'])
                ];
                $hasStuffing = true;
            }
        }

        // Check phrases for excessive repetition
        foreach (array_slice($phrases, 0, 5) as $phrase => $analysis) {
            if ($analysis['density'] > 2) { // More than 2% density for phrases is suspicious
                $stuffingIndicators[] = [
                    'type' => 'phrase',
                    'keyword' => $phrase,
                    'density' => $analysis['density'],
                    'count' => $analysis['count'],
                    'severity' => $this->getStuffingSeverity($analysis['density'])
                ];
                $hasStuffing = true;
            }
        }

        // Check for keyword concentration (many keywords in small text)
        $highDensityWords = count(array_filter($singleWords, fn($a) => $a['density'] > 3));
        if ($highDensityWords > 5 && $totalWords < 500) {
            $stuffingIndicators[] = [
                'type' => 'concentration',
                'issue' => 'Multiple high-density keywords in short content',
                'high_density_count' => $highDensityWords,
                'severity' => 'high'
            ];
            $hasStuffing = true;
        }

        return [
            'has_stuffing' => $hasStuffing,
            'indicators' => $stuffingIndicators,
            'severity_level' => $this->getOverallStuffingSeverity($stuffingIndicators),
            'stuffing_score' => $this->calculateStuffingScore($stuffingIndicators)
        ];
    }

    /**
     * Analyze keyword distribution throughout content
     */
    private function analyzeKeywordDistribution(string $textContent, array $keywords): array
    {
        // Split content into sections (rough estimate)
        $words = explode(' ', $textContent);
        $sectionSize = max(100, count($words) / 5); // 5 sections
        $sections = array_chunk($words, (int)$sectionSize);

        $distribution = [];
        $topKeywords = array_slice($keywords, 0, 5, true);

        foreach ($topKeywords as $keyword => $data) {
            $sectionCounts = [];
            foreach ($sections as $index => $section) {
                $sectionText = strtolower(implode(' ', $section));
                $count = substr_count($sectionText, $keyword);
                $sectionCounts[] = $count;
            }

            $distribution[$keyword] = [
                'total_count' => $data['count'],
                'section_distribution' => $sectionCounts,
                'distribution_score' => $this->calculateDistributionScore($sectionCounts),
                'is_well_distributed' => $this->isWellDistributed($sectionCounts)
            ];
        }

        return $distribution;
    }

    /**
     * Perform basic semantic analysis
     */
    private function performSemanticAnalysis(array $singleWords, array $phrases): array
    {
        $topWords = array_slice($singleWords, 0, 10, true);
        $topPhrases = array_slice($phrases, 0, 5, true);

        // Group semantically related words (basic implementation)
        $semanticGroups = $this->groupSemanticKeywords($topWords);

        // Analyze topic focus
        $topicFocus = $this->analyzeTopicFocus($topWords, $topPhrases);

        return [
            'semantic_groups' => $semanticGroups,
            'topic_focus' => $topicFocus,
            'keyword_diversity' => $this->calculateKeywordDiversity($topWords),
            'semantic_coherence' => $this->calculateSemanticCoherence($semanticGroups),
            'suggested_keywords' => $this->suggestRelatedKeywords($topWords)
        ];
    }

    /**
     * Generate keyword recommendations
     */
    private function generateKeywordRecommendations(
        array $singleWords,
        array $phrases,
        array $longPhrases,
        array $titleKeywords,
        array $headingKeywords,
        array $stuffingAnalysis,
        int $totalWords
    ): array {
        $recommendations = [];

        // Check for keyword stuffing
        if ($stuffingAnalysis['has_stuffing']) {
            foreach ($stuffingAnalysis['indicators'] as $indicator) {
                $recommendations[] = [
                    'type' => 'error',
                    'category' => 'keyword_density',
                    'message' => "Potential keyword stuffing detected for '{$indicator['keyword']}'",
                    'impact' => 'high',
                    'fix' => "Reduce usage of '{$indicator['keyword']}' (current density: {$indicator['density']}%)",
                    'current_density' => $indicator['density'],
                    'recommended_density' => '< 3%'
                ];
            }
        }

        // Check for missing primary keywords in title
        if ($titleKeywords['score'] < 50) {
            $recommendations[] = [
                'type' => 'warning',
                'category' => 'keyword_optimization',
                'message' => 'Title keywords not well represented in content',
                'impact' => 'medium',
                'fix' => 'Ensure primary keywords from title appear naturally throughout the content'
            ];
        }

        // Check for under-optimized primary keywords
        $topKeywords = array_slice($singleWords, 0, 3, true);
        foreach ($topKeywords as $keyword => $data) {
            if ($data['density'] < 1 && $totalWords > 300) {
                $recommendations[] = [
                    'type' => 'suggestion',
                    'category' => 'keyword_density',
                    'message' => "Consider increasing density of primary keyword '{$keyword}'",
                    'impact' => 'medium',
                    'fix' => "Current density: {$data['density']}%. Consider targeting 1-3% for primary keywords",
                    'current_density' => $data['density'],
                    'recommended_density' => '1-3%'
                ];
            }
        }

        // Check for lack of long-tail phrases
        if (count($longPhrases) < 3 && $totalWords > 500) {
            $recommendations[] = [
                'type' => 'suggestion',
                'category' => 'keyword_strategy',
                'message' => 'Limited use of long-tail keyword phrases',
                'impact' => 'medium',
                'fix' => 'Include more specific 3+ word phrases to target long-tail searches'
            ];
        }

        // Check content length vs keyword targeting
        if ($totalWords < 300 && count($singleWords) > 20) {
            $recommendations[] = [
                'type' => 'warning',
                'category' => 'content_strategy',
                'message' => 'Too many keywords for short content',
                'impact' => 'medium',
                'fix' => 'Focus on fewer primary keywords or expand content length'
            ];
        }

        return $recommendations;
    }

    // Helper methods

    private function getOptimalDensityRange(string $type): string
    {
        return match($type) {
            'single' => '1-3%',
            'phrase' => '0.5-2%',
            'long_phrase' => '0.1-1%',
            default => '1-3%'
        };
    }

    private function getDensityStatus(float $density, string $type): string
    {
        $thresholds = [
            'single' => ['optimal' => [1, 3], 'over' => 5],
            'phrase' => ['optimal' => [0.5, 2], 'over' => 3],
            'long_phrase' => ['optimal' => [0.1, 1], 'over' => 2]
        ];

        $config = $thresholds[$type] ?? $thresholds['single'];

        if ($density > $config['over']) {
            return 'over_optimized';
        } elseif ($density >= $config['optimal'][0] && $density <= $config['optimal'][1]) {
            return 'optimal';
        } else {
            return 'under_optimized';
        }
    }

    private function calculateRelevanceScore(string $word, int $contentCount, int $totalWords): float
    {
        if ($contentCount === 0) return 0;
        $density = ($contentCount / $totalWords) * 100;
        return min(100, $density * 20); // Scale density to relevance score
    }

    private function getHeadingWeight(string $level): int
    {
        return match($level) {
            'h1' => 10,
            'h2' => 8,
            'h3' => 6,
            'h4' => 4,
            'h5' => 3,
            'h6' => 2,
            default => 1
        };
    }

    private function identifyTitleKeywordIssues(array $titleKeywords): array
    {
        $issues = [];
        $inContentCount = count(array_filter($titleKeywords, fn($k) => $k['in_content']));

        if ($inContentCount === 0) {
            $issues[] = 'No title keywords found in content';
        } elseif ($inContentCount < count($titleKeywords) / 2) {
            $issues[] = 'Less than half of title keywords appear in content';
        }

        return $issues;
    }

    private function countHeadingKeywordsInContent(array $headingKeywords): int
    {
        $count = 0;
        foreach ($headingKeywords as $level => $keywords) {
            $count += count(array_filter($keywords, fn($k) => $k['in_content']));
        }
        return $count;
    }

    private function analyzeHeadingKeywordDistribution(array $headingKeywords): array
    {
        $distribution = [];
        foreach ($headingKeywords as $level => $keywords) {
            $distribution[$level] = count($keywords);
        }
        return $distribution;
    }

    private function getStuffingSeverity(float $density): string
    {
        if ($density > 10) return 'critical';
        if ($density > 7) return 'high';
        if ($density > 5) return 'medium';
        return 'low';
    }

    private function getOverallStuffingSeverity(array $indicators): string
    {
        if (empty($indicators)) return 'none';

        $severities = array_column($indicators, 'severity');
        if (in_array('critical', $severities)) return 'critical';
        if (in_array('high', $severities)) return 'high';
        if (in_array('medium', $severities)) return 'medium';
        return 'low';
    }

    private function calculateStuffingScore(array $indicators): float
    {
        if (empty($indicators)) return 100;

        $penalty = 0;
        foreach ($indicators as $indicator) {
            $penalty += match($indicator['severity']) {
                'critical' => 30,
                'high' => 20,
                'medium' => 10,
                'low' => 5,
                default => 5
            };
        }

        return max(0, 100 - $penalty);
    }

    private function calculateDistributionScore(array $sectionCounts): float
    {
        if (empty($sectionCounts) || array_sum($sectionCounts) === 0) return 0;

        $average = array_sum($sectionCounts) / count($sectionCounts);
        $variance = 0;

        foreach ($sectionCounts as $count) {
            $variance += pow($count - $average, 2);
        }

        $variance /= count($sectionCounts);
        $standardDeviation = sqrt($variance);

        // Lower standard deviation means better distribution
        $maxStdDev = $average; // Worst case: all keywords in one section
        return $maxStdDev > 0 ? max(0, 100 - (($standardDeviation / $maxStdDev) * 100)) : 100;
    }

    private function isWellDistributed(array $sectionCounts): bool
    {
        $score = $this->calculateDistributionScore($sectionCounts);
        return $score >= 70; // 70% or higher distribution score
    }

    private function groupSemanticKeywords(array $keywords): array
    {
        // Basic semantic grouping - in a real implementation, this would use NLP libraries
        $groups = [];
        $grouped = [];

        foreach ($keywords as $word => $data) {
            if (isset($grouped[$word])) continue;

            $group = [$word];
            $grouped[$word] = true;

            // Find similar words (basic string similarity)
            foreach ($keywords as $otherWord => $otherData) {
                if ($word !== $otherWord && !isset($grouped[$otherWord])) {
                    if ($this->areSemanticallySimilar($word, $otherWord)) {
                        $group[] = $otherWord;
                        $grouped[$otherWord] = true;
                    }
                }
            }

            if (count($group) > 1) {
                $groups[] = $group;
            }
        }

        return $groups;
    }

    private function areSemanticallySimilar(string $word1, string $word2): bool
    {
        // Basic similarity check - could be enhanced with stemming/lemmatization
        return similar_text($word1, $word2) / max(strlen($word1), strlen($word2)) > 0.6;
    }

    private function analyzeTopicFocus(array $topWords, array $topPhrases): array
    {
        // Calculate topic coherence based on keyword relationships
        $topicScore = 75; // Base score

        if (count($topWords) > 10) {
            $topicScore -= 10; // Too many different topics
        }

        $phraseCount = count($topPhrases);
        if ($phraseCount > 0) {
            $topicScore += min(20, $phraseCount * 4); // Bonus for focused phrases
        }

        return [
            'score' => min(100, max(0, $topicScore)),
            'primary_topic_strength' => $this->calculateTopicStrength($topWords),
            'focus_level' => $topicScore >= 80 ? 'High' : ($topicScore >= 60 ? 'Medium' : 'Low')
        ];
    }

    private function calculateTopicStrength(array $topWords): float
    {
        if (empty($topWords)) return 0;

        $topKeyword = reset($topWords);
        $totalDensity = array_sum(array_column($topWords, 'density'));

        return $totalDensity > 0 ? ($topKeyword['density'] / $totalDensity) * 100 : 0;
    }

    private function calculateKeywordDiversity(array $keywords): float
    {
        $wordCount = count($keywords);
        if ($wordCount === 0) return 0;

        // Higher diversity is good for content richness
        return min(100, $wordCount * 5);
    }

    private function calculateSemanticCoherence(array $semanticGroups): float
    {
        $groupCount = count($semanticGroups);
        $totalKeywords = array_sum(array_map('count', $semanticGroups));

        if ($totalKeywords === 0) return 50; // Neutral score

        // Higher ratio of grouped keywords indicates better semantic coherence
        $coherenceRatio = $totalKeywords > 0 ? ($groupCount / $totalKeywords) : 0;
        return min(100, $coherenceRatio * 200);
    }

    private function suggestRelatedKeywords(array $keywords): array
    {
        // Basic related keyword suggestions - would use external APIs in production
        $suggestions = [];
        $topKeywords = array_slice($keywords, 0, 3, true);

        foreach ($topKeywords as $keyword => $data) {
            $suggestions[] = [
                'base_keyword' => $keyword,
                'suggestions' => [
                    $keyword . ' guide',
                    $keyword . ' tips',
                    'best ' . $keyword,
                    $keyword . ' benefits'
                ]
            ];
        }

        return array_slice($suggestions, 0, 3); // Limit suggestions
    }

    private function generateDensityInsights(float $densityScore, array $stuffingAnalysis, int $totalWords): array
    {
        $insights = [];

        if ($densityScore >= 85) {
            $insights[] = 'Excellent keyword optimization with natural distribution';
        } elseif ($densityScore >= 70) {
            $insights[] = 'Good keyword usage with room for minor improvements';
        } else {
            $insights[] = 'Keyword optimization needs improvement';
        }

        if ($stuffingAnalysis['has_stuffing']) {
            $insights[] = 'Keyword stuffing detected - reduce keyword repetition';
        }

        if ($totalWords < 300) {
            $insights[] = 'Short content may limit keyword optimization opportunities';
        }

        return $insights;
    }
}