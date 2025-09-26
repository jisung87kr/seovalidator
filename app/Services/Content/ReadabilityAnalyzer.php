<?php

namespace App\Services\Content;

use Illuminate\Support\Facades\Log;

/**
 * Advanced readability analysis service
 * Implements Flesch-Kincaid and other readability algorithms
 * to assess content accessibility and user experience
 */
class ReadabilityAnalyzer
{
    /**
     * Analyze text readability using multiple algorithms
     */
    public function analyze(string $textContent, string $html = '', array $options = []): array
    {
        Log::debug('Starting readability analysis', [
            'text_length' => strlen($textContent),
            'word_count' => str_word_count($textContent)
        ]);

        $startTime = microtime(true);

        try {
            // Basic text metrics
            $metrics = $this->calculateBasicMetrics($textContent);

            // Flesch Reading Ease Score
            $fleschEase = $this->calculateFleschReadingEase($metrics);

            // Flesch-Kincaid Grade Level
            $fleschKincaidGrade = $this->calculateFleschKincaidGrade($metrics);

            // Automated Readability Index (ARI)
            $ariScore = $this->calculateAutomatedReadabilityIndex($metrics);

            // Coleman-Liau Index
            $colemanLiauIndex = $this->calculateColemanLiauIndex($metrics);

            // SMOG Index
            $smogIndex = $this->calculateSMOGIndex($textContent, $metrics);

            // Gunning Fog Index
            $gunningFogIndex = $this->calculateGunningFogIndex($textContent, $metrics);

            // Additional readability factors
            $structuralAnalysis = $this->analyzeStructuralReadability($html, $textContent);
            $vocabularyAnalysis = $this->analyzeVocabularyComplexity($textContent);
            $sentenceAnalysis = $this->analyzeSentenceComplexity($textContent);

            // Calculate overall readability score
            $overallScore = $this->calculateOverallReadabilityScore([
                'flesch_ease' => $fleschEase,
                'flesch_kincaid' => $fleschKincaidGrade,
                'ari' => $ariScore,
                'coleman_liau' => $colemanLiauIndex,
                'smog' => $smogIndex,
                'gunning_fog' => $gunningFogIndex,
                'structural' => $structuralAnalysis,
                'vocabulary' => $vocabularyAnalysis,
                'sentence' => $sentenceAnalysis
            ]);

            // Generate readability recommendations
            $recommendations = $this->generateReadabilityRecommendations([
                'overall_score' => $overallScore,
                'flesch_ease' => $fleschEase,
                'flesch_kincaid' => $fleschKincaidGrade,
                'metrics' => $metrics,
                'structural' => $structuralAnalysis,
                'vocabulary' => $vocabularyAnalysis,
                'sentence' => $sentenceAnalysis
            ]);

            $analysis = [
                'analyzed_at' => date('c'),
                'analysis_duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'overall_score' => $overallScore,
                'reading_level' => $this->determineReadingLevel($overallScore),
                'target_audience' => $this->determineTargetAudience($overallScore),
                'basic_metrics' => $metrics,
                'flesch_reading_ease' => $fleschEase,
                'flesch_kincaid_grade' => $fleschKincaidGrade,
                'automated_readability_index' => $ariScore,
                'coleman_liau_index' => $colemanLiauIndex,
                'smog_index' => $smogIndex,
                'gunning_fog_index' => $gunningFogIndex,
                'structural_analysis' => $structuralAnalysis,
                'vocabulary_analysis' => $vocabularyAnalysis,
                'sentence_analysis' => $sentenceAnalysis,
                'recommendations' => $recommendations,
                'readability_insights' => $this->generateReadabilityInsights($overallScore, $metrics, $fleschEase)
            ];

            Log::info('Readability analysis completed', [
                'overall_score' => $overallScore,
                'flesch_ease' => $fleschEase['score'],
                'flesch_kincaid_grade' => $fleschKincaidGrade['grade'],
                'reading_level' => $analysis['reading_level']
            ]);

            return $analysis;

        } catch (\Exception $e) {
            Log::error('Readability analysis failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Calculate basic text metrics
     */
    private function calculateBasicMetrics(string $text): array
    {
        $sentences = $this->countSentences($text);
        $words = str_word_count($text);
        $syllables = $this->countSyllables($text);
        $characters = strlen(preg_replace('/\s/', '', $text));
        $paragraphs = $this->countParagraphs($text);

        return [
            'total_sentences' => $sentences,
            'total_words' => $words,
            'total_syllables' => $syllables,
            'total_characters' => $characters,
            'total_paragraphs' => $paragraphs,
            'avg_words_per_sentence' => $sentences > 0 ? round($words / $sentences, 2) : 0,
            'avg_syllables_per_word' => $words > 0 ? round($syllables / $words, 2) : 0,
            'avg_characters_per_word' => $words > 0 ? round($characters / $words, 2) : 0,
            'avg_sentences_per_paragraph' => $paragraphs > 0 ? round($sentences / $paragraphs, 2) : 0
        ];
    }

    /**
     * Calculate Flesch Reading Ease Score
     */
    private function calculateFleschReadingEase(array $metrics): array
    {
        $avgSentenceLength = $metrics['avg_words_per_sentence'];
        $avgSyllablesPerWord = $metrics['avg_syllables_per_word'];

        $score = 206.835 - (1.015 * $avgSentenceLength) - (84.6 * $avgSyllablesPerWord);
        $score = max(0, min(100, $score));

        return [
            'score' => round($score, 1),
            'level' => $this->getFleschReadingLevel($score),
            'description' => $this->getFleschDescription($score),
            'school_level' => $this->getFleschSchoolLevel($score),
            'notes' => $this->getFleschNotes($score)
        ];
    }

    /**
     * Calculate Flesch-Kincaid Grade Level
     */
    private function calculateFleschKincaidGrade(array $metrics): array
    {
        $avgSentenceLength = $metrics['avg_words_per_sentence'];
        $avgSyllablesPerWord = $metrics['avg_syllables_per_word'];

        $grade = (0.39 * $avgSentenceLength) + (11.8 * $avgSyllablesPerWord) - 15.59;
        $grade = max(0, $grade);

        return [
            'grade' => round($grade, 1),
            'level' => $this->getGradeLevel($grade),
            'description' => $this->getGradeLevelDescription($grade),
            'recommended_age' => $this->getRecommendedAge($grade)
        ];
    }

    /**
     * Calculate Automated Readability Index (ARI)
     */
    private function calculateAutomatedReadabilityIndex(array $metrics): array
    {
        if ($metrics['total_words'] === 0 || $metrics['total_sentences'] === 0) {
            return ['score' => 0, 'grade' => 0, 'level' => 'Unable to calculate'];
        }

        $score = 4.71 * ($metrics['total_characters'] / $metrics['total_words']) +
                0.5 * ($metrics['total_words'] / $metrics['total_sentences']) - 21.43;

        $score = max(0, $score);

        return [
            'score' => round($score, 1),
            'grade' => round($score),
            'level' => $this->getGradeLevel($score),
            'description' => 'Automated Readability Index grade level'
        ];
    }

    /**
     * Calculate Coleman-Liau Index
     */
    private function calculateColemanLiauIndex(array $metrics): array
    {
        if ($metrics['total_words'] === 0) {
            return ['score' => 0, 'grade' => 0, 'level' => 'Unable to calculate'];
        }

        $avgCharactersPer100Words = ($metrics['total_characters'] / $metrics['total_words']) * 100;
        $avgSentencesPer100Words = ($metrics['total_sentences'] / $metrics['total_words']) * 100;

        $score = 0.0588 * $avgCharactersPer100Words - 0.296 * $avgSentencesPer100Words - 15.8;
        $score = max(0, $score);

        return [
            'score' => round($score, 1),
            'grade' => round($score),
            'level' => $this->getGradeLevel($score),
            'description' => 'Coleman-Liau Index grade level'
        ];
    }

    /**
     * Calculate SMOG Index
     */
    private function calculateSMOGIndex(string $text, array $metrics): array
    {
        $complexWords = $this->countComplexWords($text);

        if ($metrics['total_sentences'] === 0) {
            return ['score' => 0, 'grade' => 0, 'level' => 'Unable to calculate'];
        }

        // SMOG formula: 1.043 * sqrt(complex_words * (30 / sentences)) + 3.1291
        $score = 1.043 * sqrt($complexWords * (30 / $metrics['total_sentences'])) + 3.1291;
        $score = max(0, $score);

        return [
            'score' => round($score, 1),
            'grade' => round($score),
            'level' => $this->getGradeLevel($score),
            'complex_words_count' => $complexWords,
            'description' => 'SMOG (Simple Measure of Gobbledygook) grade level'
        ];
    }

    /**
     * Calculate Gunning Fog Index
     */
    private function calculateGunningFogIndex(string $text, array $metrics): array
    {
        $complexWords = $this->countComplexWords($text);

        if ($metrics['total_sentences'] === 0 || $metrics['total_words'] === 0) {
            return ['score' => 0, 'grade' => 0, 'level' => 'Unable to calculate'];
        }

        $avgSentenceLength = $metrics['total_words'] / $metrics['total_sentences'];
        $complexWordPercentage = ($complexWords / $metrics['total_words']) * 100;

        $score = 0.4 * ($avgSentenceLength + $complexWordPercentage);
        $score = max(0, $score);

        return [
            'score' => round($score, 1),
            'grade' => round($score),
            'level' => $this->getGradeLevel($score),
            'complex_words_percentage' => round($complexWordPercentage, 1),
            'description' => 'Gunning Fog Index grade level'
        ];
    }

    /**
     * Analyze structural readability factors
     */
    private function analyzeStructuralReadability(string $html, string $text): array
    {
        $score = 100; // Start with perfect score

        // Paragraph analysis
        $paragraphCount = substr_count($html, '<p');
        $avgWordsPerParagraph = $paragraphCount > 0 ? str_word_count($text) / $paragraphCount : 0;

        if ($avgWordsPerParagraph > 150) {
            $score -= 20; // Long paragraphs reduce readability
        } elseif ($avgWordsPerParagraph > 100) {
            $score -= 10;
        }

        // List usage (improves readability)
        $listCount = substr_count($html, '<ul') + substr_count($html, '<ol');
        if ($listCount > 0) {
            $score += min(15, $listCount * 3);
        }

        // Heading usage (improves scannability)
        $headingCount = 0;
        for ($i = 1; $i <= 6; $i++) {
            $headingCount += substr_count($html, "<h{$i}");
        }
        if ($headingCount > 0) {
            $score += min(10, $headingCount * 2);
        }

        // Bold/strong text (improves scannability)
        $boldCount = substr_count($html, '<strong') + substr_count($html, '<b>');
        if ($boldCount > 0) {
            $score += min(5, $boldCount);
        }

        return [
            'score' => max(0, min(100, round($score, 1))),
            'paragraph_count' => $paragraphCount,
            'avg_words_per_paragraph' => round($avgWordsPerParagraph, 1),
            'list_count' => $listCount,
            'heading_count' => $headingCount,
            'bold_elements' => $boldCount,
            'structural_features' => $this->identifyStructuralFeatures($html)
        ];
    }

    /**
     * Analyze vocabulary complexity
     */
    private function analyzeVocabularyComplexity(string $text): array
    {
        $words = str_word_count($text, 1);
        $totalWords = count($words);

        if ($totalWords === 0) {
            return ['score' => 0, 'complexity_level' => 'Cannot analyze'];
        }

        $complexWords = 0;
        $longWords = 0;
        $uniqueWords = [];
        $wordLengths = [];

        foreach ($words as $word) {
            $word = strtolower(trim($word, '.,!?;:"()[]{}'));
            $wordLength = strlen($word);
            $syllableCount = $this->countSyllablesInWord($word);

            $wordLengths[] = $wordLength;
            $uniqueWords[$word] = true;

            if ($syllableCount >= 3) {
                $complexWords++;
            }

            if ($wordLength >= 7) {
                $longWords++;
            }
        }

        $avgWordLength = array_sum($wordLengths) / count($wordLengths);
        $vocabularyDiversity = count($uniqueWords) / $totalWords;
        $complexWordRatio = $complexWords / $totalWords;
        $longWordRatio = $longWords / $totalWords;

        // Calculate vocabulary complexity score (lower is better for readability)
        $score = 100;
        $score -= ($complexWordRatio * 100); // Penalty for complex words
        $score -= ($longWordRatio * 50); // Penalty for long words
        $score += ($vocabularyDiversity * 20); // Bonus for vocabulary diversity (up to a point)

        $score = max(0, min(100, $score));

        return [
            'score' => round($score, 1),
            'complexity_level' => $this->getVocabularyComplexityLevel($score),
            'total_words' => $totalWords,
            'unique_words' => count($uniqueWords),
            'complex_words' => $complexWords,
            'long_words' => $longWords,
            'avg_word_length' => round($avgWordLength, 1),
            'vocabulary_diversity' => round($vocabularyDiversity, 3),
            'complex_word_ratio' => round($complexWordRatio, 3),
            'long_word_ratio' => round($longWordRatio, 3)
        ];
    }

    /**
     * Analyze sentence complexity
     */
    private function analyzeSentenceComplexity(string $text): array
    {
        $sentences = $this->splitIntoSentences($text);
        $totalSentences = count($sentences);

        if ($totalSentences === 0) {
            return ['score' => 0, 'complexity_level' => 'Cannot analyze'];
        }

        $sentenceLengths = [];
        $complexSentences = 0;
        $veryLongSentences = 0;

        foreach ($sentences as $sentence) {
            $words = str_word_count($sentence);
            $sentenceLengths[] = $words;

            if ($words > 25) {
                $complexSentences++;
            }

            if ($words > 35) {
                $veryLongSentences++;
            }
        }

        $avgSentenceLength = array_sum($sentenceLengths) / count($sentenceLengths);
        $complexSentenceRatio = $complexSentences / $totalSentences;
        $veryLongSentenceRatio = $veryLongSentences / $totalSentences;

        // Calculate sentence complexity score
        $score = 100;
        $score -= ($complexSentenceRatio * 50); // Penalty for complex sentences
        $score -= ($veryLongSentenceRatio * 30); // Extra penalty for very long sentences

        if ($avgSentenceLength > 30) {
            $score -= 20;
        } elseif ($avgSentenceLength > 20) {
            $score -= 10;
        }

        $score = max(0, min(100, $score));

        return [
            'score' => round($score, 1),
            'complexity_level' => $this->getSentenceComplexityLevel($score),
            'total_sentences' => $totalSentences,
            'avg_sentence_length' => round($avgSentenceLength, 1),
            'complex_sentences' => $complexSentences,
            'very_long_sentences' => $veryLongSentences,
            'complex_sentence_ratio' => round($complexSentenceRatio, 3),
            'sentence_length_distribution' => $this->analyzeSentenceLengthDistribution($sentenceLengths)
        ];
    }

    /**
     * Calculate overall readability score
     */
    private function calculateOverallReadabilityScore(array $analyses): array
    {
        $weights = [
            'flesch_ease' => 0.25,      // 25%
            'flesch_kincaid' => 0.20,   // 20%
            'structural' => 0.20,       // 20%
            'vocabulary' => 0.15,       // 15%
            'sentence' => 0.15,         // 15%
            'ari' => 0.05              // 5%
        ];

        // Convert scores to 0-100 scale
        $normalizedScores = [
            'flesch_ease' => $analyses['flesch_ease']['score'],
            'flesch_kincaid' => $this->normalizeGradeToScore($analyses['flesch_kincaid']['grade']),
            'structural' => $analyses['structural']['score'],
            'vocabulary' => $analyses['vocabulary']['score'],
            'sentence' => $analyses['sentence']['score'],
            'ari' => $this->normalizeGradeToScore($analyses['ari']['score'])
        ];

        $totalScore = 0;
        foreach ($normalizedScores as $type => $score) {
            $weight = $weights[$type] ?? 0;
            $totalScore += $score * $weight;
        }

        return [
            'overall' => round($totalScore, 1),
            'components' => $normalizedScores,
            'weights' => $weights,
            'grade' => $this->getReadabilityGrade($totalScore)
        ];
    }

    // Helper methods

    private function countSentences(string $text): int
    {
        return preg_match_all('/[.!?]+/', $text);
    }

    private function countSyllables(string $text): int
    {
        $words = str_word_count($text, 1);
        $totalSyllables = 0;

        foreach ($words as $word) {
            $totalSyllables += $this->countSyllablesInWord($word);
        }

        return $totalSyllables;
    }

    private function countSyllablesInWord(string $word): int
    {
        $word = strtolower(trim($word, '.,!?;:"()[]{}'));

        if (strlen($word) <= 3) {
            return 1;
        }

        // Count vowel groups
        $vowels = 'aeiouy';
        $syllableCount = 0;
        $prevWasVowel = false;

        for ($i = 0; $i < strlen($word); $i++) {
            $isVowel = strpos($vowels, $word[$i]) !== false;

            if ($isVowel && !$prevWasVowel) {
                $syllableCount++;
            }

            $prevWasVowel = $isVowel;
        }

        // Handle silent 'e'
        if (substr($word, -1) === 'e' && $syllableCount > 1) {
            $syllableCount--;
        }

        return max(1, $syllableCount);
    }

    private function countParagraphs(string $text): int
    {
        return count(array_filter(explode("\n", $text), fn($p) => trim($p) !== ''));
    }

    private function countComplexWords(string $text): int
    {
        $words = str_word_count($text, 1);
        $complexWords = 0;

        foreach ($words as $word) {
            if ($this->countSyllablesInWord($word) >= 3) {
                $complexWords++;
            }
        }

        return $complexWords;
    }

    private function splitIntoSentences(string $text): array
    {
        return array_filter(
            preg_split('/[.!?]+/', $text),
            fn($sentence) => trim($sentence) !== ''
        );
    }

    private function getFleschReadingLevel(float $score): string
    {
        if ($score >= 90) return 'Very Easy';
        if ($score >= 80) return 'Easy';
        if ($score >= 70) return 'Fairly Easy';
        if ($score >= 60) return 'Standard';
        if ($score >= 50) return 'Fairly Difficult';
        if ($score >= 30) return 'Difficult';
        return 'Very Difficult';
    }

    private function getFleschDescription(float $score): string
    {
        if ($score >= 90) return 'Easily understood by an average 11-year-old student';
        if ($score >= 80) return 'Easily understood by 13- to 15-year-old students';
        if ($score >= 70) return 'Easily understood by 16- to 17-year-old students';
        if ($score >= 60) return 'Easily understood by 18- to 19-year-old students';
        if ($score >= 50) return 'Understood by 20- to 21-year-old students';
        if ($score >= 30) return 'Understood by university graduates';
        return 'Best understood by university graduates';
    }

    private function getFleschSchoolLevel(float $score): string
    {
        if ($score >= 90) return '5th grade';
        if ($score >= 80) return '6th grade';
        if ($score >= 70) return '7th grade';
        if ($score >= 60) return '8th & 9th grade';
        if ($score >= 50) return '10th to 12th grade';
        if ($score >= 30) return 'College level';
        return 'Graduate level';
    }

    private function getFleschNotes(float $score): string
    {
        if ($score >= 70) return 'Suitable for general audiences and web content';
        if ($score >= 60) return 'Suitable for most adult audiences';
        if ($score >= 50) return 'May be challenging for some readers';
        return 'Consider simplifying for broader accessibility';
    }

    private function getGradeLevel(float $grade): string
    {
        if ($grade <= 6) return 'Elementary';
        if ($grade <= 8) return 'Middle School';
        if ($grade <= 12) return 'High School';
        if ($grade <= 16) return 'College';
        return 'Graduate';
    }

    private function getGradeLevelDescription(float $grade): string
    {
        $gradeInt = (int)round($grade);
        if ($gradeInt <= 12) {
            return "Grade {$gradeInt} reading level";
        } elseif ($gradeInt <= 16) {
            return "College level (year " . ($gradeInt - 12) . ")";
        } else {
            return "Graduate level";
        }
    }

    private function getRecommendedAge(float $grade): string
    {
        $age = (int)round($grade) + 5; // Grade + 5 years typically
        return "Age {$age}+";
    }

    private function identifyStructuralFeatures(string $html): array
    {
        return [
            'has_lists' => (substr_count($html, '<ul') + substr_count($html, '<ol')) > 0,
            'has_headings' => preg_match('/<h[1-6]/', $html) > 0,
            'has_bold_text' => (substr_count($html, '<strong') + substr_count($html, '<b>')) > 0,
            'has_italic_text' => (substr_count($html, '<em') + substr_count($html, '<i>')) > 0,
            'has_tables' => substr_count($html, '<table') > 0,
            'has_blockquotes' => substr_count($html, '<blockquote') > 0
        ];
    }

    private function getVocabularyComplexityLevel(float $score): string
    {
        if ($score >= 80) return 'Simple';
        if ($score >= 60) return 'Moderate';
        if ($score >= 40) return 'Complex';
        return 'Very Complex';
    }

    private function getSentenceComplexityLevel(float $score): string
    {
        if ($score >= 80) return 'Simple';
        if ($score >= 60) return 'Moderate';
        if ($score >= 40) return 'Complex';
        return 'Very Complex';
    }

    private function analyzeSentenceLengthDistribution(array $lengths): array
    {
        $short = count(array_filter($lengths, fn($l) => $l <= 10));
        $medium = count(array_filter($lengths, fn($l) => $l > 10 && $l <= 20));
        $long = count(array_filter($lengths, fn($l) => $l > 20 && $l <= 30));
        $veryLong = count(array_filter($lengths, fn($l) => $l > 30));

        return [
            'short_sentences' => $short,
            'medium_sentences' => $medium,
            'long_sentences' => $long,
            'very_long_sentences' => $veryLong
        ];
    }

    private function normalizeGradeToScore(float $grade): float
    {
        // Convert grade level to 0-100 score (lower grade = higher score)
        return max(0, min(100, 100 - ($grade * 4)));
    }

    private function getReadabilityGrade(float $score): string
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

    private function determineReadingLevel(array $overallScore): string
    {
        $score = $overallScore['overall'];

        if ($score >= 85) return 'Very Easy to Read';
        if ($score >= 70) return 'Easy to Read';
        if ($score >= 60) return 'Fairly Easy to Read';
        if ($score >= 50) return 'Standard Difficulty';
        if ($score >= 40) return 'Fairly Difficult';
        if ($score >= 30) return 'Difficult';
        return 'Very Difficult';
    }

    private function determineTargetAudience(array $overallScore): string
    {
        $score = $overallScore['overall'];

        if ($score >= 80) return 'General public, ages 13+';
        if ($score >= 70) return 'High school students and adults';
        if ($score >= 60) return 'College-level readers';
        if ($score >= 50) return 'University graduates';
        return 'Subject matter experts';
    }

    private function generateReadabilityRecommendations(array $analysisData): array
    {
        $recommendations = [];
        $overallScore = $analysisData['overall_score']['overall'];
        $fleschEase = $analysisData['flesch_ease'];
        $metrics = $analysisData['metrics'];

        // Overall readability issues
        if ($overallScore < 60) {
            $recommendations[] = [
                'type' => 'warning',
                'category' => 'readability',
                'message' => 'Content readability is below recommended levels',
                'impact' => 'high',
                'fix' => 'Consider simplifying language, shortening sentences, and improving structure'
            ];
        }

        // Sentence length issues
        if ($metrics['avg_words_per_sentence'] > 25) {
            $recommendations[] = [
                'type' => 'error',
                'category' => 'sentence_length',
                'message' => 'Sentences are too long',
                'impact' => 'high',
                'fix' => "Average sentence length is {$metrics['avg_words_per_sentence']} words. Aim for 15-20 words per sentence",
                'current_value' => $metrics['avg_words_per_sentence'],
                'recommended_value' => '15-20 words'
            ];
        } elseif ($metrics['avg_words_per_sentence'] > 20) {
            $recommendations[] = [
                'type' => 'warning',
                'category' => 'sentence_length',
                'message' => 'Sentences could be shorter',
                'impact' => 'medium',
                'fix' => "Consider breaking down longer sentences. Current average: {$metrics['avg_words_per_sentence']} words"
            ];
        }

        // Flesch Reading Ease issues
        if ($fleschEase['score'] < 50) {
            $recommendations[] = [
                'type' => 'warning',
                'category' => 'flesch_score',
                'message' => 'Content is difficult to read',
                'impact' => 'high',
                'fix' => "Flesch score: {$fleschEase['score']}. Use simpler words and shorter sentences to improve accessibility",
                'current_score' => $fleschEase['score'],
                'recommended_score' => '60+'
            ];
        }

        // Vocabulary complexity issues
        $vocabularyAnalysis = $analysisData['vocabulary'];
        if ($vocabularyAnalysis['score'] < 60) {
            $recommendations[] = [
                'type' => 'suggestion',
                'category' => 'vocabulary',
                'message' => 'Vocabulary is complex',
                'impact' => 'medium',
                'fix' => "Replace complex words with simpler alternatives where possible. Complex word ratio: {$vocabularyAnalysis['complex_word_ratio']}"
            ];
        }

        // Structural improvements
        $structuralAnalysis = $analysisData['structural'];
        if ($structuralAnalysis['score'] < 70) {
            if ($structuralAnalysis['avg_words_per_paragraph'] > 100) {
                $recommendations[] = [
                    'type' => 'suggestion',
                    'category' => 'structure',
                    'message' => 'Paragraphs are too long',
                    'impact' => 'medium',
                    'fix' => "Average paragraph length: {$structuralAnalysis['avg_words_per_paragraph']} words. Aim for 50-100 words per paragraph"
                ];
            }

            if ($structuralAnalysis['heading_count'] === 0) {
                $recommendations[] = [
                    'type' => 'warning',
                    'category' => 'structure',
                    'message' => 'No headings found',
                    'impact' => 'medium',
                    'fix' => 'Add headings (H2, H3, etc.) to break up content and improve scannability'
                ];
            }

            if ($structuralAnalysis['list_count'] === 0) {
                $recommendations[] = [
                    'type' => 'suggestion',
                    'category' => 'structure',
                    'message' => 'Consider adding lists',
                    'impact' => 'low',
                    'fix' => 'Use bullet points or numbered lists to present information more clearly'
                ];
            }
        }

        return $recommendations;
    }

    private function generateReadabilityInsights(array $overallScore, array $metrics, array $fleschEase): array
    {
        $insights = [];

        if ($overallScore['overall'] >= 80) {
            $insights[] = 'Content is highly readable and accessible to most audiences';
        } elseif ($overallScore['overall'] >= 60) {
            $insights[] = 'Content has good readability with room for improvement';
        } else {
            $insights[] = 'Content readability needs significant improvement';
        }

        if ($fleschEase['score'] >= 70) {
            $insights[] = 'Flesch Reading Ease score indicates content is suitable for general audiences';
        } elseif ($fleschEase['score'] >= 50) {
            $insights[] = 'Content may be challenging for some readers';
        } else {
            $insights[] = 'Content is quite difficult and may require subject matter expertise';
        }

        if ($metrics['avg_words_per_sentence'] > 25) {
            $insights[] = 'Long sentences significantly impact readability';
        }

        return $insights;
    }
}