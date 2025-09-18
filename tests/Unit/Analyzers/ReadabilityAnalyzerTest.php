<?php

namespace Tests\Unit\Analyzers;

use App\Analyzers\ReadabilityAnalyzer;
use App\DTOs\TextProcessingResult;
use PHPUnit\Framework\TestCase;

class ReadabilityAnalyzerTest extends TestCase
{
    private ReadabilityAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = new ReadabilityAnalyzer();
    }

    public function test_calculates_flesch_kincaid_score(): void
    {
        $textResult = $this->createTextResult(
            'This is a simple test. Short sentences are easy to read.',
            11, // words
            2   // sentences
        );

        $result = $this->analyzer->analyze($textResult);

        $this->assertGreaterThan(0, $result->fleschKincaidScore);
        $this->assertIsFloat($result->fleschKincaidScore);
    }

    public function test_calculates_gunning_fog_index(): void
    {
        $textResult = $this->createTextResult(
            'This is a simple test with some complex multisyllabic terminology.',
            10, // words
            1   // sentences
        );

        $result = $this->analyzer->analyze($textResult);

        $this->assertGreaterThan(0, $result->gunningFogIndex);
        $this->assertIsFloat($result->gunningFogIndex);
    }

    public function test_calculates_smog_index(): void
    {
        $textResult = $this->createTextResult(
            'This is a comprehensive analysis of multifaceted terminology. Complex vocabulary requires sophisticated understanding. Educational institutions emphasize comprehension.',
            18, // words
            3   // sentences
        );

        $result = $this->analyzer->analyze($textResult);

        $this->assertGreaterThan(0, $result->smogIndex);
        $this->assertIsFloat($result->smogIndex);
    }

    public function test_calculates_average_sentence_length(): void
    {
        $textResult = $this->createTextResult(
            'Short sentence. This is a longer sentence with more words.',
            10, // words
            2   // sentences
        );

        $result = $this->analyzer->analyze($textResult);

        $this->assertEquals(5.0, $result->averageSentenceLength);
        $this->assertEquals(5.0, $result->averageWordsPerSentence);
    }

    public function test_determines_reading_level(): void
    {
        // Test easy reading level
        $easyTextResult = $this->createTextResult(
            'This is easy to read. Short words. Simple ideas.',
            9,
            3
        );

        $result = $this->analyzer->analyze($easyTextResult);
        $this->assertContains($result->readingLevel, ['very_easy', 'easy', 'fairly_easy', 'standard']);
    }

    public function test_counts_syllables_correctly(): void
    {
        $textResult = $this->createTextResult('hello world', 2, 1);

        $result = $this->analyzer->analyze($textResult);

        $this->assertGreaterThan(0, $result->totalSyllables);
        $this->assertEquals(2, $result->totalWords);
    }

    public function test_counts_complex_words(): void
    {
        $textResult = $this->createTextResult(
            'Simple words and complicated terminology with multisyllabic expressions.',
            8,
            1
        );

        $result = $this->analyzer->analyze($textResult);

        $this->assertGreaterThan(0, $result->complexWords);
        $this->assertLessThanOrEqual($result->totalWords, $result->complexWords);
    }

    public function test_generates_readability_suggestions(): void
    {
        // Test with difficult text
        $difficultTextResult = $this->createTextResult(
            'Extraordinarily complicated multifaceted terminology requiring sophisticated comprehension capabilities and advanced educational preparation.',
            12,
            1
        );

        $result = $this->analyzer->analyze($difficultTextResult);

        $this->assertIsArray($result->suggestions);
        $this->assertNotEmpty($result->suggestions);
        
        // Should suggest improvements for difficult text
        $suggestionsText = implode(' ', $result->suggestions);
        $this->assertStringContainsStringIgnoringCase('simpl', $suggestionsText);
    }

    public function test_handles_empty_content(): void
    {
        $emptyTextResult = $this->createTextResult('', 0, 0);

        $result = $this->analyzer->analyze($emptyTextResult);

        $this->assertEquals(0.0, $result->fleschKincaidScore);
        $this->assertEquals(0.0, $result->gunningFogIndex);
        $this->assertEquals(0.0, $result->smogIndex);
        $this->assertEquals(0.0, $result->averageSentenceLength);
        $this->assertEquals('unknown', $result->readingLevel);
        $this->assertNotEmpty($result->suggestions);
    }

    public function test_reading_grade_classification(): void
    {
        // Test very easy text (should be high Flesch score)
        $result = $this->analyzer->analyze($this->createTextResult('Cat sat. Dog ran.', 4, 2));
        $grade = $result->getReadingGrade();
        $this->assertIsString($grade);
        
        // The grade should be appropriate for simple text
        $this->assertContains($grade, [
            '5th Grade', '6th Grade', '7th Grade', '8th & 9th Grade',
            '10th to 12th Grade', 'College Level', 'Graduate Level'
        ]);
    }

    public function test_is_easy_to_read_method(): void
    {
        // Create text that should be easy to read
        $easyTextResult = $this->createTextResult('This is easy text. Short words work best.', 8, 2);
        $result = $this->analyzer->analyze($easyTextResult);
        
        // Check if the isEasyToRead method exists and returns boolean
        $this->assertIsBool($result->isEasyToRead());
    }

    public function test_korean_text_analysis(): void
    {
        $koreanTextResult = new TextProcessingResult(
            cleanedText: '안녕하세요. 이것은 한국어 텍스트입니다.',
            normalizedText: '안녕하세요 이것은 한국어 텍스트입니다',
            detectedLanguage: 'ko',
            wordCount: 5,
            characterCount: 20,
            sentenceCount: 2,
            paragraphCount: 1,
            textDensity: 50.0,
            wordFrequency: [],
            sentenceStructure: ['average_length' => 2.5],
            languageMetrics: ['confidence' => 90.0, 'syllable_estimation' => 10]
        );

        $result = $this->analyzer->analyze($koreanTextResult);

        $this->assertGreaterThan(0, $result->fleschKincaidScore);
        $this->assertIsString($result->readingLevel);
        $this->assertNotEmpty($result->suggestions);
    }

    public function test_complex_sentence_analysis(): void
    {
        $complexTextResult = $this->createTextResult(
            'Although the methodology was comprehensive and the results were statistically significant, the implications of these findings require further investigation through additional longitudinal studies.',
            25,
            1
        );

        $result = $this->analyzer->analyze($complexTextResult);

        // Long sentence should result in lower readability score
        $this->assertLessThan(70, $result->fleschKincaidScore);
        $this->assertEquals(25.0, $result->averageSentenceLength);
        
        // Should suggest breaking up long sentences
        $suggestionsText = implode(' ', $result->suggestions);
        $this->assertStringContainsStringIgnoringCase('sentence', $suggestionsText);
    }

    private function createTextResult(string $text, int $wordCount, int $sentenceCount): TextProcessingResult
    {
        return new TextProcessingResult(
            cleanedText: $text,
            normalizedText: strtolower($text),
            detectedLanguage: 'en',
            wordCount: $wordCount,
            characterCount: strlen($text),
            sentenceCount: $sentenceCount,
            paragraphCount: 1,
            textDensity: 50.0,
            wordFrequency: [],
            sentenceStructure: [
                'average_length' => $wordCount / max(1, $sentenceCount),
                'length_distribution' => ['short' => 0, 'medium' => 1, 'long' => 0]
            ],
            languageMetrics: ['confidence' => 80.0, 'syllable_estimation' => $wordCount * 1.5]
        );
    }
}