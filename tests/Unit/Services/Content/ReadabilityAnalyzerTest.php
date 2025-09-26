<?php

namespace Tests\Unit\Services\Content;

use App\Services\Content\ReadabilityAnalyzer;
use PHPUnit\Framework\TestCase;

class ReadabilityAnalyzerTest extends TestCase
{
    private ReadabilityAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = new ReadabilityAnalyzer();
    }

    /** @test */
    public function it_analyzes_simple_readable_text()
    {
        $text = 'This is a simple test. The cat sat on the mat. Dogs love to play fetch.';

        $result = $this->analyzer->analyze($text);

        $this->assertArrayHasKey('overall_score', $result);
        $this->assertArrayHasKey('flesch_reading_ease', $result);
        $this->assertArrayHasKey('flesch_kincaid_grade', $result);
        $this->assertArrayHasKey('basic_metrics', $result);

        // Should have reasonable scores for simple text
        $this->assertGreaterThan(70, $result['flesch_reading_ease']['score']);
        $this->assertLessThan(6, $result['flesch_kincaid_grade']['grade']);

        // Basic metrics should be calculated
        $this->assertEquals(3, $result['basic_metrics']['total_sentences']);
        $this->assertGreaterThan(10, $result['basic_metrics']['total_words']);
    }

    /** @test */
    public function it_analyzes_complex_difficult_text()
    {
        $text = 'The implementation of sophisticated algorithmic methodologies necessitates comprehensive understanding of multidimensional computational paradigms. Furthermore, the optimization of such systems requires extensive consideration of performance characteristics and resource utilization patterns.';

        $result = $this->analyzer->analyze($text);

        // Complex text should have lower readability scores
        $this->assertLessThan(50, $result['flesch_reading_ease']['score']);
        $this->assertGreaterThan(12, $result['flesch_kincaid_grade']['grade']);

        // Should identify complexity
        $this->assertEquals('Fairly Difficult', $result['flesch_reading_ease']['level']);
        $this->assertContains('Graduate', $result['flesch_kincaid_grade']['level']);
    }

    /** @test */
    public function it_calculates_flesch_reading_ease_correctly()
    {
        $text = 'The cat sat. The dog ran. Birds fly high.';

        $result = $this->analyzer->analyze($text);

        $fleschEase = $result['flesch_reading_ease'];

        $this->assertIsFloat($fleschEase['score']);
        $this->assertGreaterThanOrEqual(0, $fleschEase['score']);
        $this->assertLessThanOrEqual(100, $fleschEase['score']);
        $this->assertArrayHasKey('level', $fleschEase);
        $this->assertArrayHasKey('description', $fleschEase);
        $this->assertArrayHasKey('school_level', $fleschEase);
    }

    /** @test */
    public function it_calculates_flesch_kincaid_grade_correctly()
    {
        $text = 'Simple words make reading easy. Short sentences help too.';

        $result = $this->analyzer->analyze($text);

        $gradeLevel = $result['flesch_kincaid_grade'];

        $this->assertIsFloat($gradeLevel['grade']);
        $this->assertGreaterThanOrEqual(0, $gradeLevel['grade']);
        $this->assertArrayHasKey('level', $gradeLevel);
        $this->assertArrayHasKey('description', $gradeLevel);
        $this->assertArrayHasKey('recommended_age', $gradeLevel);
    }

    /** @test */
    public function it_calculates_multiple_readability_indices()
    {
        $text = 'This is a comprehensive test of multiple readability algorithms. We want to ensure that all calculations are working properly and providing accurate results for content analysis.';

        $result = $this->analyzer->analyze($text);

        // Should have all major readability indices
        $this->assertArrayHasKey('flesch_reading_ease', $result);
        $this->assertArrayHasKey('flesch_kincaid_grade', $result);
        $this->assertArrayHasKey('automated_readability_index', $result);
        $this->assertArrayHasKey('coleman_liau_index', $result);
        $this->assertArrayHasKey('smog_index', $result);
        $this->assertArrayHasKey('gunning_fog_index', $result);

        // Each should have score and grade
        foreach (['automated_readability_index', 'coleman_liau_index', 'smog_index', 'gunning_fog_index'] as $index) {
            $this->assertArrayHasKey('score', $result[$index]);
            $this->assertArrayHasKey('grade', $result[$index]);
            $this->assertIsNumeric($result[$index]['score']);
            $this->assertIsNumeric($result[$index]['grade']);
        }
    }

    /** @test */
    public function it_analyzes_basic_text_metrics_accurately()
    {
        $text = 'First sentence here. Second sentence follows! Third sentence ends with question? Final sentence concludes.';

        $result = $this->analyzer->analyze($text);

        $metrics = $result['basic_metrics'];

        $this->assertEquals(4, $metrics['total_sentences']);
        $this->assertGreaterThan(15, $metrics['total_words']);
        $this->assertGreaterThan(0, $metrics['total_syllables']);
        $this->assertGreaterThan(0, $metrics['total_characters']);
        $this->assertGreaterThan(0, $metrics['avg_words_per_sentence']);
        $this->assertGreaterThan(0, $metrics['avg_syllables_per_word']);
    }

    /** @test */
    public function it_analyzes_structural_readability_with_html()
    {
        $html = '<h2>Main Heading</h2><p>First paragraph with <strong>bold text</strong>.</p><ul><li>List item one</li><li>List item two</li></ul><p>Second paragraph continues here.</p>';
        $text = 'Main Heading First paragraph with bold text. List item one List item two Second paragraph continues here.';

        $result = $this->analyzer->analyze($text, $html);

        $structural = $result['structural_analysis'];

        $this->assertArrayHasKey('score', $structural);
        $this->assertArrayHasKey('heading_count', $structural);
        $this->assertArrayHasKey('list_count', $structural);
        $this->assertArrayHasKey('bold_elements', $structural);
        $this->assertArrayHasKey('structural_features', $structural);

        // Should detect structural elements
        $this->assertGreaterThan(0, $structural['heading_count']);
        $this->assertGreaterThan(0, $structural['list_count']);
        $this->assertGreaterThan(0, $structural['bold_elements']);
        $this->assertTrue($structural['structural_features']['has_headings']);
        $this->assertTrue($structural['structural_features']['has_lists']);
        $this->assertTrue($structural['structural_features']['has_bold_text']);
    }

    /** @test */
    public function it_analyzes_vocabulary_complexity()
    {
        $simpleText = 'Cat sat mat. Dog ran fast. Bird fly high.';
        $complexText = 'Sophisticated implementation requires comprehensive understanding of multidimensional computational methodologies.';

        $simpleResult = $this->analyzer->analyze($simpleText);
        $complexResult = $this->analyzer->analyze($complexText);

        $simpleVocab = $simpleResult['vocabulary_analysis'];
        $complexVocab = $complexResult['vocabulary_analysis'];

        // Simple text should have higher vocabulary score (easier)
        $this->assertGreaterThan($complexVocab['score'], $simpleVocab['score']);

        // Complex text should have more complex words
        $this->assertGreaterThan($simpleVocab['complex_words'], $complexVocab['complex_words']);

        // Both should have proper analysis structure
        foreach ([$simpleVocab, $complexVocab] as $vocab) {
            $this->assertArrayHasKey('score', $vocab);
            $this->assertArrayHasKey('complexity_level', $vocab);
            $this->assertArrayHasKey('complex_words', $vocab);
            $this->assertArrayHasKey('avg_word_length', $vocab);
            $this->assertArrayHasKey('vocabulary_diversity', $vocab);
        }
    }

    /** @test */
    public function it_analyzes_sentence_complexity()
    {
        $simpleText = 'Cat sat. Dog ran. Bird flew.';
        $complexText = 'The sophisticated implementation of advanced algorithmic methodologies, which requires comprehensive understanding of multidimensional computational paradigms and extensive consideration of performance characteristics, necessitates careful optimization.';

        $simpleResult = $this->analyzer->analyze($simpleText);
        $complexResult = $this->analyzer->analyze($complexText);

        $simpleSentence = $simpleResult['sentence_analysis'];
        $complexSentence = $complexResult['sentence_analysis'];

        // Simple text should have higher sentence score (easier)
        $this->assertGreaterThan($complexSentence['score'], $simpleSentence['score']);

        // Complex text should have higher average sentence length
        $this->assertGreaterThan($simpleSentence['avg_sentence_length'], $complexSentence['avg_sentence_length']);

        // Both should have proper analysis structure
        foreach ([$simpleSentence, $complexSentence] as $sentence) {
            $this->assertArrayHasKey('score', $sentence);
            $this->assertArrayHasKey('complexity_level', $sentence);
            $this->assertArrayHasKey('avg_sentence_length', $sentence);
            $this->assertArrayHasKey('complex_sentences', $sentence);
        }
    }

    /** @test */
    public function it_calculates_overall_readability_score()
    {
        $text = 'This is a test of overall readability scoring. The text should be analyzed using multiple algorithms. Each algorithm contributes to the final score. The result should be comprehensive and accurate.';

        $result = $this->analyzer->analyze($text);

        $overallScore = $result['overall_score'];

        $this->assertArrayHasKey('overall', $overallScore);
        $this->assertArrayHasKey('components', $overallScore);
        $this->assertArrayHasKey('weights', $overallScore);
        $this->assertArrayHasKey('grade', $overallScore);

        $this->assertIsFloat($overallScore['overall']);
        $this->assertGreaterThanOrEqual(0, $overallScore['overall']);
        $this->assertLessThanOrEqual(100, $overallScore['overall']);

        // Should have component scores
        $this->assertArrayHasKey('flesch_ease', $overallScore['components']);
        $this->assertArrayHasKey('structural', $overallScore['components']);
        $this->assertArrayHasKey('vocabulary', $overallScore['components']);
        $this->assertArrayHasKey('sentence', $overallScore['components']);
    }

    /** @test */
    public function it_generates_appropriate_recommendations()
    {
        $difficultText = 'The implementation of sophisticated algorithmic methodologies necessitates comprehensive understanding of multidimensional computational paradigms, requiring extensive consideration of performance characteristics and resource utilization patterns throughout the entire development lifecycle.';

        $result = $this->analyzer->analyze($difficultText);

        $recommendations = $result['recommendations'];

        $this->assertIsArray($recommendations);
        $this->assertNotEmpty($recommendations);

        // Should have structured recommendations
        foreach ($recommendations as $recommendation) {
            $this->assertArrayHasKey('type', $recommendation);
            $this->assertArrayHasKey('category', $recommendation);
            $this->assertArrayHasKey('message', $recommendation);
            $this->assertArrayHasKey('impact', $recommendation);
            $this->assertArrayHasKey('fix', $recommendation);

            $this->assertContains($recommendation['type'], ['error', 'warning', 'suggestion']);
            $this->assertContains($recommendation['impact'], ['high', 'medium', 'low']);
        }
    }

    /** @test */
    public function it_determines_reading_level_appropriately()
    {
        $childText = 'The cat is big. The dog is small. They play together.';
        $adultText = 'The comprehensive analysis reveals significant implications for contemporary business strategies and organizational development methodologies.';

        $childResult = $this->analyzer->analyze($childText);
        $adultResult = $this->analyzer->analyze($adultText);

        // Child text should be easier to read
        $this->assertContains('Easy', $childResult['reading_level']);
        $this->assertContains('Elementary', $childResult['flesch_kincaid_grade']['level']);

        // Adult text should be more difficult
        $this->assertNotContains('Easy', $adultResult['reading_level']);
        $this->assertNotContains('Elementary', $adultResult['flesch_kincaid_grade']['level']);
    }

    /** @test */
    public function it_determines_target_audience_correctly()
    {
        $simpleText = 'Dogs are fun pets. They like to play and run. Kids love dogs too.';
        $complexText = 'The implementation of enterprise-level solutions requires comprehensive understanding of architectural patterns, scalability considerations, and performance optimization strategies.';

        $simpleResult = $this->analyzer->analyze($simpleText);
        $complexResult = $this->analyzer->analyze($complexText);

        // Simple text should target general audience
        $this->assertStringContainsString('General public', $simpleResult['target_audience']);

        // Complex text should target more educated audience
        $this->assertStringContainsString('University', $complexResult['target_audience']);
    }

    /** @test */
    public function it_provides_readability_insights()
    {
        $text = 'This is a comprehensive test of the readability analysis system. We want to ensure that meaningful insights are generated based on the analysis results.';

        $result = $this->analyzer->analyze($text);

        $insights = $result['readability_insights'];

        $this->assertIsArray($insights);
        $this->assertNotEmpty($insights);

        // Should provide meaningful insights
        foreach ($insights as $insight) {
            $this->assertIsString($insight);
            $this->assertGreaterThan(10, strlen($insight)); // Non-trivial insights
        }
    }

    /** @test */
    public function it_handles_empty_text_gracefully()
    {
        $result = $this->analyzer->analyze('');

        // Should handle empty input without errors
        $this->assertArrayHasKey('overall_score', $result);
        $this->assertArrayHasKey('basic_metrics', $result);

        // Metrics should reflect empty state
        $this->assertEquals(0, $result['basic_metrics']['total_words']);
        $this->assertEquals(0, $result['basic_metrics']['total_sentences']);
    }

    /** @test */
    public function it_handles_very_short_text()
    {
        $result = $this->analyzer->analyze('Short.');

        // Should handle very short input
        $this->assertArrayHasKey('overall_score', $result);
        $this->assertArrayHasKey('basic_metrics', $result);

        $this->assertEquals(1, $result['basic_metrics']['total_words']);
        $this->assertEquals(1, $result['basic_metrics']['total_sentences']);
    }

    /** @test */
    public function it_analyzes_text_with_numbers_and_symbols()
    {
        $text = 'The price is $29.99 for 100% quality. Call 1-800-555-0123 today! Visit www.example.com for more info.';

        $result = $this->analyzer->analyze($text);

        // Should handle special characters and numbers
        $this->assertArrayHasKey('overall_score', $result);
        $this->assertGreaterThan(0, $result['basic_metrics']['total_words']);
        $this->assertGreaterThan(0, $result['flesch_reading_ease']['score']);
    }

    /** @test */
    public function it_maintains_consistent_scoring_scale()
    {
        $texts = [
            'Cat sat.',
            'The cat sat on the comfortable mat.',
            'The sophisticated implementation requires comprehensive understanding.',
            'The implementation of sophisticated algorithmic methodologies necessitates comprehensive understanding of multidimensional computational paradigms.'
        ];

        foreach ($texts as $text) {
            $result = $this->analyzer->analyze($text);

            // All scores should be within 0-100 range
            $this->assertGreaterThanOrEqual(0, $result['overall_score']['overall']);
            $this->assertLessThanOrEqual(100, $result['overall_score']['overall']);
            $this->assertGreaterThanOrEqual(0, $result['flesch_reading_ease']['score']);
            $this->assertLessThanOrEqual(100, $result['flesch_reading_ease']['score']);
        }
    }

    /** @test */
    public function it_provides_analysis_metadata()
    {
        $text = 'Test content for metadata validation.';

        $result = $this->analyzer->analyze($text);

        // Should include analysis metadata
        $this->assertArrayHasKey('analyzed_at', $result);
        $this->assertArrayHasKey('analysis_duration_ms', $result);

        // Metadata should be properly formatted
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}/', $result['analyzed_at']);
        $this->assertIsFloat($result['analysis_duration_ms']);
        $this->assertGreaterThan(0, $result['analysis_duration_ms']);
    }

    /** @test */
    public function it_maintains_algorithm_accuracy_for_known_examples()
    {
        // Test with text that has known characteristics
        $text = 'The cat sat on the mat. The dog ran in the park. This is easy to read.';

        $result = $this->analyzer->analyze($text);

        // For simple text with short sentences:
        // - Flesch Reading Ease should be high (easier to read)
        // - Grade level should be low (elementary level)
        // - Sentence complexity should be low

        $this->assertGreaterThan(70, $result['flesch_reading_ease']['score']);
        $this->assertLessThan(8, $result['flesch_kincaid_grade']['grade']);
        $this->assertGreaterThan(70, $result['sentence_analysis']['score']);

        // Average words per sentence should be reasonable for simple text
        $this->assertLessThan(15, $result['basic_metrics']['avg_words_per_sentence']);
    }
}