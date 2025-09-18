<?php

namespace Tests\Unit\Analyzers;

use App\Analyzers\KeywordAnalyzer;
use App\DTOs\CrawlResult;
use App\DTOs\TextProcessingResult;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class KeywordAnalyzerTest extends TestCase
{
    private KeywordAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = new KeywordAnalyzer();
    }

    public function test_analyzes_keyword_density(): void
    {
        $html = '<html><body><h1>SEO optimization guide</h1><p>SEO is important for optimization. Good SEO practices improve optimization results.</p></body></html>';
        $crawlResult = $this->createCrawlResult($html);
        $textResult = $this->createTextResult('seo optimization guide seo is important for optimization good seo practices improve optimization results', 13);

        $result = $this->analyzer->analyze($crawlResult, $textResult, ['seo', 'optimization']);

        $this->assertArrayHasKey('seo', $result->densityMap);
        $this->assertArrayHasKey('optimization', $result->densityMap);
        $this->assertGreaterThan(0, $result->densityMap['seo']);
        $this->assertGreaterThan(0, $result->densityMap['optimization']);
    }

    public function test_detects_keyword_stuffing(): void
    {
        // Create content with excessive keyword repetition
        $html = '<p>SEO SEO SEO SEO SEO test content SEO SEO SEO</p>';
        $crawlResult = $this->createCrawlResult($html);
        $textResult = $this->createTextResult('seo seo seo seo seo test content seo seo seo', 9);

        $result = $this->analyzer->analyze($crawlResult, $textResult, ['seo']);

        $this->assertTrue($result->hasKeywordStuffing);
        $this->assertGreaterThan(3.0, $result->densityMap['seo'] ?? 0);
    }

    public function test_analyzes_keyword_prominence(): void
    {
        $html = '
            <html>
                <head><title>SEO optimization guide</title></head>
                <body>
                    <h1>Complete SEO Guide</h1>
                    <h2>SEO basics</h2>
                    <p>This paragraph discusses optimization techniques.</p>
                </body>
            </html>
        ';
        $crawlResult = $this->createCrawlResult($html);
        $textResult = $this->createTextResult('seo optimization guide complete seo guide seo basics this paragraph discusses optimization techniques', 13);

        $result = $this->analyzer->analyze($crawlResult, $textResult, ['seo', 'optimization']);

        $this->assertArrayHasKey('seo', $result->prominentKeywords);
        $this->assertArrayHasKey('optimization', $result->prominentKeywords);
        
        $seoProminence = $result->prominentKeywords['seo'];
        $this->assertGreaterThan(0, $seoProminence['score']);
        $this->assertContains('title', $seoProminence['locations']);
        $this->assertContains('h1', $seoProminence['locations']);
    }

    public function test_identifies_long_tail_keywords(): void
    {
        $html = '<p>Best SEO practices for beginners. Advanced SEO techniques guide. Professional SEO optimization services.</p>';
        $crawlResult = $this->createCrawlResult($html);
        $textResult = $this->createTextResult('best seo practices for beginners advanced seo techniques guide professional seo optimization services', 12);

        $result = $this->analyzer->analyze($crawlResult, $textResult, ['seo']);

        $this->assertIsArray($result->longTailKeywords);
        $this->assertNotEmpty($result->longTailKeywords);
    }

    public function test_calculates_keyword_distribution(): void
    {
        $html = '<p>SEO is important. In the middle section, we discuss SEO tools. Finally, SEO best practices.</p>';
        $crawlResult = $this->createCrawlResult($html);
        $textResult = $this->createTextResult('seo is important in the middle section we discuss seo tools finally seo best practices', 15);

        $result = $this->analyzer->analyze($crawlResult, $textResult, ['seo']);

        $this->assertArrayHasKey('seo', $result->keywordDistribution);
        $distribution = $result->keywordDistribution['seo'];
        $this->assertArrayHasKey('total_occurrences', $distribution);
        $this->assertArrayHasKey('sections', $distribution);
        $this->assertEquals(3, $distribution['total_occurrences']);
    }

    public function test_finds_semantic_keywords(): void
    {
        $html = '<p>SEO optimization requires keyword research and content analysis. Good SEO involves technical optimization and quality content.</p>';
        $crawlResult = $this->createCrawlResult($html);
        $textResult = $this->createTextResult('seo optimization requires keyword research and content analysis good seo involves technical optimization and quality content', 16);

        $result = $this->analyzer->analyze($crawlResult, $textResult, ['seo']);

        $this->assertIsArray($result->semanticKeywords);
        if (isset($result->semanticKeywords['seo'])) {
            $this->assertIsArray($result->semanticKeywords['seo']);
        }
    }

    public function test_generates_appropriate_suggestions(): void
    {
        $html = '<p>This content has no target keywords mentioned.</p>';
        $crawlResult = $this->createCrawlResult($html);
        $textResult = $this->createTextResult('this content has no target keywords mentioned', 7);

        $result = $this->analyzer->analyze($crawlResult, $textResult, ['seo', 'optimization']);

        $this->assertIsArray($result->suggestions);
        $this->assertNotEmpty($result->suggestions);
    }

    public function test_handles_empty_content(): void
    {
        $crawlResult = CrawlResult::failure('test-url', 'No content');
        $textResult = $this->createTextResult('', 0);

        $result = $this->analyzer->analyze($crawlResult, $textResult, ['seo']);

        $this->assertEmpty($result->densityMap);
        $this->assertFalse($result->hasKeywordStuffing);
        $this->assertEquals(0.0, $result->overallDensity);
        $this->assertNotEmpty($result->suggestions);
    }

    public function test_calculates_overall_density(): void
    {
        $html = '<p>SEO optimization guide for SEO beginners. Learn optimization techniques.</p>';
        $crawlResult = $this->createCrawlResult($html);
        $textResult = $this->createTextResult('seo optimization guide for seo beginners learn optimization techniques', 9);

        $result = $this->analyzer->analyze($crawlResult, $textResult, ['seo', 'optimization']);

        $this->assertGreaterThan(0, $result->overallDensity);
        $this->assertIsFloat($result->overallDensity);
    }

    public function test_extracts_keywords_from_word_frequency(): void
    {
        $html = '<p>Content analysis requires understanding text processing and natural language techniques.</p>';
        $crawlResult = $this->createCrawlResult($html);
        
        $wordFrequency = [
            'content' => 5,
            'analysis' => 4,
            'text' => 3,
            'processing' => 3,
            'natural' => 2,
            'language' => 2,
        ];
        
        $textResult = $this->createTextResult('content analysis requires understanding text processing and natural language techniques', 10, $wordFrequency);

        $result = $this->analyzer->analyze($crawlResult, $textResult, []); // No target keywords

        $this->assertNotEmpty($result->densityMap);
        $this->assertArrayHasKey('content', $result->densityMap);
        $this->assertArrayHasKey('analysis', $result->densityMap);
    }

    private function createCrawlResult(string $html): CrawlResult
    {
        return CrawlResult::success(
            'https://example.com',
            $html,
            ['title' => 'Test Page'],
            0.5,
            200
        );
    }

    private function createTextResult(string $text, int $wordCount, array $wordFrequency = []): TextProcessingResult
    {
        return new TextProcessingResult(
            cleanedText: $text,
            normalizedText: $text,
            detectedLanguage: 'en',
            wordCount: $wordCount,
            characterCount: strlen($text),
            sentenceCount: 1,
            paragraphCount: 1,
            textDensity: 50.0,
            wordFrequency: $wordFrequency,
            sentenceStructure: ['average_length' => $wordCount],
            languageMetrics: ['confidence' => 80.0]
        );
    }
}