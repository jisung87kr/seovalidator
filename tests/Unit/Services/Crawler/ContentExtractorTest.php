<?php

namespace Tests\Unit\Services\Crawler;

use App\Services\Crawler\ContentExtractor;
use Exception;
use PHPUnit\Framework\TestCase;

class ContentExtractorTest extends TestCase
{
    private ContentExtractor $contentExtractor;
    private string $sampleHtml;
    private string $richContentHtml;

    protected function setUp(): void
    {
        parent::setUp();
        $this->contentExtractor = new ContentExtractor();

        $this->sampleHtml = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Test Article</title>
    <meta name="description" content="This is a test article about content extraction">
</head>
<body>
    <header>
        <nav>Navigation</nav>
    </header>
    <main>
        <h1>Main Article Title</h1>
        <p>This is the first paragraph of the article. It contains some important information about the topic.</p>
        <h2>Subheading One</h2>
        <p>This paragraph discusses the first subtopic. It has multiple sentences to test readability metrics.</p>
        <p>Another paragraph with different content. This helps test text analysis capabilities.</p>
        <h3>Smaller Heading</h3>
        <ul>
            <li>First list item</li>
            <li>Second list item</li>
            <li>Third list item</li>
        </ul>
        <p>A longer paragraph that contains more complex sentences. The purpose is to test various readability algorithms and content analysis features. This includes keyword density calculations and content quality assessments.</p>
        <blockquote>This is a quoted text that should be included in content analysis.</blockquote>
    </main>
    <aside>
        <p>Sidebar content that might be less important</p>
    </aside>
    <footer>
        <p>Footer information</p>
    </footer>
</body>
</html>
HTML;

        $this->richContentHtml = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Rich Content Example</title>
</head>
<body>
    <article>
        <h1>Understanding Content Quality</h1>
        <p>Content quality is essential for effective communication. Good content should be clear, engaging, and valuable to readers.</p>

        <h2>What Makes Quality Content?</h2>
        <p>Quality content demonstrates several key characteristics:</p>
        <ol>
            <li>Clarity and readability</li>
            <li>Relevant and accurate information</li>
            <li>Engaging writing style</li>
            <li>Proper structure and organization</li>
        </ol>

        <h2>Content Structure Best Practices</h2>
        <p>Well-structured content uses headings effectively. Headings create hierarchy and improve navigation. They also help search engines understand content organization.</p>

        <h3>Using Headings Properly</h3>
        <p>Start with H1 for the main title. Use H2 for major sections. Use H3 for subsections. Avoid skipping heading levels.</p>

        <h3>Writing Style Tips</h3>
        <p>Write for your audience. Use active voice when possible. Keep sentences concise but varied. Include questions to engage readers. What do you think makes content engaging?</p>

        <p>Consider readability scores. The Flesch Reading Ease score measures text difficulty. Scores between 60-70 are considered standard. Higher scores indicate easier reading.</p>

        <h2>Content Analysis Metrics</h2>
        <p>Various metrics help evaluate content quality. Word count affects SEO performance. Reading time influences user engagement. Keyword density impacts search ranking.</p>

        <p>Modern content analysis tools examine multiple factors simultaneously. They consider semantic relationships between words. They evaluate content structure and organization. They assess readability using multiple algorithms.</p>

        <h2>Conclusion</h2>
        <p>Quality content requires attention to multiple factors. It balances readability with depth. It structures information logically. It engages readers while providing value.</p>
    </article>
</body>
</html>
HTML;
    }

    public function testExtractFromHtmlBasicStructure(): void
    {
        $result = $this->contentExtractor->extractFromHtml($this->sampleHtml);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('text_analysis', $result);
        $this->assertArrayHasKey('content_structure', $result);
        $this->assertArrayHasKey('readability', $result);
        $this->assertArrayHasKey('keywords', $result);
        $this->assertArrayHasKey('content_quality', $result);
        $this->assertArrayHasKey('language_detection', $result);
        $this->assertArrayHasKey('content_categories', $result);
        $this->assertArrayHasKey('seo_content', $result);
        $this->assertArrayHasKey('extracted_at', $result);
    }

    public function testTextAnalysis(): void
    {
        $result = $this->contentExtractor->extractFromHtml($this->sampleHtml);
        $textAnalysis = $result['text_analysis'];

        $this->assertIsArray($textAnalysis);
        $this->assertArrayHasKey('character_count', $textAnalysis);
        $this->assertArrayHasKey('word_count', $textAnalysis);
        $this->assertArrayHasKey('sentence_count', $textAnalysis);
        $this->assertArrayHasKey('paragraph_count', $textAnalysis);
        $this->assertArrayHasKey('reading_time_minutes', $textAnalysis);
        $this->assertArrayHasKey('unique_words', $textAnalysis);
        $this->assertArrayHasKey('vocabulary_diversity', $textAnalysis);

        $this->assertIsInt($textAnalysis['character_count']);
        $this->assertIsInt($textAnalysis['word_count']);
        $this->assertIsInt($textAnalysis['sentence_count']);
        $this->assertIsInt($textAnalysis['paragraph_count']);
        $this->assertIsFloat($textAnalysis['reading_time_minutes']);

        $this->assertGreaterThan(0, $textAnalysis['character_count']);
        $this->assertGreaterThan(0, $textAnalysis['word_count']);
        $this->assertGreaterThan(0, $textAnalysis['sentence_count']);
        $this->assertGreaterThan(0, $textAnalysis['paragraph_count']);
    }

    public function testReadabilityAnalysis(): void
    {
        $result = $this->contentExtractor->extractFromHtml($this->richContentHtml);
        $readability = $result['readability'];

        $this->assertIsArray($readability);
        $this->assertArrayHasKey('flesch_kincaid_grade', $readability);
        $this->assertArrayHasKey('flesch_reading_ease', $readability);
        $this->assertArrayHasKey('gunning_fog_index', $readability);
        $this->assertArrayHasKey('automated_readability_index', $readability);
        $this->assertArrayHasKey('coleman_liau_index', $readability);
        $this->assertArrayHasKey('readability_grade', $readability);
        $this->assertArrayHasKey('reading_difficulty', $readability);

        $this->assertIsFloat($readability['flesch_kincaid_grade']);
        $this->assertIsFloat($readability['flesch_reading_ease']);
        $this->assertIsFloat($readability['gunning_fog_index']);
        $this->assertIsString($readability['readability_grade']);
        $this->assertIsString($readability['reading_difficulty']);

        $this->assertGreaterThan(0, $readability['flesch_reading_ease']);
    }

    public function testKeywordAnalysis(): void
    {
        $result = $this->contentExtractor->extractFromHtml($this->richContentHtml);
        $keywords = $result['keywords'];

        $this->assertIsArray($keywords);
        $this->assertArrayHasKey('single_keywords', $keywords);
        $this->assertArrayHasKey('phrases', $keywords);
        $this->assertArrayHasKey('keyword_density', $keywords);
        $this->assertArrayHasKey('total_unique_keywords', $keywords);

        $singleKeywords = $keywords['single_keywords'];
        $this->assertIsArray($singleKeywords);
        $this->assertContains('content', array_keys($singleKeywords));

        foreach ($singleKeywords as $keyword => $data) {
            $this->assertArrayHasKey('count', $data);
            $this->assertArrayHasKey('density', $data);
            $this->assertIsInt($data['count']);
            $this->assertIsFloat($data['density']);
            $this->assertGreaterThan(0, $data['count']);
        }

        $phrases = $keywords['phrases'];
        $this->assertIsArray($phrases);

        if (!empty($phrases)) {
            foreach ($phrases as $phrase => $count) {
                $this->assertIsString($phrase);
                $this->assertIsInt($count);
                $this->assertGreaterThan(1, $count); // Phrases should appear more than once
            }
        }
    }

    public function testContentQualityAssessment(): void
    {
        $result = $this->contentExtractor->extractFromHtml($this->richContentHtml);
        $quality = $result['content_quality'];

        $this->assertIsArray($quality);
        $this->assertArrayHasKey('overall_score', $quality);
        $this->assertArrayHasKey('grade', $quality);
        $this->assertArrayHasKey('quality_factors', $quality);
        $this->assertArrayHasKey('recommendations', $quality);

        $this->assertIsFloat($quality['overall_score']);
        $this->assertIsString($quality['grade']);
        $this->assertIsArray($quality['quality_factors']);
        $this->assertIsArray($quality['recommendations']);

        $this->assertGreaterThanOrEqual(0, $quality['overall_score']);
        $this->assertLessThanOrEqual(100, $quality['overall_score']);

        $factors = $quality['quality_factors'];
        $this->assertArrayHasKey('word_count_score', $factors);
        $this->assertArrayHasKey('readability_score', $factors);
        $this->assertArrayHasKey('content_depth_score', $factors);
        $this->assertArrayHasKey('uniqueness_score', $factors);

        foreach ($factors as $factor => $score) {
            $this->assertIsFloat($score);
            $this->assertGreaterThanOrEqual(0, $score);
            $this->assertLessThanOrEqual(100, $score);
        }
    }

    public function testLanguageDetection(): void
    {
        $result = $this->contentExtractor->extractFromHtml($this->sampleHtml);
        $language = $result['language_detection'];

        $this->assertIsArray($language);
        $this->assertArrayHasKey('detected_language', $language);
        $this->assertArrayHasKey('confidence', $language);
        $this->assertArrayHasKey('alternative_languages', $language);
        $this->assertArrayHasKey('mixed_language', $language);

        $this->assertIsString($language['detected_language']);
        $this->assertIsFloat($language['confidence']);
        $this->assertIsArray($language['alternative_languages']);
        $this->assertIsBool($language['mixed_language']);
    }

    public function testContentCategorization(): void
    {
        $result = $this->contentExtractor->extractFromHtml($this->richContentHtml);
        $categories = $result['content_categories'];

        $this->assertIsArray($categories);
        $this->assertArrayHasKey('content_type', $categories);
        $this->assertArrayHasKey('topics', $categories);
        $this->assertArrayHasKey('writing_style', $categories);
        $this->assertArrayHasKey('audience_level', $categories);
        $this->assertArrayHasKey('content_intent', $categories);

        $this->assertIsString($categories['content_type']);
        $this->assertIsArray($categories['topics']);
        $this->assertIsArray($categories['writing_style']);
        $this->assertIsString($categories['audience_level']);
        $this->assertIsString($categories['content_intent']);
    }

    public function testSeoContentAnalysis(): void
    {
        $result = $this->contentExtractor->extractFromHtml($this->richContentHtml);
        $seoContent = $result['seo_content'];

        $this->assertIsArray($seoContent);
        $this->assertArrayHasKey('keyword_optimization', $seoContent);
        $this->assertArrayHasKey('content_length_seo', $seoContent);
        $this->assertArrayHasKey('readability_seo', $seoContent);

        $keywordOpt = $seoContent['keyword_optimization'];
        $this->assertIsArray($keywordOpt);
        $this->assertArrayHasKey('primary_keywords', $keywordOpt);
        $this->assertArrayHasKey('keyword_stuffing_risk', $keywordOpt);

        $contentLength = $seoContent['content_length_seo'];
        $this->assertIsArray($contentLength);
        $this->assertArrayHasKey('word_count', $contentLength);
        $this->assertArrayHasKey('seo_rating', $contentLength);

        $readabilitySeo = $seoContent['readability_seo'];
        $this->assertIsArray($readabilitySeo);
        $this->assertArrayHasKey('flesch_score', $readabilitySeo);
        $this->assertArrayHasKey('seo_readability_rating', $readabilitySeo);
    }

    public function testExtractSections(): void
    {
        $selectors = [
            'main_content' => 'main',
            'sidebar' => 'aside',
            'navigation' => 'nav'
        ];

        $result = $this->contentExtractor->extractSections($this->sampleHtml, $selectors);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('sections', $result);
        $this->assertArrayHasKey('total_sections', $result);
        $this->assertArrayHasKey('combined_analysis', $result);

        $sections = $result['sections'];
        $this->assertArrayHasKey('main_content', $sections);
        $this->assertArrayHasKey('sidebar', $sections);
        $this->assertArrayHasKey('navigation', $sections);

        $mainContent = $sections['main_content'];
        $this->assertArrayHasKey('text', $mainContent);
        $this->assertArrayHasKey('word_count', $mainContent);
        $this->assertArrayHasKey('character_count', $mainContent);
        $this->assertArrayHasKey('reading_time', $mainContent);
        $this->assertArrayHasKey('keywords', $mainContent);

        $this->assertIsString($mainContent['text']);
        $this->assertIsInt($mainContent['word_count']);
        $this->assertGreaterThan(0, $mainContent['word_count']);
    }

    public function testExtractMainContent(): void
    {
        $result = $this->contentExtractor->extractMainContent($this->richContentHtml);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('main_content', $result);
        $this->assertArrayHasKey('content_analysis', $result);
        $this->assertArrayHasKey('content_quality', $result);

        $mainContent = $result['main_content'];
        $this->assertArrayHasKey('text', $mainContent);
        $this->assertArrayHasKey('confidence_score', $mainContent);
        $this->assertArrayHasKey('extraction_method', $mainContent);

        $this->assertIsString($mainContent['text']);
        $this->assertIsFloat($mainContent['confidence_score']);
        $this->assertIsString($mainContent['extraction_method']);
        $this->assertGreaterThan(0, strlen($mainContent['text']));
    }

    public function testAnalyzeDuplicateContent(): void
    {
        $compareWith = [
            'This is the first paragraph of the article. It contains some important information about the topic.',
            'Completely different content that should not match.',
            'This paragraph discusses the first subtopic. It has multiple sentences to test readability metrics.'
        ];

        $result = $this->contentExtractor->analyzeDuplicateContent($this->sampleHtml, $compareWith);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('content_fingerprint', $result);
        $this->assertArrayHasKey('content_hash', $result);
        $this->assertArrayHasKey('word_count', $result);
        $this->assertArrayHasKey('unique_word_ratio', $result);
        $this->assertArrayHasKey('similarity_scores', $result);
        $this->assertArrayHasKey('duplicate_matches', $result);

        $this->assertIsString($result['content_fingerprint']);
        $this->assertIsString($result['content_hash']);
        $this->assertIsInt($result['word_count']);
        $this->assertIsFloat($result['unique_word_ratio']);
        $this->assertIsArray($result['similarity_scores']);
        $this->assertIsArray($result['duplicate_matches']);

        $similarityScores = $result['similarity_scores'];
        $this->assertCount(3, $similarityScores);

        foreach ($similarityScores as $score) {
            $this->assertIsFloat($score);
            $this->assertGreaterThanOrEqual(0, $score);
            $this->assertLessThanOrEqual(1, $score);
        }
    }

    public function testAnalyzeAccessibilityContent(): void
    {
        $accessibleHtml = <<<HTML
<html lang="en">
<body>
    <h1>Main Title</h1>
    <h2>Section Title</h2>
    <p>This content is written in simple, clear language. It uses short sentences. The reading level is appropriate for general audiences.</p>
    <img src="test.jpg" alt="Descriptive alternative text for the image">
    <a href="/more-info" title="Learn more about this topic">Read more</a>
    <form>
        <label for="name">Your name:</label>
        <input type="text" id="name" name="name">
    </form>
</body>
</html>
HTML;

        $result = $this->contentExtractor->analyzeAccessibilityContent($accessibleHtml);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('reading_level', $result);
        $this->assertArrayHasKey('content_structure', $result);
        $this->assertArrayHasKey('plain_text_alternative', $result);
        $this->assertArrayHasKey('content_landmarks', $result);

        $readingLevel = $result['reading_level'];
        $this->assertIsArray($readingLevel);
        $this->assertArrayHasKey('flesch_reading_ease', $readingLevel);

        $plainText = $result['plain_text_alternative'];
        $this->assertIsString($plainText);
        $this->assertGreaterThan(0, strlen($plainText));

        $contentStructure = $result['content_structure'];
        $this->assertIsArray($contentStructure);
    }

    public function testExtractFromHtmlWithEmptyContent(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to extract content');
        $this->contentExtractor->extractFromHtml('');
    }

    public function testExtractFromHtmlWithMinimalContent(): void
    {
        $minimalHtml = '<html><body><p>Short text.</p></body></html>';

        $result = $this->contentExtractor->extractFromHtml($minimalHtml);

        $this->assertIsArray($result);
        $textAnalysis = $result['text_analysis'];
        $this->assertGreaterThan(0, $textAnalysis['word_count']);
        $this->assertGreaterThan(0, $textAnalysis['character_count']);
    }

    public function testContentWithSpecialCharacters(): void
    {
        $specialHtml = <<<HTML
<html>
<body>
    <p>Content with special characters: áéíóú, ñ, ç, β, π, ∞, ©, ®, ™</p>
    <p>Quotes: "double quotes" and 'single quotes'</p>
    <p>Math: 2 + 2 = 4, 50% of 100 = 50</p>
    <p>Symbols: &lt; &gt; &amp; &quot;</p>
</body>
</html>
HTML;

        $result = $this->contentExtractor->extractFromHtml($specialHtml);

        $this->assertIsArray($result);
        $textAnalysis = $result['text_analysis'];
        $this->assertGreaterThan(0, $textAnalysis['word_count']);
        $this->assertGreaterThan(0, $textAnalysis['character_count']);
    }

    public function testLongContentAnalysis(): void
    {
        // Generate long content
        $longHtml = '<html><body>';
        $longHtml .= '<h1>Long Content Analysis Test</h1>';

        for ($i = 1; $i <= 100; $i++) {
            $longHtml .= "<p>This is paragraph number $i. It contains meaningful content for testing purposes. ";
            $longHtml .= "The content analysis system should handle longer documents efficiently. ";
            $longHtml .= "This helps test scalability and performance of content extraction algorithms.</p>";

            if ($i % 20 === 0) {
                $longHtml .= "<h2>Section $i</h2>";
            }
        }
        $longHtml .= '</body></html>';

        $result = $this->contentExtractor->extractFromHtml($longHtml);

        $this->assertIsArray($result);
        $textAnalysis = $result['text_analysis'];
        $this->assertGreaterThan(1000, $textAnalysis['word_count']);
        $this->assertGreaterThan(5, $textAnalysis['reading_time_minutes']);

        $contentQuality = $result['content_quality'];
        $this->assertGreaterThan(70, $contentQuality['overall_score']); // Should score well for length
    }

    public function testContentWithMultipleLanguages(): void
    {
        $multiLangHtml = <<<HTML
<html>
<body>
    <p>This is English content with some words.</p>
    <p>Este es contenido en español con algunas palabras.</p>
    <p>Ceci est du contenu français avec quelques mots.</p>
    <p>Back to English for the conclusion.</p>
</body>
</html>
HTML;

        $result = $this->contentExtractor->extractFromHtml($multiLangHtml);

        $this->assertIsArray($result);
        $language = $result['language_detection'];
        $this->assertIsArray($language);

        // Should detect mixed language
        $this->assertTrue($language['mixed_language'] || count($language['alternative_languages']) > 0);
    }

    public function testContentStructureAnalysis(): void
    {
        $structuredHtml = <<<HTML
<html>
<body>
    <h1>Main Title</h1>
    <h2>First Section</h2>
    <p>First paragraph.</p>
    <p>Second paragraph.</p>
    <h3>Subsection</h3>
    <p>Subsection content.</p>
    <ul>
        <li>List item 1</li>
        <li>List item 2</li>
    </ul>
    <h2>Second Section</h2>
    <p>More content here.</p>
    <ol>
        <li>Numbered item 1</li>
        <li>Numbered item 2</li>
    </ol>
</body>
</html>
HTML;

        $result = $this->contentExtractor->extractFromHtml($structuredHtml);

        $this->assertIsArray($result);
        $structure = $result['content_structure'];
        $this->assertIsArray($structure);
        $this->assertArrayHasKey('headings_structure', $structure);
        $this->assertArrayHasKey('paragraph_analysis', $structure);
        $this->assertArrayHasKey('list_analysis', $structure);

        $headings = $structure['headings_structure'];
        $this->assertIsArray($headings);
        $this->assertArrayHasKey('headings', $headings);
        $this->assertGreaterThan(0, $headings['total_count']);

        $paragraphs = $structure['paragraph_analysis'];
        $this->assertIsArray($paragraphs);
        $this->assertArrayHasKey('paragraphs', $paragraphs);
        $this->assertGreaterThan(0, $paragraphs['total_count']);

        $lists = $structure['list_analysis'];
        $this->assertIsArray($lists);
        $this->assertArrayHasKey('unordered_lists', $lists);
        $this->assertArrayHasKey('ordered_lists', $lists);
        $this->assertEquals(1, $lists['unordered_lists']);
        $this->assertEquals(1, $lists['ordered_lists']);
    }

    public function testReadabilityScoreVariations(): void
    {
        // Easy to read content
        $easyHtml = '<html><body><p>This is easy. Short words. Simple ideas. Clear text.</p></body></html>';

        // Complex content
        $complexHtml = '<html><body><p>The implementation of sophisticated algorithmic methodologies necessitates comprehensive understanding of computational complexities and their ramifications on system performance optimization strategies.</p></body></html>';

        $easyResult = $this->contentExtractor->extractFromHtml($easyHtml);
        $complexResult = $this->contentExtractor->extractFromHtml($complexHtml);

        $easyReadability = $easyResult['readability']['flesch_reading_ease'];
        $complexReadability = $complexResult['readability']['flesch_reading_ease'];

        $this->assertGreaterThan($complexReadability, $easyReadability);
        $this->assertEquals('Very Easy', $easyResult['readability']['reading_difficulty']);
    }

    public function testKeywordDensityCalculation(): void
    {
        $keywordRichHtml = <<<HTML
<html>
<body>
    <p>SEO optimization is important for SEO success. Good SEO practices improve SEO rankings.</p>
    <p>Content marketing and SEO work together. SEO content should be valuable content.</p>
    <p>The best SEO strategy includes SEO analysis and SEO monitoring.</p>
</body>
</html>
HTML;

        $result = $this->contentExtractor->extractFromHtml($keywordRichHtml);

        $keywords = $result['keywords'];
        $keywordDensity = $keywords['keyword_density'];

        $this->assertIsArray($keywordDensity);
        $this->assertArrayHasKey('seo', $keywordDensity);

        $seoData = $keywordDensity['seo'];
        $this->assertGreaterThan(5, $seoData['count']); // "seo" appears multiple times
        $this->assertGreaterThan(10, $seoData['density']); // High density

        // Check for keyword stuffing detection
        $seoContent = $result['seo_content'];
        $keywordOpt = $seoContent['keyword_optimization'];
        $this->assertTrue($keywordOpt['keyword_stuffing_risk']);
    }

    public function testContentQualityFactors(): void
    {
        $qualityHtml = <<<HTML
<html>
<body>
    <h1>Comprehensive Guide to Quality Content</h1>

    <p>Creating high-quality content requires understanding multiple factors that contribute to reader engagement and search engine optimization. This guide explores essential elements of effective content creation.</p>

    <h2>Understanding Your Audience</h2>
    <p>Quality content starts with understanding your target audience. Who are they? What challenges do they face? What information do they need? Answering these questions helps create focused, valuable content.</p>

    <h2>Content Structure and Organization</h2>
    <p>Well-organized content uses clear headings, logical flow, and appropriate formatting. This improves readability and helps readers find information quickly.</p>

    <ul>
        <li>Use descriptive headings</li>
        <li>Keep paragraphs concise</li>
        <li>Include bullet points and lists</li>
        <li>Add relevant images and media</li>
    </ul>

    <h2>Writing Style and Tone</h2>
    <p>Your writing style should match your audience and purpose. Professional content differs from casual blog posts. Consistency in tone builds trust and recognition.</p>

    <h2>Optimizing for Search Engines</h2>
    <p>SEO-friendly content balances human readability with search engine requirements. Use relevant keywords naturally. Include meta descriptions and proper heading structure.</p>

    <p>Quality content takes time and effort to create. However, the investment pays off through increased engagement, better search rankings, and stronger audience relationships.</p>
</body>
</html>
HTML;

        $result = $this->contentExtractor->extractFromHtml($qualityHtml);

        $quality = $result['content_quality'];
        $factors = $quality['quality_factors'];

        $this->assertGreaterThan(80, $factors['word_count_score']); // Good length
        $this->assertGreaterThan(70, $factors['readability_score']); // Readable
        $this->assertGreaterThan(70, $factors['content_depth_score']); // Good structure
        $this->assertGreaterThan(70, $factors['structure_score']); // Well structured

        $this->assertGreaterThan(75, $quality['overall_score']);
        $this->assertContains($quality['grade'], ['Excellent', 'Good']);
    }
}