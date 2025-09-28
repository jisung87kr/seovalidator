<?php

namespace App\Services\Crawler;

use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Content Extractor for text analysis and content evaluation
 *
 * Extracts and analyzes textual content from web pages including
 * readability metrics, keyword analysis, and content structure evaluation.
 */
class ContentExtractor
{
    private array $config;
    private array $stopWords;

    public function __construct()
    {
        $this->config = config('puppeteer.content_analysis', []);
        $this->stopWords = $this->loadStopWords();
    }

    /**
     * Extract and analyze content from HTML
     *
     * @param string $html HTML content
     * @param array $options Analysis options
     * @return array Content analysis results
     */
    public function extractFromHtml(string $html, array $options = []): array
    {
        try {
            // Clean HTML and extract text content
            $cleanHtml = $this->cleanHtml($html, $options);
            $textContent = $this->extractTextContent($cleanHtml, $options);

            return [
                'text_analysis' => $this->analyzeText($textContent),
                'content_structure' => $this->analyzeContentStructure($cleanHtml),
                'readability' => $this->analyzeReadability($textContent),
                'keywords' => $this->analyzeKeywords($textContent, $options),
                'content_quality' => $this->assessContentQuality($textContent, $cleanHtml),
                'language_detection' => $this->detectLanguage($textContent),
                'content_categories' => $this->categorizeContent($textContent, $cleanHtml),
                'seo_content' => $this->analyzeSeoContent($textContent, $cleanHtml),
                'extracted_at' => time()
            ];

        } catch (Exception $e) {
            Log::error("Content extraction failed", ['error' => $e->getMessage()]);
            throw new Exception("Failed to extract content: " . $e->getMessage());
        }
    }

    /**
     * Extract specific content sections
     *
     * @param string $html HTML content
     * @param array $selectors CSS selectors for content sections
     * @param array $options Extraction options
     * @return array Section content analysis
     */
    public function extractSections(string $html, array $selectors, array $options = []): array
    {
        try {
            $sections = [];

            foreach ($selectors as $sectionName => $selector) {
                $sectionHtml = $this->extractBySelector($html, $selector);
                $sectionText = $this->extractTextContent($sectionHtml, $options);

                $sections[$sectionName] = [
                    'html' => $sectionHtml,
                    'text' => $sectionText,
                    'word_count' => str_word_count($sectionText),
                    'character_count' => strlen($sectionText),
                    'reading_time' => $this->calculateReadingTime($sectionText),
                    'keywords' => $this->extractTopKeywords($sectionText, 5)
                ];
            }

            return [
                'sections' => $sections,
                'total_sections' => count($sections),
                'combined_analysis' => $this->analyzeCombinedSections($sections)
            ];

        } catch (Exception $e) {
            Log::error("Section extraction failed", ['error' => $e->getMessage()]);
            return ['sections' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * Extract main content using readability algorithms
     *
     * @param string $html HTML content
     * @param array $options Content extraction options
     * @return array Main content analysis
     */
    public function extractMainContent(string $html, array $options = []): array
    {
        try {
            // Use multiple algorithms to identify main content
            $contentCandidates = $this->findContentCandidates($html);
            $mainContent = $this->selectBestContentCandidate($contentCandidates);
            $contentText = $this->extractTextContent($mainContent['html'] ?? '', $options);

            return [
                'main_content' => [
                    'html' => $mainContent['html'] ?? '',
                    'text' => $contentText,
                    'confidence_score' => $mainContent['score'] ?? 0,
                    'extraction_method' => $mainContent['method'] ?? 'unknown'
                ],
                'content_analysis' => $this->analyzeText($contentText),
                'content_quality' => $this->assessContentQuality($contentText, $mainContent['html'] ?? ''),
                'alternatives' => array_slice($contentCandidates, 1, 3) // Top 3 alternatives
            ];

        } catch (Exception $e) {
            Log::error("Main content extraction failed", ['error' => $e->getMessage()]);
            return $this->getEmptyMainContent();
        }
    }

    /**
     * Analyze content for duplicate detection
     *
     * @param string $html HTML content
     * @param array $compareWith Array of content to compare against
     * @param array $options Comparison options
     * @return array Duplicate analysis results
     */
    public function analyzeDuplicateContent(string $html, array $compareWith = [], array $options = []): array
    {
        try {
            $content = $this->extractTextContent($this->cleanHtml($html), $options);
            $contentFingerprint = $this->generateContentFingerprint($content);

            $duplicateAnalysis = [
                'content_fingerprint' => $contentFingerprint,
                'content_hash' => md5($content),
                'word_count' => str_word_count($content),
                'unique_word_ratio' => $this->calculateUniqueWordRatio($content),
                'duplicate_matches' => [],
                'similarity_scores' => []
            ];

            // Compare with provided content
            foreach ($compareWith as $index => $compareContent) {
                $compareText = is_array($compareContent)
                    ? ($compareContent['text'] ?? $compareContent['content'] ?? '')
                    : $compareContent;

                $similarity = $this->calculateContentSimilarity($content, $compareText);
                $duplicateAnalysis['similarity_scores'][$index] = $similarity;

                if ($similarity > ($options['duplicate_threshold'] ?? 0.8)) {
                    $duplicateAnalysis['duplicate_matches'][] = [
                        'index' => $index,
                        'similarity' => $similarity,
                        'type' => $similarity > 0.95 ? 'exact' : 'near_duplicate'
                    ];
                }
            }

            return $duplicateAnalysis;

        } catch (Exception $e) {
            Log::error("Duplicate content analysis failed", ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Extract and analyze content for accessibility
     *
     * @param string $html HTML content
     * @param array $options Accessibility options
     * @return array Accessibility content analysis
     */
    public function analyzeAccessibilityContent(string $html, array $options = []): array
    {
        try {
            return [
                'reading_level' => $this->calculateReadingLevel($html),
                'content_structure' => $this->analyzeContentHierarchy($html),
                'plain_text_alternative' => $this->generatePlainText($html),
                'content_landmarks' => $this->identifyContentLandmarks($html),
                'alt_text_analysis' => $this->analyzeAltTextContent($html),
                'heading_content' => $this->analyzeHeadingContent($html),
                'link_context' => $this->analyzeLinkContext($html),
                'form_content' => $this->analyzeFormContent($html)
            ];

        } catch (Exception $e) {
            Log::error("Accessibility content analysis failed", ['error' => $e->getMessage()]);
            return $this->getEmptyAccessibilityAnalysis();
        }
    }

    /**
     * Clean HTML content removing unwanted elements
     *
     * @param string $html HTML content
     * @param array $options Cleaning options
     * @return string Cleaned HTML
     */
    private function cleanHtml(string $html, array $options = []): string
    {
        // Remove script and style tags
        $html = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $html);
        $html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);

        // Remove comments
        $html = preg_replace('/<!--.*?-->/is', '', $html);

        // Remove unwanted elements based on options
        $removeElements = $options['remove_elements'] ?? ['nav', 'header', 'footer', 'aside'];
        foreach ($removeElements as $element) {
            $html = preg_replace("/<{$element}[^>]*>.*?<\/{$element}>/is", '', $html);
        }

        // Clean up excessive whitespace
        $html = preg_replace('/\s+/', ' ', $html);
        $html = preg_replace('/>\s+</', '><', $html);

        return trim($html);
    }

    /**
     * Extract text content from HTML
     *
     * @param string $html HTML content
     * @param array $options Extraction options
     * @return string Text content
     */
    private function extractTextContent(string $html, array $options = []): string
    {
        // Convert HTML entities
        $text = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Strip HTML tags
        $text = strip_tags($text);

        // Clean up whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        // Apply text filters if specified
        if ($options['min_sentence_length'] ?? 0) {
            $sentences = explode('.', $text);
            $sentences = array_filter($sentences, function($sentence) use ($options) {
                return strlen(trim($sentence)) >= $options['min_sentence_length'];
            });
            $text = implode('. ', $sentences);
        }

        return $text;
    }

    /**
     * Analyze text content metrics
     *
     * @param string $text Text content
     * @return array Text analysis
     */
    private function analyzeText(string $text): array
    {
        $sentences = $this->splitIntoSentences($text);
        $words = $this->splitIntoWords($text);
        $paragraphs = $this->splitIntoParagraphs($text);

        return [
            'character_count' => strlen($text),
            'character_count_no_spaces' => strlen(str_replace(' ', '', $text)),
            'word_count' => count($words),
            'sentence_count' => count($sentences),
            'paragraph_count' => count($paragraphs),
            'average_words_per_sentence' => count($sentences) > 0 ? round(count($words) / count($sentences), 2) : 0,
            'average_characters_per_word' => count($words) > 0 ? round(array_sum(array_map('strlen', $words)) / count($words), 2) : 0,
            'reading_time_minutes' => $this->calculateReadingTime($text),
            'unique_words' => count(array_unique($words)),
            'vocabulary_diversity' => count($words) > 0 ? round(count(array_unique($words)) / count($words), 4) : 0,
            'longest_sentence' => $this->findLongestSentence($sentences),
            'shortest_sentence' => $this->findShortestSentence($sentences)
        ];
    }

    /**
     * Analyze content structure and organization
     *
     * @param string $html HTML content
     * @return array Structure analysis
     */
    private function analyzeContentStructure(string $html): array
    {
        return [
            'headings_structure' => $this->analyzeHeadingsStructure($html),
            'paragraph_analysis' => $this->analyzeParagraphs($html),
            'list_analysis' => $this->analyzeLists($html),
            'content_blocks' => $this->identifyContentBlocks($html),
            'content_density' => $this->calculateContentDensity($html),
            'structural_elements' => $this->countStructuralElements($html)
        ];
    }

    /**
     * Analyze content readability
     *
     * @param string $text Text content
     * @return array Readability analysis
     */
    private function analyzeReadability(string $text): array
    {
        $sentences = $this->splitIntoSentences($text);
        $words = $this->splitIntoWords($text);
        $syllables = $this->countSyllables($text);

        // Calculate various readability scores
        $fleschKincaid = $this->calculateFleschKincaidScore($words, $sentences, $syllables);
        $fleschReadingEase = $this->calculateFleschReadingEase($words, $sentences, $syllables);
        $gunningFog = $this->calculateGunningFogIndex($words, $sentences);

        return [
            'flesch_kincaid_grade' => $fleschKincaid,
            'flesch_reading_ease' => $fleschReadingEase,
            'gunning_fog_index' => $gunningFog,
            'automated_readability_index' => $this->calculateARI($text),
            'coleman_liau_index' => $this->calculateColemanLiau($text),
            'readability_grade' => $this->getReadabilityGrade($fleschKincaid),
            'reading_difficulty' => $this->getReadingDifficulty($fleschReadingEase),
            'complex_words_percentage' => $this->calculateComplexWordsPercentage($words),
            'average_sentence_length' => count($sentences) > 0 ? round(count($words) / count($sentences), 2) : 0
        ];
    }

    /**
     * Analyze keywords and phrases
     *
     * @param string $text Text content
     * @param array $options Analysis options
     * @return array Keyword analysis
     */
    private function analyzeKeywords(string $text, array $options = []): array
    {
        $words = $this->splitIntoWords($text);
        $cleanWords = $this->removeStopWords($words);

        // Single word analysis
        $singleKeywords = $this->analyzeSingleKeywords($cleanWords);

        // Multi-word phrase analysis
        $phrases = $this->extractPhrases($text, $options['phrase_length'] ?? [2, 3, 4]);

        // Keyword density analysis
        $keywordDensity = $this->calculateKeywordDensity($words);

        // Semantic keyword analysis
        $semanticKeywords = $this->extractSemanticKeywords($text);

        return [
            'single_keywords' => $singleKeywords,
            'phrases' => $phrases,
            'keyword_density' => $keywordDensity,
            'semantic_keywords' => $semanticKeywords,
            'total_unique_keywords' => count($singleKeywords),
            'keyword_distribution' => $this->analyzeKeywordDistribution($text, array_keys($singleKeywords)),
            'long_tail_keywords' => $this->identifyLongTailKeywords($phrases)
        ];
    }

    /**
     * Assess overall content quality
     *
     * @param string $text Text content
     * @param string $html HTML content
     * @return array Quality assessment
     */
    private function assessContentQuality(string $text, string $html): array
    {
        $wordCount = str_word_count($text);
        $readabilityScores = $this->analyzeReadability($text);

        $qualityFactors = [
            'word_count_score' => $this->scoreWordCount($wordCount),
            'readability_score' => $this->scoreReadability($readabilityScores),
            'content_depth_score' => $this->scoreContentDepth($text, $html),
            'uniqueness_score' => $this->scoreContentUniqueness($text),
            'structure_score' => $this->scoreContentStructure($html),
            'engagement_score' => $this->scoreContentEngagement($text, $html)
        ];

        $overallScore = array_sum($qualityFactors) / count($qualityFactors);

        return [
            'overall_score' => round($overallScore, 1),
            'grade' => $this->getQualityGrade($overallScore),
            'quality_factors' => $qualityFactors,
            'recommendations' => $this->generateQualityRecommendations($qualityFactors, $text, $html),
            'content_freshness' => $this->analyzeContentFreshness($text, $html)
        ];
    }

    /**
     * Detect content language
     *
     * @param string $text Text content
     * @return array Language detection results
     */
    private function detectLanguage(string $text): array
    {
        // Simple language detection based on common words and patterns
        $languages = $this->detectLanguageByPatterns($text);

        return [
            'detected_language' => $languages['primary'] ?? 'unknown',
            'confidence' => $languages['confidence'] ?? 0,
            'alternative_languages' => $languages['alternatives'] ?? [],
            'mixed_language' => count($languages['alternatives'] ?? []) > 0,
            'language_indicators' => $this->findLanguageIndicators($text)
        ];
    }

    /**
     * Categorize content by type and topic
     *
     * @param string $text Text content
     * @param string $html HTML content
     * @return array Content categorization
     */
    private function categorizeContent(string $text, string $html): array
    {
        return [
            'content_type' => $this->identifyContentType($text, $html),
            'topics' => $this->extractTopics($text),
            'writing_style' => $this->analyzeWritingStyle($text),
            'audience_level' => $this->determineAudienceLevel($text),
            'content_intent' => $this->analyzeContentIntent($text, $html),
            'industry_indicators' => $this->identifyIndustryIndicators($text)
        ];
    }

    /**
     * Analyze content for SEO factors
     *
     * @param string $text Text content
     * @param string $html HTML content
     * @return array SEO content analysis
     */
    private function analyzeSeoContent(string $text, string $html): array
    {
        return [
            'keyword_optimization' => $this->analyzeSeoKeywords($text),
            'content_length_seo' => $this->analyzeSeoContentLength(str_word_count($text)),
            'heading_optimization' => $this->analyzeSeoHeadings($html),
            'internal_linking' => $this->analyzeSeoInternalLinks($html),
            'content_freshness_seo' => $this->analyzeSeoFreshness($text, $html),
            'content_uniqueness_seo' => $this->analyzeSeoUniqueness($text),
            'readability_seo' => $this->analyzeSeoReadability($this->analyzeReadability($text)),
            'content_structure_seo' => $this->analyzeSeoStructure($html)
        ];
    }

    // Helper methods for text processing

    private function splitIntoSentences(string $text): array
    {
        // Simple sentence splitting on periods, exclamation marks, and question marks
        $sentences = preg_split('/[.!?]+/', $text);
        return array_filter(array_map('trim', $sentences), function($sentence) {
            return strlen($sentence) > 0;
        });
    }

    private function splitIntoWords(string $text): array
    {
        // Split on whitespace and common punctuation
        $words = preg_split('/[\s\-_,;:()]+/', strtolower($text));
        return array_filter(array_map('trim', $words), function($word) {
            return strlen($word) > 0 && ctype_alpha($word);
        });
    }

    private function splitIntoParagraphs(string $text): array
    {
        $paragraphs = preg_split('/\n\s*\n/', $text);
        return array_filter(array_map('trim', $paragraphs), function($paragraph) {
            return strlen($paragraph) > 0;
        });
    }

    private function calculateReadingTime(string $text): float
    {
        $wordCount = str_word_count($text);
        $wordsPerMinute = 200; // Average reading speed
        return round($wordCount / $wordsPerMinute, 1);
    }

    private function countSyllables(string $text): int
    {
        $words = $this->splitIntoWords($text);
        $totalSyllables = 0;

        foreach ($words as $word) {
            $totalSyllables += $this->countSyllablesInWord($word);
        }

        return $totalSyllables;
    }

    private function countSyllablesInWord(string $word): int
    {
        // Simple syllable counting algorithm
        $word = strtolower($word);
        $syllableCount = 0;
        $vowels = 'aeiouy';
        $previousWasVowel = false;

        for ($i = 0; $i < strlen($word); $i++) {
            $isVowel = strpos($vowels, $word[$i]) !== false;
            if ($isVowel && !$previousWasVowel) {
                $syllableCount++;
            }
            $previousWasVowel = $isVowel;
        }

        // Handle silent e
        if (substr($word, -1) === 'e' && $syllableCount > 1) {
            $syllableCount--;
        }

        return max(1, $syllableCount);
    }

    private function loadStopWords(): array
    {
        // Common English stop words
        return [
            'a', 'an', 'and', 'are', 'as', 'at', 'be', 'by', 'for', 'from',
            'has', 'he', 'in', 'is', 'it', 'its', 'of', 'on', 'that', 'the',
            'to', 'was', 'will', 'with', 'would', 'you', 'your', 'we', 'they',
            'them', 'their', 'this', 'these', 'those', 'have', 'had', 'can',
            'could', 'should', 'or', 'but', 'not', 'do', 'does', 'did', 'i',
            'me', 'my', 'myself', 'we', 'our', 'ours', 'ourselves'
        ];
    }

    private function removeStopWords(array $words): array
    {
        return array_values(array_filter($words, function($word) {
            return !in_array(strtolower($word), $this->stopWords);
        }));
    }

    // Readability calculation methods

    private function calculateFleschKincaidScore(array $words, array $sentences, int $syllables): float
    {
        $wordCount = count($words);
        $sentenceCount = count($sentences);

        if ($sentenceCount === 0 || $wordCount === 0) {
            return 0;
        }

        $score = 0.39 * ($wordCount / $sentenceCount) + 11.8 * ($syllables / $wordCount) - 15.59;
        return round($score, 2);
    }

    private function calculateFleschReadingEase(array $words, array $sentences, int $syllables): float
    {
        $wordCount = count($words);
        $sentenceCount = count($sentences);

        if ($sentenceCount === 0 || $wordCount === 0) {
            return 0;
        }

        $score = 206.835 - (1.015 * ($wordCount / $sentenceCount)) - (84.6 * ($syllables / $wordCount));
        return round($score, 2);
    }

    private function calculateGunningFogIndex(array $words, array $sentences): float
    {
        $wordCount = count($words);
        $sentenceCount = count($sentences);

        if ($sentenceCount === 0 || $wordCount === 0) {
            return 0;
        }

        $complexWords = $this->countComplexWords($words);
        $score = 0.4 * (($wordCount / $sentenceCount) + (100 * ($complexWords / $wordCount)));
        return round($score, 2);
    }

    private function calculateARI(string $text): float
    {
        $characters = strlen(str_replace(' ', '', $text));
        $words = str_word_count($text);
        $sentences = count($this->splitIntoSentences($text));

        if ($sentences === 0 || $words === 0) {
            return 0;
        }

        $score = 4.71 * ($characters / $words) + 0.5 * ($words / $sentences) - 21.43;
        return round($score, 2);
    }

    private function calculateColemanLiau(string $text): float
    {
        $characters = strlen(str_replace(' ', '', $text));
        $words = str_word_count($text);
        $sentences = count($this->splitIntoSentences($text));

        if ($words === 0) {
            return 0;
        }

        $l = ($characters / $words) * 100;
        $s = ($sentences / $words) * 100;
        $score = 0.0588 * $l - 0.296 * $s - 15.8;
        return round($score, 2);
    }

    private function countComplexWords(array $words): int
    {
        $complexCount = 0;
        foreach ($words as $word) {
            if ($this->countSyllablesInWord($word) >= 3) {
                $complexCount++;
            }
        }
        return $complexCount;
    }

    private function calculateComplexWordsPercentage(array $words): float
    {
        if (empty($words)) {
            return 0;
        }

        $complexWords = $this->countComplexWords($words);
        return round(($complexWords / count($words)) * 100, 2);
    }

    private function getReadabilityGrade(float $score): string
    {
        if ($score <= 6) return __('analysis.readability_elementary_school');
        if ($score <= 8) return __('analysis.readability_middle_school');
        if ($score <= 12) return __('analysis.readability_high_school');
        if ($score <= 16) return __('analysis.readability_college');
        return __('analysis.readability_graduate');
    }

    private function getReadingDifficulty(float $score): string
    {
        if ($score >= 90) return __('analysis.reading_very_easy');
        if ($score >= 80) return __('analysis.reading_easy');
        if ($score >= 70) return __('analysis.reading_fairly_easy');
        if ($score >= 60) return __('analysis.reading_standard');
        if ($score >= 50) return __('analysis.reading_fairly_difficult');
        if ($score >= 30) return __('analysis.reading_difficult');
        return __('analysis.reading_very_difficult');
    }

    // Keyword analysis methods

    private function analyzeSingleKeywords(array $words): array
    {
        $frequency = array_count_values($words);
        arsort($frequency);

        $keywords = [];
        $totalWords = count($words);

        foreach (array_slice($frequency, 0, 20, true) as $word => $count) {
            if (strlen($word) >= 3) { // Filter out very short words
                $keywords[$word] = [
                    'count' => $count,
                    'density' => round(($count / $totalWords) * 100, 2),
                    'length' => strlen($word)
                ];
            }
        }

        return $keywords;
    }

    private function extractPhrases(string $text, array $phraseLengths): array
    {
        $phrases = [];
        $words = $this->splitIntoWords($text);

        foreach ($phraseLengths as $length) {
            $lengthPhrases = $this->extractPhrasesOfLength($words, $length);
            $phrases = array_merge($phrases, $lengthPhrases);
        }

        // Sort by frequency
        arsort($phrases);

        return array_slice($phrases, 0, 15, true);
    }

    private function extractPhrasesOfLength(array $words, int $length): array
    {
        $phrases = [];
        $wordCount = count($words);

        for ($i = 0; $i <= $wordCount - $length; $i++) {
            $phrase = implode(' ', array_slice($words, $i, $length));

            // Skip phrases that are all stop words
            if (!$this->isAllStopWords(array_slice($words, $i, $length))) {
                $phrases[$phrase] = ($phrases[$phrase] ?? 0) + 1;
            }
        }

        return array_filter($phrases, function($count) {
            return $count > 1; // Only include phrases that appear more than once
        });
    }

    private function isAllStopWords(array $words): bool
    {
        foreach ($words as $word) {
            if (!in_array(strtolower($word), $this->stopWords)) {
                return false;
            }
        }
        return true;
    }

    private function calculateKeywordDensity(array $words): array
    {
        $totalWords = count($words);
        $frequency = array_count_values($words);
        $density = [];

        foreach ($frequency as $word => $count) {
            if (strlen($word) >= 3 && !in_array(strtolower($word), $this->stopWords)) {
                $density[$word] = [
                    'count' => $count,
                    'density' => round(($count / $totalWords) * 100, 2)
                ];
            }
        }

        return $density;
    }

    private function extractTopKeywords(string $text, int $limit): array
    {
        $words = $this->removeStopWords($this->splitIntoWords($text));
        $frequency = array_count_values($words);
        arsort($frequency);

        return array_slice($frequency, 0, $limit, true);
    }

    // Content quality scoring methods

    private function scoreWordCount(int $wordCount): float
    {
        if ($wordCount >= 2000) return 100;
        if ($wordCount >= 1500) return 90;
        if ($wordCount >= 1000) return 80;
        if ($wordCount >= 500) return 70;
        if ($wordCount >= 300) return 60;
        if ($wordCount >= 150) return 50;
        return 30;
    }

    private function scoreReadability(array $readabilityScores): float
    {
        $fleschScore = $readabilityScores['flesch_reading_ease'];

        if ($fleschScore >= 70) return 90; // Easy to read
        if ($fleschScore >= 60) return 80; // Standard
        if ($fleschScore >= 50) return 70; // Fairly difficult
        if ($fleschScore >= 30) return 60; // Difficult
        return 40; // Very difficult
    }

    private function scoreContentDepth(string $text, string $html): float
    {
        $headingCount = substr_count($html, '<h');
        $paragraphCount = substr_count($html, '<p');
        $listCount = substr_count($html, '<ul') + substr_count($html, '<ol');

        $structureScore = min(100, ($headingCount * 10) + ($paragraphCount * 3) + ($listCount * 5));
        return $structureScore;
    }

    private function scoreContentUniqueness(string $text): float
    {
        $words = $this->splitIntoWords($text);
        $uniqueRatio = count(array_unique($words)) / count($words);
        return round($uniqueRatio * 100, 1);
    }

    private function scoreContentStructure(string $html): float
    {
        $score = 0;

        if (preg_match('/<h1[^>]*>/', $html)) $score += 20;
        if (preg_match_all('/<h[2-6][^>]*>/', $html) >= 2) $score += 20;
        if (preg_match_all('/<p[^>]*>/', $html) >= 3) $score += 20;
        if (preg_match('/<ul[^>]*>|<ol[^>]*>/', $html)) $score += 20;
        if (preg_match('/<img[^>]*>/', $html)) $score += 10;
        if (preg_match('/<a[^>]*>/', $html)) $score += 10;

        return $score;
    }

    private function scoreContentEngagement(string $text, string $html): float
    {
        $score = 0;

        // Check for questions
        if (substr_count($text, '?') > 0) $score += 20;

        // Check for lists
        if (preg_match('/<ul[^>]*>|<ol[^>]*>/', $html)) $score += 20;

        // Check for images
        if (preg_match('/<img[^>]*>/', $html)) $score += 20;

        // Check for variety in sentence length
        $sentences = $this->splitIntoSentences($text);
        $lengths = array_map('str_word_count', $sentences);
        if (count($lengths) > 1) {
            $variance = $this->calculateVariance($lengths);
            $score += min(40, $variance * 2);
        }

        return min(100, $score);
    }

    private function getQualityGrade(float $score): string
    {
        if ($score >= 90) return __('analysis.quality_excellent');
        if ($score >= 80) return __('analysis.quality_good');
        if ($score >= 70) return __('analysis.quality_fair');
        if ($score >= 60) return __('analysis.quality_poor');
        return __('analysis.quality_very_poor');
    }

    private function generateQualityRecommendations(array $qualityFactors, string $text, string $html): array
    {
        $recommendations = [];

        if ($qualityFactors['word_count_score'] < 70) {
            $recommendations[] = __('analysis.content_increase_length_recommendation');
        }

        if ($qualityFactors['readability_score'] < 70) {
            $recommendations[] = __('analysis.content_improve_readability_recommendation');
        }

        if ($qualityFactors['structure_score'] < 80) {
            $recommendations[] = __('analysis.content_add_structure_recommendation');
        }

        if ($qualityFactors['engagement_score'] < 70) {
            $recommendations[] = __('analysis.content_increase_engagement_recommendation');
        }

        return $recommendations;
    }

    // Helper methods

    private function findLongestSentence(array $sentences): array
    {
        if (empty($sentences)) {
            return ['text' => '', 'word_count' => 0];
        }

        $longest = '';
        $maxWords = 0;

        foreach ($sentences as $sentence) {
            $wordCount = str_word_count($sentence);
            if ($wordCount > $maxWords) {
                $maxWords = $wordCount;
                $longest = $sentence;
            }
        }

        return ['text' => trim($longest), 'word_count' => $maxWords];
    }

    private function findShortestSentence(array $sentences): array
    {
        if (empty($sentences)) {
            return ['text' => '', 'word_count' => 0];
        }

        $shortest = $sentences[0];
        $minWords = str_word_count($shortest);

        foreach ($sentences as $sentence) {
            $wordCount = str_word_count($sentence);
            if ($wordCount < $minWords && $wordCount > 0) {
                $minWords = $wordCount;
                $shortest = $sentence;
            }
        }

        return ['text' => trim($shortest), 'word_count' => $minWords];
    }

    private function calculateVariance(array $numbers): float
    {
        if (count($numbers) === 0) return 0;

        $mean = array_sum($numbers) / count($numbers);
        $sumSquares = array_sum(array_map(function($n) use ($mean) {
            return pow($n - $mean, 2);
        }, $numbers));

        return $sumSquares / count($numbers);
    }

    // Placeholder implementations for complex methods

    private function extractBySelector(string $html, string $selector): string
    {
        // This would require a CSS selector engine
        // For now, return the original HTML
        return $html;
    }

    private function analyzeCombinedSections(array $sections): array
    {
        $totalWords = array_sum(array_column($sections, 'word_count'));
        $totalTime = array_sum(array_column($sections, 'reading_time'));

        return [
            'total_word_count' => $totalWords,
            'total_reading_time' => $totalTime,
            'average_section_length' => count($sections) > 0 ? round($totalWords / count($sections)) : 0
        ];
    }

    private function findContentCandidates(string $html): array
    {
        // Simplified content candidate detection
        return [
            [
                'html' => $html,
                'score' => 85,
                'method' => 'full_content'
            ]
        ];
    }

    private function selectBestContentCandidate(array $candidates): array
    {
        if (empty($candidates)) {
            return ['html' => '', 'score' => 0, 'method' => 'none'];
        }

        return $candidates[0];
    }

    private function getEmptyMainContent(): array
    {
        return [
            'main_content' => [
                'html' => '',
                'text' => '',
                'confidence_score' => 0,
                'extraction_method' => 'failed'
            ],
            'content_analysis' => [],
            'content_quality' => [],
            'alternatives' => []
        ];
    }

    private function generateContentFingerprint(string $content): string
    {
        // Create a content fingerprint using shingles
        $words = $this->splitIntoWords($content);
        $shingles = [];

        for ($i = 0; $i < count($words) - 2; $i++) {
            $shingle = implode(' ', array_slice($words, $i, 3));
            $shingles[] = md5($shingle);
        }

        sort($shingles);
        return md5(implode('', array_slice($shingles, 0, 20)));
    }

    private function calculateUniqueWordRatio(string $content): float
    {
        $words = $this->splitIntoWords($content);
        if (empty($words)) return 0;

        return round(count(array_unique($words)) / count($words), 4);
    }

    private function calculateContentSimilarity(string $content1, string $content2): float
    {
        $words1 = array_unique($this->splitIntoWords($content1));
        $words2 = array_unique($this->splitIntoWords($content2));

        if (empty($words1) || empty($words2)) return 0;

        $intersection = array_intersect($words1, $words2);
        $union = array_unique(array_merge($words1, $words2));

        return count($intersection) / count($union);
    }

    // Accessibility analysis methods

    private function calculateReadingLevel(string $html): array
    {
        $text = $this->extractTextContent($this->cleanHtml($html));
        return $this->analyzeReadability($text);
    }

    private function analyzeContentHierarchy(string $html): array
    {
        $headings = [];
        preg_match_all('/<h([1-6])[^>]*>(.*?)<\/h[1-6]>/i', $html, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $headings[] = [
                'level' => (int)$match[1],
                'text' => strip_tags($match[2])
            ];
        }

        return [
            'headings' => $headings,
            'hierarchy_valid' => $this->validateHeadingHierarchy(array_column($headings, 'level'))
        ];
    }

    private function generatePlainText(string $html): string
    {
        return $this->extractTextContent($this->cleanHtml($html));
    }

    private function identifyContentLandmarks(string $html): array
    {
        $landmarks = [];
        $landmarkTypes = ['main', 'nav', 'header', 'footer', 'aside', 'section'];

        foreach ($landmarkTypes as $type) {
            if (preg_match("/<{$type}[^>]*>/i", $html)) {
                $landmarks[] = $type;
            }
        }

        return $landmarks;
    }

    private function analyzeAltTextContent(string $html): array
    {
        preg_match_all('/<img[^>]*alt=["\']([^"\']*)["\'][^>]*>/i', $html, $matches);
        $altTexts = $matches[1];

        return [
            'total_alt_texts' => count($altTexts),
            'empty_alt_count' => count(array_filter($altTexts, function($alt) { return empty(trim($alt)); })),
            'average_length' => count($altTexts) > 0 ? round(array_sum(array_map('strlen', $altTexts)) / count($altTexts), 1) : 0
        ];
    }

    private function analyzeHeadingContent(string $html): array
    {
        $headingContent = [];

        for ($level = 1; $level <= 6; $level++) {
            preg_match_all("/<h{$level}[^>]*>(.*?)<\/h{$level}>/i", $html, $matches);
            $headingContent["h{$level}"] = array_map(function($heading) {
                return [
                    'text' => strip_tags($heading),
                    'length' => strlen(strip_tags($heading)),
                    'word_count' => str_word_count(strip_tags($heading))
                ];
            }, $matches[1]);
        }

        return $headingContent;
    }

    private function analyzeLinkContext(string $html): array
    {
        preg_match_all('/<a[^>]*>(.*?)<\/a>/i', $html, $matches);
        $linkTexts = $matches[1];

        $analysis = [];
        foreach ($linkTexts as $linkText) {
            $cleanText = strip_tags($linkText);
            $analysis[] = [
                'text' => $cleanText,
                'length' => strlen($cleanText),
                'is_descriptive' => strlen($cleanText) > 4 && !in_array(strtolower($cleanText), ['click here', 'read more', 'more', 'here'])
            ];
        }

        return [
            'links' => $analysis,
            'total_count' => count($analysis),
            'descriptive_count' => count(array_filter($analysis, function($link) { return $link['is_descriptive']; }))
        ];
    }

    private function analyzeFormContent(string $html): array
    {
        preg_match_all('/<label[^>]*>(.*?)<\/label>/i', $html, $labelMatches);
        preg_match_all('/<input[^>]*>/i', $html, $inputMatches);

        return [
            'label_count' => count($labelMatches[1]),
            'input_count' => count($inputMatches[0]),
            'label_to_input_ratio' => count($inputMatches[0]) > 0 ? round(count($labelMatches[1]) / count($inputMatches[0]), 2) : 0
        ];
    }

    private function getEmptyAccessibilityAnalysis(): array
    {
        return [
            'reading_level' => [],
            'content_structure' => [],
            'plain_text_alternative' => '',
            'content_landmarks' => [],
            'alt_text_analysis' => [],
            'heading_content' => [],
            'link_context' => [],
            'form_content' => []
        ];
    }

    // Placeholder methods for advanced analysis

    private function extractSemanticKeywords(string $text): array
    {
        // This would require NLP libraries for proper semantic analysis
        return [];
    }

    private function analyzeKeywordDistribution(string $text, array $keywords): array
    {
        // This would analyze how keywords are distributed throughout the text
        return ['uniform_distribution' => true];
    }

    private function identifyLongTailKeywords(array $phrases): array
    {
        // Filter phrases that are 3+ words and have low frequency
        return array_filter($phrases, function($count, $phrase) {
            return str_word_count($phrase) >= 3 && $count <= 3;
        }, ARRAY_FILTER_USE_BOTH);
    }

    private function analyzeContentFreshness(string $text, string $html): array
    {
        // This would analyze temporal indicators in content
        return ['freshness_score' => 50];
    }

    private function detectLanguageByPatterns(string $text): array
    {
        // Simple English detection - in reality, this would use proper language detection
        $commonEnglishWords = ['the', 'and', 'a', 'to', 'of', 'in', 'is', 'you', 'that', 'it'];
        $words = $this->splitIntoWords($text);
        $englishWordCount = 0;

        foreach ($words as $word) {
            if (in_array(strtolower($word), $commonEnglishWords)) {
                $englishWordCount++;
            }
        }

        $confidence = count($words) > 0 ? ($englishWordCount / count($words)) * 100 : 0;

        return [
            'primary' => $confidence > 10 ? 'en' : 'unknown',
            'confidence' => $confidence,
            'alternatives' => []
        ];
    }

    private function findLanguageIndicators(string $text): array
    {
        return ['common_words_detected' => true];
    }

    private function identifyContentType(string $text, string $html): string
    {
        if (strpos($html, '<article') !== false) return 'article';
        if (strpos($html, '<form') !== false) return 'form';
        if (preg_match('/<h[1-6][^>]*>.*?how to.*?<\/h[1-6]>/i', $html)) return 'tutorial';
        if (str_word_count($text) > 500) return 'long-form';
        return 'general';
    }

    private function extractTopics(string $text): array
    {
        // This would require topic modeling or NLP libraries
        return ['general_content'];
    }

    private function analyzeWritingStyle(string $text): array
    {
        $sentences = $this->splitIntoSentences($text);
        $avgSentenceLength = count($sentences) > 0 ? array_sum(array_map('str_word_count', $sentences)) / count($sentences) : 0;

        return [
            'average_sentence_length' => $avgSentenceLength,
            'style' => $avgSentenceLength > 20 ? 'formal' : 'casual',
            'complexity' => $avgSentenceLength > 25 ? 'high' : ($avgSentenceLength > 15 ? 'medium' : 'low')
        ];
    }

    private function determineAudienceLevel(string $text): string
    {
        $readability = $this->analyzeReadability($text);
        $grade = $readability['flesch_kincaid_grade'];

        if ($grade <= 8) return 'general_public';
        if ($grade <= 12) return 'high_school';
        if ($grade <= 16) return 'college';
        return 'expert';
    }

    private function analyzeContentIntent(string $text, string $html): string
    {
        if (strpos($text, 'buy') !== false || strpos($text, 'purchase') !== false) return 'commercial';
        if (strpos($text, 'how to') !== false || strpos($text, 'tutorial') !== false) return 'educational';
        if (strpos($html, '<form') !== false) return 'transactional';
        return 'informational';
    }

    private function identifyIndustryIndicators(string $text): array
    {
        // This would use industry-specific keyword dictionaries
        return ['general'];
    }

    // SEO analysis helper methods

    private function analyzeSeoKeywords(string $text): array
    {
        $keywords = $this->analyzeKeywords($text);
        return [
            'primary_keywords' => array_slice($keywords['single_keywords'], 0, 5, true),
            'keyword_stuffing_risk' => $this->detectKeywordStuffing($keywords['keyword_density']),
            'semantic_coverage' => 'basic' // Would require semantic analysis
        ];
    }

    private function detectKeywordStuffing(array $keywordDensity): bool
    {
        foreach ($keywordDensity as $keyword => $data) {
            if ($data['density'] > 3.0) { // More than 3% density might be stuffing
                return true;
            }
        }
        return false;
    }

    private function analyzeSeoContentLength(int $wordCount): array
    {
        return [
            'word_count' => $wordCount,
            'seo_rating' => $wordCount >= 300 ? 'good' : 'needs_improvement',
            'target_range' => '300-2000 words'
        ];
    }

    private function analyzeSeoHeadings(string $html): array
    {
        $h1Count = preg_match_all('/<h1[^>]*>/i', $html);
        $totalHeadings = preg_match_all('/<h[1-6][^>]*>/i', $html);

        return [
            'h1_count' => $h1Count,
            'total_headings' => $totalHeadings,
            'structure_score' => $h1Count === 1 && $totalHeadings >= 3 ? 'good' : 'needs_improvement'
        ];
    }

    private function analyzeSeoInternalLinks(string $html): array
    {
        $allLinks = preg_match_all('/<a[^>]*href=[^>]*>/i', $html);
        $externalLinks = preg_match_all('/<a[^>]*href=["\']https?:\/\/[^"\']*["\'][^>]*>/i', $html);
        $internalLinks = $allLinks - $externalLinks;

        return [
            'internal_link_count' => $internalLinks,
            'external_link_count' => $externalLinks,
            'internal_linking_score' => $internalLinks >= 3 ? 'good' : 'needs_improvement'
        ];
    }

    private function analyzeSeoFreshness(string $text, string $html): array
    {
        // This would analyze publication dates, update indicators, etc.
        return [
            'freshness_indicators' => false,
            'estimated_age' => 'unknown'
        ];
    }

    private function analyzeSeoUniqueness(string $text): array
    {
        return [
            'uniqueness_score' => $this->scoreContentUniqueness($text),
            'duplicate_risk' => 'low' // Would require external comparison
        ];
    }

    private function analyzeSeoReadability(array $readabilityScores): array
    {
        return [
            'flesch_score' => $readabilityScores['flesch_reading_ease'],
            'seo_readability_rating' => $readabilityScores['flesch_reading_ease'] >= 60 ? 'good' : 'needs_improvement',
            'target_score' => '60-70 (Standard to Fairly Easy)'
        ];
    }

    private function analyzeSeoStructure(string $html): array
    {
        $paragraphs = preg_match_all('/<p[^>]*>/i', $html);
        $lists = preg_match_all('/<[uo]l[^>]*>/i', $html);
        $images = preg_match_all('/<img[^>]*>/i', $html);

        return [
            'paragraph_count' => $paragraphs,
            'list_count' => $lists,
            'image_count' => $images,
            'structure_diversity' => ($paragraphs > 0 && $lists > 0 && $images > 0) ? 'good' : 'basic'
        ];
    }

    // Additional helper methods

    private function analyzeHeadingsStructure(string $html): array
    {
        $headings = [];
        preg_match_all('/<h([1-6])[^>]*>(.*?)<\/h[1-6]>/i', $html, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $headings[] = [
                'level' => (int)$match[1],
                'text' => strip_tags($match[2]),
                'word_count' => str_word_count(strip_tags($match[2]))
            ];
        }

        return [
            'headings' => $headings,
            'total_count' => count($headings),
            'hierarchy_valid' => $this->validateHeadingHierarchy(array_column($headings, 'level'))
        ];
    }

    private function validateHeadingHierarchy(array $levels): bool
    {
        $previousLevel = 0;

        foreach ($levels as $level) {
            if ($level > $previousLevel + 1) {
                return false; // Skipped heading level
            }
            $previousLevel = $level;
        }

        return true;
    }

    private function analyzeParagraphs(string $html): array
    {
        preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $html, $matches);
        $paragraphs = $matches[1];

        $analysis = [];
        foreach ($paragraphs as $paragraph) {
            $text = strip_tags($paragraph);
            $analysis[] = [
                'text' => $text,
                'word_count' => str_word_count($text),
                'character_count' => strlen($text)
            ];
        }

        return [
            'paragraphs' => $analysis,
            'total_count' => count($analysis),
            'average_length' => count($analysis) > 0 ? round(array_sum(array_column($analysis, 'word_count')) / count($analysis), 1) : 0
        ];
    }

    private function analyzeLists(string $html): array
    {
        $ulCount = preg_match_all('/<ul[^>]*>/i', $html);
        $olCount = preg_match_all('/<ol[^>]*>/i', $html);
        $liCount = preg_match_all('/<li[^>]*>/i', $html);

        return [
            'unordered_lists' => $ulCount,
            'ordered_lists' => $olCount,
            'total_lists' => $ulCount + $olCount,
            'list_items' => $liCount
        ];
    }

    private function identifyContentBlocks(string $html): array
    {
        $blocks = [];

        // Identify semantic content blocks
        $semanticTags = ['article', 'section', 'aside', 'main', 'header', 'footer'];
        foreach ($semanticTags as $tag) {
            $count = preg_match_all("/<{$tag}[^>]*>/i", $html);
            if ($count > 0) {
                $blocks[$tag] = $count;
            }
        }

        return $blocks;
    }

    private function calculateContentDensity(string $html): float
    {
        $textLength = strlen(strip_tags($html));
        $htmlLength = strlen($html);

        return $htmlLength > 0 ? round(($textLength / $htmlLength) * 100, 2) : 0;
    }

    private function countStructuralElements(string $html): array
    {
        return [
            'divs' => preg_match_all('/<div[^>]*>/i', $html),
            'spans' => preg_match_all('/<span[^>]*>/i', $html),
            'paragraphs' => preg_match_all('/<p[^>]*>/i', $html),
            'headings' => preg_match_all('/<h[1-6][^>]*>/i', $html),
            'lists' => preg_match_all('/<[uo]l[^>]*>/i', $html),
            'images' => preg_match_all('/<img[^>]*>/i', $html),
            'links' => preg_match_all('/<a[^>]*>/i', $html)
        ];
    }
}