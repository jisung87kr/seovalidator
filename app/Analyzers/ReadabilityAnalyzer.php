<?php

namespace App\Analyzers;

use App\DTOs\CrawlResult;
use App\Utils\TextProcessor;
use DOMDocument;
use DOMXPath;

class ReadabilityAnalyzer
{
    /**
     * Analyze text readability using multiple metrics
     */
    public function analyze(CrawlResult $crawlResult): array
    {
        if (!$crawlResult->isSuccessful() || empty($crawlResult->getContent())) {
            return $this->getEmptyResult();
        }

        $content = $crawlResult->getContent();
        $dom = new DOMDocument();
        @$dom->loadHTML($content);
        $xpath = new DOMXPath($dom);

        // Extract text content
        $bodyText = $this->extractBodyText($xpath);

        if (empty($bodyText)) {
            return $this->getEmptyResult();
        }

        // Basic text statistics
        $wordCount = TextProcessor::getWordCount($bodyText);
        $sentences = TextProcessor::getSentences($bodyText);
        $sentenceCount = count($sentences);
        $syllableCount = TextProcessor::getTotalSyllables($bodyText);

        if ($sentenceCount === 0 || $wordCount === 0) {
            return $this->getEmptyResult();
        }

        // Calculate average metrics
        $avgWordsPerSentence = $wordCount / $sentenceCount;
        $avgSyllablesPerWord = $syllableCount / $wordCount;

        return [
            'flesch_kincaid_grade' => $this->calculateFleschKincaidGrade($avgWordsPerSentence, $avgSyllablesPerWord),
            'flesch_reading_ease' => $this->calculateFleschReadingEase($avgWordsPerSentence, $avgSyllablesPerWord),
            'gunning_fog_index' => $this->calculateGunningFogIndex($bodyText, $sentenceCount, $wordCount),
            'smog_index' => $this->calculateSmogIndex($bodyText, $sentenceCount),
            'coleman_liau_index' => $this->calculateColemanLiauIndex($bodyText),
            'automated_readability_index' => $this->calculateAutomatedReadabilityIndex($wordCount, $sentenceCount, $bodyText),
            'text_statistics' => [
                'word_count' => $wordCount,
                'sentence_count' => $sentenceCount,
                'syllable_count' => $syllableCount,
                'character_count' => TextProcessor::getCharacterCount($bodyText),
                'avg_words_per_sentence' => round($avgWordsPerSentence, 2),
                'avg_syllables_per_word' => round($avgSyllablesPerWord, 2),
                'avg_sentence_length' => TextProcessor::getAverageSentenceLength($bodyText),
            ],
            'readability_grade' => $this->determineReadabilityGrade($avgWordsPerSentence, $avgSyllablesPerWord),
            'complexity_analysis' => $this->analyzeComplexity($bodyText),
            'recommendations' => $this->generateRecommendations($avgWordsPerSentence, $avgSyllablesPerWord, $wordCount),
        ];
    }

    /**
     * Extract body text content
     */
    private function extractBodyText(DOMXPath $xpath): string
    {
        $bodyNodes = $xpath->query('//body//text()[not(ancestor::script) and not(ancestor::style) and not(ancestor::noscript)]');
        $text = '';
        
        foreach ($bodyNodes as $node) {
            $text .= ' ' . $node->textContent;
        }
        
        return TextProcessor::cleanText($text);
    }

    /**
     * Calculate Flesch-Kincaid Grade Level
     */
    private function calculateFleschKincaidGrade(float $avgWordsPerSentence, float $avgSyllablesPerWord): float
    {
        return round((0.39 * $avgWordsPerSentence) + (11.8 * $avgSyllablesPerWord) - 15.59, 1);
    }

    /**
     * Calculate Flesch Reading Ease Score
     */
    private function calculateFleschReadingEase(float $avgWordsPerSentence, float $avgSyllablesPerWord): float
    {
        return round(206.835 - (1.015 * $avgWordsPerSentence) - (84.6 * $avgSyllablesPerWord), 1);
    }

    /**
     * Calculate Gunning Fog Index
     */
    private function calculateGunningFogIndex(string $text, int $sentenceCount, int $wordCount): float
    {
        $complexWords = $this->countComplexWords($text);
        $percentageComplexWords = ($complexWords / $wordCount) * 100;
        
        return round(0.4 * (($wordCount / $sentenceCount) + $percentageComplexWords), 1);
    }

    /**
     * Calculate SMOG Index
     */
    private function calculateSmogIndex(string $text, int $sentenceCount): float
    {
        $complexWords = $this->countComplexWords($text);
        
        // SMOG requires at least 30 sentences for accuracy
        if ($sentenceCount < 30) {
            // Estimate based on available sentences
            $estimatedComplexWords = ($complexWords / $sentenceCount) * 30;
            return round(1.0430 * sqrt($estimatedComplexWords) + 3.1291, 1);
        }
        
        return round(1.0430 * sqrt($complexWords * (30 / $sentenceCount)) + 3.1291, 1);
    }

    /**
     * Calculate Coleman-Liau Index
     */
    private function calculateColemanLiauIndex(string $text): float
    {
        $wordCount = TextProcessor::getWordCount($text);
        $sentenceCount = count(TextProcessor::getSentences($text));
        $characterCount = TextProcessor::getCharacterCount($text);
        
        if ($wordCount === 0) {
            return 0;
        }
        
        $avgCharsPerWord = ($characterCount / $wordCount) * 100;
        $avgSentencesPer100Words = ($sentenceCount / $wordCount) * 100;
        
        return round((0.0588 * $avgCharsPerWord) - (0.296 * $avgSentencesPer100Words) - 15.8, 1);
    }

    /**
     * Calculate Automated Readability Index (ARI)
     */
    private function calculateAutomatedReadabilityIndex(int $wordCount, int $sentenceCount, string $text): float
    {
        $characterCount = TextProcessor::getCharacterCount($text);
        
        if ($wordCount === 0 || $sentenceCount === 0) {
            return 0;
        }
        
        return round((4.71 * ($characterCount / $wordCount)) + (0.5 * ($wordCount / $sentenceCount)) - 21.43, 1);
    }

    /**
     * Count complex words (3+ syllables)
     */
    private function countComplexWords(string $text): int
    {
        $words = TextProcessor::getWords($text);
        $complexCount = 0;
        
        foreach ($words as $word) {
            if (TextProcessor::countSyllables($word) >= 3) {
                $complexCount++;
            }
        }
        
        return $complexCount;
    }

    /**
     * Determine overall readability grade
     */
    private function determineReadabilityGrade(float $avgWordsPerSentence, float $avgSyllablesPerWord): string
    {
        $fleschScore = $this->calculateFleschReadingEase($avgWordsPerSentence, $avgSyllablesPerWord);
        
        if ($fleschScore >= 90) {
            return 'Very Easy (5th grade)';
        } elseif ($fleschScore >= 80) {
            return 'Easy (6th grade)';
        } elseif ($fleschScore >= 70) {
            return 'Fairly Easy (7th grade)';
        } elseif ($fleschScore >= 60) {
            return 'Standard (8th-9th grade)';
        } elseif ($fleschScore >= 50) {
            return 'Fairly Difficult (10th-12th grade)';
        } elseif ($fleschScore >= 30) {
            return 'Difficult (College level)';
        } else {
            return 'Very Difficult (Graduate level)';
        }
    }

    /**
     * Analyze text complexity
     */
    private function analyzeComplexity(string $text): array
    {
        $words = TextProcessor::getWords($text);
        $totalWords = count($words);
        $sentences = TextProcessor::getSentences($text);
        
        // Analyze word length distribution
        $wordLengths = array_map('mb_strlen', $words);
        $shortWords = count(array_filter($wordLengths, fn($len) => $len <= 4));
        $mediumWords = count(array_filter($wordLengths, fn($len) => $len >= 5 && $len <= 8));
        $longWords = count(array_filter($wordLengths, fn($len) => $len >= 9));
        
        // Analyze sentence length distribution
        $sentenceLengths = array_map(fn($s) => TextProcessor::getWordCount($s), $sentences);
        $shortSentences = count(array_filter($sentenceLengths, fn($len) => $len <= 10));
        $mediumSentences = count(array_filter($sentenceLengths, fn($len) => $len >= 11 && $len <= 20));
        $longSentences = count(array_filter($sentenceLengths, fn($len) => $len >= 21));
        
        return [
            'word_length_distribution' => [
                'short_words' => round(($shortWords / $totalWords) * 100, 1),
                'medium_words' => round(($mediumWords / $totalWords) * 100, 1),
                'long_words' => round(($longWords / $totalWords) * 100, 1),
            ],
            'sentence_length_distribution' => [
                'short_sentences' => round(($shortSentences / count($sentences)) * 100, 1),
                'medium_sentences' => round(($mediumSentences / count($sentences)) * 100, 1),
                'long_sentences' => round(($longSentences / count($sentences)) * 100, 1),
            ],
            'complex_words_percentage' => round(($this->countComplexWords($text) / $totalWords) * 100, 1),
            'passive_voice_detection' => $this->detectPassiveVoice($text),
        ];
    }

    /**
     * Basic passive voice detection
     */
    private function detectPassiveVoice(string $text): array
    {
        $sentences = TextProcessor::getSentences($text);
        $passiveSentences = 0;
        $passiveIndicators = [
            'was ', 'were ', 'been ', 'being ', 'is ', 'are ', 'am ',
            'was being', 'were being', 'has been', 'have been', 'had been'
        ];
        
        foreach ($sentences as $sentence) {
            $sentenceLower = mb_strtolower($sentence);
            foreach ($passiveIndicators as $indicator) {
                if (mb_strpos($sentenceLower, $indicator) !== false) {
                    // Simple check for past participle after the indicator
                    if (preg_match('/(?:was|were|been|being|is|are|am)\s+\w+ed\b/', $sentenceLower)) {
                        $passiveSentences++;
                        break;
                    }
                }
            }
        }
        
        $passivePercentage = count($sentences) > 0 ? ($passiveSentences / count($sentences)) * 100 : 0;
        
        return [
            'passive_sentences_count' => $passiveSentences,
            'total_sentences' => count($sentences),
            'passive_percentage' => round($passivePercentage, 1),
            'recommendation' => $passivePercentage > 25 ? 'Reduce passive voice usage' : 'Acceptable passive voice usage',
        ];
    }

    /**
     * Generate readability recommendations
     */
    private function generateRecommendations(float $avgWordsPerSentence, float $avgSyllablesPerWord, int $wordCount): array
    {
        $recommendations = [];
        
        if ($avgWordsPerSentence > 20) {
            $recommendations[] = 'Consider breaking long sentences into shorter ones (current average: ' . round($avgWordsPerSentence, 1) . ' words per sentence)';
        }
        
        if ($avgSyllablesPerWord > 1.7) {
            $recommendations[] = 'Use simpler words when possible (current average: ' . round($avgSyllablesPerWord, 2) . ' syllables per word)';
        }
        
        if ($wordCount < 300) {
            $recommendations[] = 'Consider adding more content for better SEO (current: ' . $wordCount . ' words)';
        }
        
        $complexWordsPercentage = ($this->countComplexWords('') / $wordCount) * 100;
        if ($complexWordsPercentage > 15) {
            $recommendations[] = 'Reduce complex words usage (current: ' . round($complexWordsPercentage, 1) . '% complex words)';
        }
        
        if (empty($recommendations)) {
            $recommendations[] = 'Your content has good readability!';
        }
        
        return $recommendations;
    }

    /**
     * Get empty result structure
     */
    private function getEmptyResult(): array
    {
        return [
            'flesch_kincaid_grade' => 0,
            'flesch_reading_ease' => 0,
            'gunning_fog_index' => 0,
            'smog_index' => 0,
            'coleman_liau_index' => 0,
            'automated_readability_index' => 0,
            'text_statistics' => [
                'word_count' => 0,
                'sentence_count' => 0,
                'syllable_count' => 0,
                'character_count' => 0,
                'avg_words_per_sentence' => 0,
                'avg_syllables_per_word' => 0,
                'avg_sentence_length' => 0,
            ],
            'readability_grade' => 'No content',
            'complexity_analysis' => [],
            'recommendations' => ['No content available for analysis'],
        ];
    }
}