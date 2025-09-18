<?php

namespace Tests\Unit\Utils;

use App\Utils\TextProcessor;
use PHPUnit\Framework\TestCase;

class TextProcessorTest extends TestCase
{
    private TextProcessor $textProcessor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->textProcessor = new TextProcessor();
    }

    public function test_processes_simple_html_content(): void
    {
        $html = '<html><body><h1>Test Title</h1><p>This is a test paragraph with some content.</p></body></html>';

        $result = $this->textProcessor->processText($html);

        $this->assertGreaterThan(0, $result->wordCount);
        $this->assertGreaterThan(0, $result->characterCount);
        $this->assertEquals(1, $result->sentenceCount);
        $this->assertContains($result->detectedLanguage, ['en', 'unknown']);
        $this->assertIsArray($result->wordFrequency);
    }

    public function test_detects_english_language(): void
    {
        $englishText = '<p>The quick brown fox jumps over the lazy dog. This is a test of English language detection.</p>';

        $result = $this->textProcessor->processText($englishText);

        $this->assertEquals('en', $result->detectedLanguage);
        $this->assertGreaterThan(50, $result->languageMetrics['confidence']);
    }

    public function test_detects_korean_language(): void
    {
        $koreanText = '<p>안녕하세요. 이것은 한국어 텍스트입니다. 언어 감지 테스트를 위한 문장입니다.</p>';

        $result = $this->textProcessor->processText($koreanText);

        $this->assertEquals('ko', $result->detectedLanguage);
    }

    public function test_counts_words_correctly(): void
    {
        $text = '<p>One two three four five words.</p>';

        $result = $this->textProcessor->processText($text);

        $this->assertEquals(5, $result->wordCount);
    }

    public function test_counts_sentences_correctly(): void
    {
        $text = '<p>First sentence. Second sentence! Third sentence?</p>';

        $result = $this->textProcessor->processText($text);

        $this->assertEquals(3, $result->sentenceCount);
    }

    public function test_counts_paragraphs_correctly(): void
    {
        $text = '<p>First paragraph.</p><p>Second paragraph.</p>';

        $result = $this->textProcessor->processText($text);

        $this->assertEquals(2, $result->paragraphCount);
    }

    public function test_calculates_text_density(): void
    {
        $html = '<html><head><title>Test</title></head><body><p>Content</p></body></html>';

        $result = $this->textProcessor->processText($html);

        $this->assertGreaterThan(0, $result->textDensity);
        $this->assertLessThanOrEqual(100, $result->textDensity);
    }

    public function test_removes_script_and_style_content(): void
    {
        $html = '
            <html>
                <head>
                    <style>body { color: red; }</style>
                    <script>console.log("test");</script>
                </head>
                <body>
                    <p>Visible content</p>
                </body>
            </html>
        ';

        $result = $this->textProcessor->processText($html);

        $this->assertStringNotContainsString('color: red', $result->cleanedText);
        $this->assertStringNotContainsString('console.log', $result->cleanedText);
        $this->assertStringContainsString('Visible content', $result->cleanedText);
    }

    public function test_calculates_word_frequency(): void
    {
        $text = '<p>test word test another word test</p>';

        $result = $this->textProcessor->processText($text);

        $this->assertIsArray($result->wordFrequency);
        $this->assertArrayHasKey('test', $result->wordFrequency);
        $this->assertEquals(3, $result->wordFrequency['test']);
    }

    public function test_handles_empty_content(): void
    {
        $result = $this->textProcessor->processText('');

        $this->assertEquals(0, $result->wordCount);
        $this->assertEquals(0, $result->characterCount);
        $this->assertEquals(0, $result->sentenceCount);
        $this->assertEquals('unknown', $result->detectedLanguage);
    }

    public function test_normalizes_text_properly(): void
    {
        $text = '<p>  Multiple   spaces   and   UPPERCASE  Text!  </p>';

        $result = $this->textProcessor->processText($text);

        $normalizedWords = explode(' ', $result->normalizedText);
        $this->assertNotContains('', $normalizedWords); // No empty strings
        $this->assertEquals(strtolower($result->normalizedText), $result->normalizedText);
    }

    public function test_estimates_syllables_for_english(): void
    {
        $text = '<p>Hello world</p>'; // hello = 2 syllables, world = 1 syllable

        $result = $this->textProcessor->processText($text);

        $this->assertGreaterThan(0, $result->languageMetrics['syllable_estimation']);
    }

    public function test_estimates_syllables_for_korean(): void
    {
        $koreanText = '<p>안녕하세요</p>'; // 5 syllables

        $result = $this->textProcessor->processText($koreanText);

        $this->assertEquals(5, $result->languageMetrics['syllable_estimation']);
    }

    public function test_analyzes_sentence_structure(): void
    {
        $text = '<p>Short sentence. This is a much longer sentence with more words. Medium length sentence here.</p>';

        $result = $this->textProcessor->processText($text);

        $this->assertIsArray($result->sentenceStructure);
        $this->assertArrayHasKey('average_length', $result->sentenceStructure);
        $this->assertArrayHasKey('length_distribution', $result->sentenceStructure);
        $this->assertGreaterThan(0, $result->sentenceStructure['average_length']);
    }

    public function test_calculates_language_confidence(): void
    {
        $strongEnglishText = '<p>The quick brown fox jumps over the lazy dog and runs through the forest.</p>';

        $result = $this->textProcessor->processText($strongEnglishText);

        $this->assertEquals('en', $result->detectedLanguage);
        $this->assertGreaterThan(10, $result->languageMetrics['confidence']);
    }
}