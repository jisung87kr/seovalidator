<?php

namespace App\Utils;

use App\DTOs\TextProcessorResult;
use DOMDocument;
use DOMXPath;

class TextProcessor
{
    /**
     * Process text and return comprehensive analysis
     */
    public function processText(string $html): TextProcessorResult
    {
        // Handle empty content
        if (empty(trim($html))) {
            return new TextProcessorResult();
        }

        // Parse HTML with proper encoding
        $dom = new DOMDocument();
        $dom->encoding = 'UTF-8';
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $xpath = new DOMXPath($dom);

        // Extract clean text
        $cleanText = $this->extractCleanText($xpath);
        
        // Calculate metrics
        $wordCount = $this->getWordCount($cleanText);
        $characterCount = $this->getCharacterCount($cleanText);
        $sentences = $this->getSentences($cleanText);
        $sentenceCount = count($sentences);
        $paragraphCount = $this->getParagraphCount($xpath);
        $detectedLanguage = $this->detectLanguage($cleanText);
        $languageMetrics = $this->calculateLanguageMetrics($cleanText, $detectedLanguage);
        $wordFrequency = $this->getMostCommonWords($cleanText, 20);
        $textDensity = $this->getTextDensity($cleanText);
        $normalizedText = $this->normalizeText($cleanText);
        $sentenceStructure = $this->analyzeSentenceStructure($sentences);

        // Add syllable estimation to language metrics
        $languageMetrics['syllable_estimation'] = $this->getTotalSyllables($cleanText);

        return new TextProcessorResult(
            $wordCount,
            $characterCount,
            $sentenceCount,
            $paragraphCount,
            $detectedLanguage,
            $languageMetrics,
            $wordFrequency,
            $textDensity,
            $cleanText,
            $normalizedText,
            $sentenceStructure
        );
    }

    /**
     * Extract clean text from HTML
     */
    private function extractCleanText(DOMXPath $xpath): string
    {
        // Remove script and style tags first
        $body = $xpath->query('//body')->item(0);
        if (!$body) {
            // If no body tag, use the whole document
            $body = $xpath->query('/*')->item(0);
        }
        
        if (!$body) {
            return '';
        }
        
        // Get text content directly, which preserves UTF-8
        $text = $body->textContent;
        
        return $this->cleanText($text);
    }

    /**
     * Get paragraph count from DOM
     */
    private function getParagraphCount(DOMXPath $xpath): int
    {
        return $xpath->query('//p')->length;
    }

    /**
     * Calculate language confidence metrics
     */
    private function calculateLanguageMetrics(string $text, string $detectedLanguage): array
    {
        $confidence = 0;
        
        switch ($detectedLanguage) {
            case 'ko':
                // Korean characters
                $koreanChars = preg_match_all('/[\x{AC00}-\x{D7AF}]/u', $text);
                $totalChars = mb_strlen($text);
                $confidence = $totalChars > 0 ? ($koreanChars / $totalChars) * 100 : 0;
                break;
                
            case 'ja':
                // Japanese characters
                $japaneseChars = preg_match_all('/[\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{4E00}-\x{9FAF}]/u', $text);
                $totalChars = mb_strlen($text);
                $confidence = $totalChars > 0 ? ($japaneseChars / $totalChars) * 100 : 0;
                break;
                
            case 'en':
            default:
                // English detection confidence based on common English words
                $englishWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were', 'this', 'that', 'it', 'have', 'has', 'will', 'would', 'can', 'could'];
                $words = $this->getWords($text);
                $englishWordCount = 0;
                
                foreach ($words as $word) {
                    if (in_array(strtolower($word), $englishWords)) {
                        $englishWordCount++;
                    }
                }
                
                $confidence = count($words) > 0 ? ($englishWordCount / count($words)) * 100 : 0;
                // Boost confidence if we have many common English words
                if ($confidence > 15) {
                    $confidence = min(100, $confidence * 3);
                }
                break;
        }
        
        return [
            'confidence' => round($confidence, 2),
            'detected_language' => $detectedLanguage,
        ];
    }

    /**
     * Clean and normalize text content
     */
    public function cleanText(string $text): string
    {
        // Remove HTML tags
        $text = strip_tags($text);
        
        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Trim whitespace
        $text = trim($text);
        
        return $text;
    }

    /**
     * Calculate word count
     */
    public function getWordCount(string $text): int
    {
        $cleanText = $this->cleanText($text);
        if (empty($cleanText)) {
            return 0;
        }
        
        // Use a more precise word counting method
        $words = $this->getWords($cleanText);
        return count($words);
    }

    /**
     * Calculate character count (excluding spaces)
     */
    public function getCharacterCount(string $text, bool $includeSpaces = false): int
    {
        $cleanText = $this->cleanText($text);
        
        if ($includeSpaces) {
            return mb_strlen($cleanText);
        }
        
        return mb_strlen(preg_replace('/\s/', '', $cleanText));
    }

    /**
     * Get sentences from text
     */
    public function getSentences(string $text): array
    {
        $cleanText = $this->cleanText($text);
        
        // Split by sentence-ending punctuation
        $sentences = preg_split('/[.!?]+/', $cleanText, -1, PREG_SPLIT_NO_EMPTY);
        
        return array_map('trim', $sentences);
    }

    /**
     * Calculate average sentence length
     */
    public function getAverageSentenceLength(string $text): float
    {
        $sentences = $this->getSentences($text);
        $sentenceCount = count($sentences);
        
        if ($sentenceCount === 0) {
            return 0;
        }
        
        $totalWords = 0;
        foreach ($sentences as $sentence) {
            $totalWords += $this->getWordCount($sentence);
        }
        
        return $totalWords / $sentenceCount;
    }

    /**
     * Get paragraphs from text
     */
    public function getParagraphs(string $text): array
    {
        $cleanText = $this->cleanText($text);
        
        // Split by double line breaks or paragraph tags
        $paragraphs = preg_split('/\n\s*\n|\r\n\s*\r\n/', $cleanText, -1, PREG_SPLIT_NO_EMPTY);
        
        return array_map('trim', $paragraphs);
    }

    /**
     * Extract words from text
     */
    public function getWords(string $text): array
    {
        $cleanText = $this->cleanText($text);
        
        // Remove punctuation first, then extract words
        $cleanText = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $cleanText);
        
        // Extract words (alphanumeric characters only)
        preg_match_all('/\b\w+\b/u', mb_strtolower($cleanText), $matches);
        
        // Filter out empty strings
        return array_filter($matches[0] ?? [], function($word) {
            return !empty(trim($word));
        });
    }

    /**
     * Calculate keyword density
     */
    public function calculateKeywordDensity(string $text, string $keyword): float
    {
        $words = $this->getWords($text);
        $totalWords = count($words);
        
        if ($totalWords === 0) {
            return 0;
        }
        
        $keywordLower = mb_strtolower($keyword);
        $keywordCount = 0;
        
        // Handle multi-word keywords
        $keywordWords = explode(' ', $keywordLower);
        
        if (count($keywordWords) === 1) {
            // Single word keyword
            $keywordCount = array_count_values($words)[$keywordLower] ?? 0;
        } else {
            // Multi-word keyword
            $textLower = mb_strtolower($text);
            $keywordCount = substr_count($textLower, $keywordLower);
        }
        
        return ($keywordCount / $totalWords) * 100;
    }

    /**
     * Count syllables in a word (approximation for English)
     */
    public function countSyllables(string $word): int
    {
        $word = mb_strtolower(trim($word));
        
        if (mb_strlen($word) <= 3) {
            return 1;
        }
        
        // Remove common endings that don't add syllables
        $word = preg_replace('/(?:[^laeiouy]es|ed|[^laeiouy]e)$/', '', $word);
        $word = preg_replace('/^y/', '', $word);
        
        // Count vowel groups
        preg_match_all('/[aeiouy]+/', $word, $matches);
        $syllableCount = count($matches[0]);
        
        return max(1, $syllableCount);
    }

    /**
     * Calculate total syllables in text
     */
    public function getTotalSyllables(string $text): int
    {
        $words = $this->getWords($text);
        $totalSyllables = 0;
        
        foreach ($words as $word) {
            $totalSyllables += $this->countSyllables($word);
        }
        
        return $totalSyllables;
    }

    /**
     * Detect language (basic implementation)
     */
    public function detectLanguage(string $text): string
    {
        // Basic language detection based on common words
        $cleanText = mb_strtolower($this->cleanText($text));
        
        // Korean detection - more thorough check
        preg_match_all('/[\x{AC00}-\x{D7AF}]/u', $cleanText, $koreanMatches);
        $koreanCount = count($koreanMatches[0]);
        
        if ($koreanCount > 0) {
            return 'ko';
        }
        
        // Japanese detection
        if (preg_match('/[\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{4E00}-\x{9FAF}]/u', $cleanText)) {
            return 'ja';
        }
        
        // Chinese detection
        if (preg_match('/[\x{4E00}-\x{9FFF}]/u', $cleanText)) {
            return 'zh';
        }
        
        // Default to English
        return 'en';
    }

    /**
     * Calculate text density (words per 100 characters)
     */
    public function getTextDensity(string $text): float
    {
        $wordCount = $this->getWordCount($text);
        $charCount = $this->getCharacterCount($text, true);
        
        if ($charCount === 0) {
            return 0;
        }
        
        return ($wordCount / $charCount) * 100;
    }

    /**
     * Get most common words
     */
    public function getMostCommonWords(string $text, int $limit = 10): array
    {
        $words = $this->getWords($text);
        
        // Remove common stop words
        $stopWords = [
            'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by',
            'is', 'are', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had', 'do', 'does', 'did',
            'will', 'would', 'could', 'should', 'may', 'might', 'must', 'can', 'this', 'that', 'these', 'those'
        ];
        
        $filteredWords = array_filter($words, function($word) use ($stopWords) {
            return !in_array($word, $stopWords) && mb_strlen($word) > 2;
        });
        
        $wordCounts = array_count_values($filteredWords);
        arsort($wordCounts);
        
        return array_slice($wordCounts, 0, $limit, true);
    }

    /**
     * Normalize text for processing
     */
    public function normalizeText(string $text): string
    {
        $normalized = $this->cleanText($text);
        $normalized = mb_strtolower($normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        return trim($normalized);
    }

    /**
     * Analyze sentence structure
     */
    public function analyzeSentenceStructure(array $sentences): array
    {
        if (empty($sentences)) {
            return [
                'average_length' => 0,
                'length_distribution' => ['short' => 0, 'medium' => 0, 'long' => 0]
            ];
        }

        $lengths = [];
        $shortCount = 0;
        $mediumCount = 0;
        $longCount = 0;

        foreach ($sentences as $sentence) {
            $wordCount = $this->getWordCount($sentence);
            $lengths[] = $wordCount;

            if ($wordCount <= 8) {
                $shortCount++;
            } elseif ($wordCount <= 18) {
                $mediumCount++;
            } else {
                $longCount++;
            }
        }

        $averageLength = count($lengths) > 0 ? array_sum($lengths) / count($lengths) : 0;

        return [
            'average_length' => round($averageLength, 2),
            'length_distribution' => [
                'short' => $shortCount,
                'medium' => $mediumCount,
                'long' => $longCount
            ]
        ];
    }
}