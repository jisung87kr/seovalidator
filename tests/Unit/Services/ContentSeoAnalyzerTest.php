<?php

namespace Tests\Unit\Services;

use App\Analyzers\ContentStructureAnalyzer;
use App\Analyzers\KeywordAnalyzer;
use App\Analyzers\ReadabilityAnalyzer;
use App\DTOs\ContentStructureResult;
use App\DTOs\CrawlResult;
use App\DTOs\KeywordResult;
use App\DTOs\ReadabilityResult;
use App\DTOs\TextProcessingResult;
use App\Services\ContentSeoAnalyzer;
use App\Utils\TextProcessor;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ContentSeoAnalyzerTest extends TestCase
{
    private ContentSeoAnalyzer $analyzer;
    private MockObject $textProcessor;
    private MockObject $keywordAnalyzer;
    private MockObject $readabilityAnalyzer;
    private MockObject $structureAnalyzer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->textProcessor = $this->createMock(TextProcessor::class);
        $this->keywordAnalyzer = $this->createMock(KeywordAnalyzer::class);
        $this->readabilityAnalyzer = $this->createMock(ReadabilityAnalyzer::class);
        $this->structureAnalyzer = $this->createMock(ContentStructureAnalyzer::class);

        $this->analyzer = new ContentSeoAnalyzer(
            $this->textProcessor,
            $this->keywordAnalyzer,
            $this->readabilityAnalyzer,
            $this->structureAnalyzer
        );
    }

    public function test_performs_complete_analysis(): void
    {
        // Arrange
        $crawlResult = $this->createCrawlResult();
        $textResult = $this->createTextResult();
        $keywordResult = $this->createKeywordResult();
        $readabilityResult = $this->createReadabilityResult();
        $structureResult = $this->createStructureResult();

        $this->textProcessor
            ->expects($this->once())
            ->method('processText')
            ->willReturn($textResult);

        $this->keywordAnalyzer
            ->expects($this->once())
            ->method('analyze')
            ->with($crawlResult, $textResult, [])
            ->willReturn($keywordResult);

        $this->readabilityAnalyzer
            ->expects($this->once())
            ->method('analyze')
            ->with($textResult)
            ->willReturn($readabilityResult);

        $this->structureAnalyzer
            ->expects($this->once())
            ->method('analyze')
            ->with($crawlResult, $textResult)
            ->willReturn($structureResult);

        // Act
        $result = $this->analyzer->analyze($crawlResult);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('text_processing', $result);
        $this->assertArrayHasKey('keyword_analysis', $result);
        $this->assertArrayHasKey('readability_analysis', $result);
        $this->assertArrayHasKey('structure_analysis', $result);
        $this->assertArrayHasKey('overall_score', $result);
        $this->assertArrayHasKey('suggestions', $result);

        $this->assertEquals($textResult, $result['text_processing']);
        $this->assertEquals($keywordResult, $result['keyword_analysis']);
        $this->assertEquals($readabilityResult, $result['readability_analysis']);
        $this->assertEquals($structureResult, $result['structure_analysis']);
        $this->assertIsInt($result['overall_score']);
        $this->assertIsArray($result['suggestions']);
    }

    public function test_analyzes_with_target_keywords(): void
    {
        // Arrange
        $crawlResult = $this->createCrawlResult();
        $textResult = $this->createTextResult();
        $targetKeywords = ['seo', 'optimization'];

        $this->textProcessor
            ->expects($this->once())
            ->method('processText')
            ->willReturn($textResult);

        $this->keywordAnalyzer
            ->expects($this->once())
            ->method('analyze')
            ->with($crawlResult, $textResult, $targetKeywords)
            ->willReturn($this->createKeywordResult());

        $this->readabilityAnalyzer
            ->method('analyze')
            ->willReturn($this->createReadabilityResult());

        $this->structureAnalyzer
            ->method('analyze')
            ->willReturn($this->createStructureResult());

        // Act
        $result = $this->analyzer->analyze($crawlResult, $targetKeywords);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('overall_score', $result);
    }

    public function test_calculates_overall_score_correctly(): void
    {
        // Arrange - create results that should yield a high score
        $crawlResult = $this->createCrawlResult();
        $textResult = $this->createHighQualityTextResult();
        $keywordResult = $this->createHighQualityKeywordResult();
        $readabilityResult = $this->createHighQualityReadabilityResult();
        $structureResult = $this->createHighQualityStructureResult();

        $this->textProcessor->method('processText')->willReturn($textResult);
        $this->keywordAnalyzer->method('analyze')->willReturn($keywordResult);
        $this->readabilityAnalyzer->method('analyze')->willReturn($readabilityResult);
        $this->structureAnalyzer->method('analyze')->willReturn($structureResult);

        // Act
        $result = $this->analyzer->analyze($crawlResult);

        // Assert
        $this->assertGreaterThan(70, $result['overall_score']);
        $this->assertLessThanOrEqual(100, $result['overall_score']);
    }

    public function test_handles_analysis_failure_gracefully(): void
    {
        // Arrange
        $crawlResult = CrawlResult::failure('https://example.com', 'Failed to crawl');

        $this->textProcessor
            ->method('processText')
            ->willReturn($this->createEmptyTextResult());

        $this->keywordAnalyzer
            ->method('analyze')
            ->willReturn($this->createEmptyKeywordResult());

        $this->readabilityAnalyzer
            ->method('analyze')
            ->willReturn($this->createEmptyReadabilityResult());

        $this->structureAnalyzer
            ->method('analyze')
            ->willReturn($this->createEmptyStructureResult());

        // Act
        $result = $this->analyzer->analyze($crawlResult);

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals(0, $result['overall_score']);
        $this->assertArrayHasKey('suggestions', $result);
    }

    public function test_quick_keyword_density_analysis(): void
    {
        // Arrange
        $content = '<p>SEO optimization is important for SEO success.</p>';
        $keywords = ['seo', 'optimization'];

        $textResult = $this->createTextResult();
        $keywordResult = $this->createKeywordResult();

        $this->textProcessor
            ->expects($this->once())
            ->method('processText')
            ->with($content)
            ->willReturn($textResult);

        $this->keywordAnalyzer
            ->expects($this->once())
            ->method('analyze')
            ->willReturn($keywordResult);

        // Act
        $result = $this->analyzer->analyzeKeywordDensity($content, $keywords);

        // Assert
        $this->assertInstanceOf(KeywordResult::class, $result);
    }

    public function test_quick_readability_analysis(): void
    {
        // Arrange
        $content = '<p>This is a test content for readability analysis.</p>';

        $textResult = $this->createTextResult();
        $readabilityResult = $this->createReadabilityResult();

        $this->textProcessor
            ->expects($this->once())
            ->method('processText')
            ->with($content)
            ->willReturn($textResult);

        $this->readabilityAnalyzer
            ->expects($this->once())
            ->method('analyze')
            ->with($textResult)
            ->willReturn($readabilityResult);

        // Act
        $result = $this->analyzer->analyzeReadability($content);

        // Assert
        $this->assertInstanceOf(ReadabilityResult::class, $result);
    }

    public function test_quick_content_structure_analysis(): void
    {
        // Arrange
        $crawlResult = $this->createCrawlResult();
        $textResult = $this->createTextResult();
        $structureResult = $this->createStructureResult();

        $this->textProcessor
            ->expects($this->once())
            ->method('processText')
            ->willReturn($textResult);

        $this->structureAnalyzer
            ->expects($this->once())
            ->method('analyze')
            ->with($crawlResult, $textResult)
            ->willReturn($structureResult);

        // Act
        $result = $this->analyzer->analyzeContentStructure($crawlResult);

        // Assert
        $this->assertInstanceOf(ContentStructureResult::class, $result);
    }

    public function test_calculates_content_score(): void
    {
        // Arrange
        $results = [
            'text_processing' => $this->createTextResult(),
            'keyword_analysis' => $this->createKeywordResult(),
            'readability_analysis' => $this->createReadabilityResult(),
            'structure_analysis' => $this->createStructureResult(),
        ];

        // Act
        $score = $this->analyzer->calculateContentScore($results);

        // Assert
        $this->assertIsInt($score);
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    public function test_generates_comprehensive_suggestions(): void
    {
        // Arrange
        $crawlResult = $this->createCrawlResult();
        $textResult = $this->createTextResult();
        $keywordResult = $this->createKeywordResultWithSuggestions();
        $readabilityResult = $this->createReadabilityResultWithSuggestions();
        $structureResult = $this->createStructureResultWithSuggestions();

        $this->textProcessor->method('processText')->willReturn($textResult);
        $this->keywordAnalyzer->method('analyze')->willReturn($keywordResult);
        $this->readabilityAnalyzer->method('analyze')->willReturn($readabilityResult);
        $this->structureAnalyzer->method('analyze')->willReturn($structureResult);

        // Act
        $result = $this->analyzer->analyze($crawlResult);

        // Assert
        $this->assertArrayHasKey('suggestions', $result);
        $this->assertArrayHasKey('keyword', $result['suggestions']);
        $this->assertArrayHasKey('readability', $result['suggestions']);
        $this->assertArrayHasKey('structure', $result['suggestions']);
        $this->assertArrayHasKey('priority', $result['suggestions']);
    }

    private function createCrawlResult(): CrawlResult
    {
        return CrawlResult::success(
            'https://example.com',
            '<html><body><h1>Test</h1><p>Content</p></body></html>',
            ['title' => 'Test Page'],
            0.5,
            200
        );
    }

    private function createTextResult(): TextProcessingResult
    {
        return new TextProcessingResult(
            cleanedText: 'test content',
            normalizedText: 'test content',
            detectedLanguage: 'en',
            wordCount: 500,
            characterCount: 2500,
            sentenceCount: 25,
            paragraphCount: 5,
            textDensity: 20.0,
            wordFrequency: ['test' => 5, 'content' => 3],
            sentenceStructure: ['average_length' => 20],
            languageMetrics: ['confidence' => 80.0]
        );
    }

    private function createHighQualityTextResult(): TextProcessingResult
    {
        return new TextProcessingResult(
            cleanedText: 'high quality content',
            normalizedText: 'high quality content',
            detectedLanguage: 'en',
            wordCount: 800,
            characterCount: 4000,
            sentenceCount: 40,
            paragraphCount: 8,
            textDensity: 25.0,
            wordFrequency: ['quality' => 10, 'content' => 8],
            sentenceStructure: ['average_length' => 20],
            languageMetrics: ['confidence' => 95.0]
        );
    }

    private function createKeywordResult(): KeywordResult
    {
        return new KeywordResult(
            densityMap: ['seo' => 2.5, 'optimization' => 1.8],
            prominentKeywords: [],
            semanticKeywords: [],
            hasKeywordStuffing: false,
            longTailKeywords: [],
            keywordDistribution: [],
            overallDensity: 2.15,
            suggestions: []
        );
    }

    private function createHighQualityKeywordResult(): KeywordResult
    {
        return new KeywordResult(
            densityMap: ['seo' => 2.0, 'optimization' => 1.5],
            prominentKeywords: ['seo' => ['prominence_level' => 'high', 'score' => 15, 'locations' => ['title', 'h1']]],
            semanticKeywords: [],
            hasKeywordStuffing: false,
            longTailKeywords: ['seo optimization guide'],
            keywordDistribution: [],
            overallDensity: 1.75,
            suggestions: ['Good keyword optimization']
        );
    }

    private function createReadabilityResult(): ReadabilityResult
    {
        return new ReadabilityResult(
            fleschKincaidScore: 65.0,
            gunningFogIndex: 8.5,
            smogIndex: 7.2,
            averageSentenceLength: 18.0,
            averageWordsPerSentence: 18.0,
            totalSyllables: 750,
            complexWords: 50,
            totalWords: 500,
            totalSentences: 25,
            readingLevel: 'standard',
            suggestions: []
        );
    }

    private function createHighQualityReadabilityResult(): ReadabilityResult
    {
        return new ReadabilityResult(
            fleschKincaidScore: 75.0,
            gunningFogIndex: 7.0,
            smogIndex: 6.5,
            averageSentenceLength: 16.0,
            averageWordsPerSentence: 16.0,
            totalSyllables: 900,
            complexWords: 40,
            totalWords: 600,
            totalSentences: 30,
            readingLevel: 'fairly_easy',
            suggestions: ['Excellent readability']
        );
    }

    private function createStructureResult(): ContentStructureResult
    {
        return new ContentStructureResult(
            headingStructure: ['quality_score' => 75.0, 'counts' => ['h1' => 1, 'h2' => 3], 'issues' => []],
            linkAnalysis: ['internal_count' => 5, 'external_count' => 2, 'link_quality_score' => 80.0, 'issues' => []],
            imageAnalysis: ['total_count' => 3, 'with_alt_count' => 3, 'optimization_score' => 100.0, 'issues' => []],
            contentHierarchy: [],
            contentLength: 500,
            contentDepthScore: 85.0,
            structureIssues: [],
            suggestions: []
        );
    }

    private function createHighQualityStructureResult(): ContentStructureResult
    {
        return new ContentStructureResult(
            headingStructure: ['quality_score' => 90.0, 'counts' => ['h1' => 1, 'h2' => 4, 'h3' => 2], 'issues' => []],
            linkAnalysis: ['internal_count' => 8, 'external_count' => 2, 'link_quality_score' => 95.0, 'issues' => []],
            imageAnalysis: ['total_count' => 5, 'with_alt_count' => 5, 'optimization_score' => 100.0, 'issues' => []],
            contentHierarchy: [],
            contentLength: 800,
            contentDepthScore: 95.0,
            structureIssues: [],
            suggestions: ['Excellent content structure']
        );
    }

    private function createEmptyTextResult(): TextProcessingResult
    {
        return new TextProcessingResult(
            cleanedText: '',
            normalizedText: '',
            detectedLanguage: 'unknown',
            wordCount: 0,
            characterCount: 0,
            sentenceCount: 0,
            paragraphCount: 0,
            textDensity: 0.0,
            wordFrequency: [],
            sentenceStructure: [],
            languageMetrics: []
        );
    }

    private function createEmptyKeywordResult(): KeywordResult
    {
        return new KeywordResult([], [], [], false, [], [], 0.0, ['No content to analyze']);
    }

    private function createEmptyReadabilityResult(): ReadabilityResult
    {
        return new ReadabilityResult(0, 0, 0, 0, 0, 0, 0, 0, 0, 'unknown', ['No content to analyze']);
    }

    private function createEmptyStructureResult(): ContentStructureResult
    {
        return new ContentStructureResult([], [], [], [], 0, 0.0, [], ['No content to analyze']);
    }

    private function createKeywordResultWithSuggestions(): KeywordResult
    {
        return new KeywordResult(
            densityMap: ['seo' => 1.0],
            prominentKeywords: [],
            semanticKeywords: [],
            hasKeywordStuffing: false,
            longTailKeywords: [],
            keywordDistribution: [],
            overallDensity: 1.0,
            suggestions: ['Consider increasing keyword density', 'Add keywords to headings']
        );
    }

    private function createReadabilityResultWithSuggestions(): ReadabilityResult
    {
        return new ReadabilityResult(
            fleschKincaidScore: 45.0,
            gunningFogIndex: 12.0,
            smogIndex: 10.5,
            averageSentenceLength: 25.0,
            averageWordsPerSentence: 25.0,
            totalSyllables: 1000,
            complexWords: 100,
            totalWords: 500,
            totalSentences: 20,
            readingLevel: 'fairly_difficult',
            suggestions: ['Simplify vocabulary', 'Shorten sentences', 'Use active voice']
        );
    }

    private function createStructureResultWithSuggestions(): ContentStructureResult
    {
        return new ContentStructureResult(
            headingStructure: ['quality_score' => 60.0, 'counts' => ['h1' => 0], 'issues' => ['No H1 tag']],
            linkAnalysis: ['internal_count' => 1, 'external_count' => 5, 'link_quality_score' => 40.0, 'issues' => ['Poor link balance']],
            imageAnalysis: ['total_count' => 2, 'with_alt_count' => 1, 'optimization_score' => 50.0, 'issues' => ['Missing alt text']],
            contentHierarchy: [],
            contentLength: 200,
            contentDepthScore: 45.0,
            structureIssues: ['No H1 tag', 'Poor link balance'],
            suggestions: ['Add H1 tag', 'Improve internal linking', 'Add alt text to images']
        );
    }
}