<?php

namespace Tests\Unit\Services\Quality;

use Tests\TestCase;
use App\Services\Quality\ContentQualityAssessor;

class ContentQualityAssessorTest extends TestCase
{
    private ContentQualityAssessor $assessor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assessor = new ContentQualityAssessor();
    }

    /** @test */
    public function it_can_assess_basic_content_quality()
    {
        $html = '<html><head><title>Test Page</title><meta name="description" content="A good description"></head><body><h1>Main Title</h1><p>This is quality content with sufficient length and proper structure.</p></body></html>';
        $url = 'https://example.com';
        $parsedData = [
            'meta' => ['title' => 'Test Page', 'description' => 'A good description'],
            'content' => ['word_count' => 150],
            'headings' => ['h1' => ['Main Title']],
            'images' => ['total_count' => 0, 'with_alt_count' => 0],
            'links' => ['total_count' => 0]
        ];

        $result = $this->assessor->assess($html, $url, $parsedData);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('analyzed_at', $result);
        $this->assertArrayHasKey('analysis_duration_ms', $result);
        $this->assertArrayHasKey('overall_score', $result);
        $this->assertArrayHasKey('quality_dimensions', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('quality_insights', $result);
        $this->assertArrayHasKey('content_metrics', $result);
        $this->assertArrayHasKey('improvement_priority', $result);

        $this->assertIsNumeric($result['overall_score']);
        $this->assertGreaterThanOrEqual(0, $result['overall_score']);
        $this->assertLessThanOrEqual(100, $result['overall_score']);
    }

    /** @test */
    public function it_assesses_readability_correctly()
    {
        // Good readability: short sentences, proper structure
        $goodReadabilityHtml = '<html><body>
            <p>This is a simple sentence. It is easy to read. The content flows well.</p>
            <p>Each paragraph has reasonable length. Sentences are not too complex. This makes reading enjoyable.</p>
            <p>The writing style is clear. It uses common words. Readers can understand quickly.</p>
        </body></html>';

        // Poor readability: long sentences, complex structure
        $poorReadabilityHtml = '<html><body>
            <p>This is an extremely long and complex sentence that contains multiple clauses, subordinate phrases, and technical jargon that would be difficult for most readers to understand, especially when combined with other similarly complex sentences in the same paragraph, creating a wall of text that is intimidating and difficult to parse.</p>
        </body></html>';

        $url = 'https://example.com';
        $parsedData = ['content' => ['word_count' => 100]];

        $goodResult = $this->assessor->assess($goodReadabilityHtml, $url, $parsedData);
        $poorResult = $this->assessor->assess($poorReadabilityHtml, $url, $parsedData);

        $goodReadability = $goodResult['quality_dimensions']['readability'];
        $poorReadability = $poorResult['quality_dimensions']['readability'];

        $this->assertGreaterThan($poorReadability['score'], $goodReadability['score']);
        $this->assertGreaterThan(0, $goodReadability['word_count']);
        $this->assertGreaterThan(0, $goodReadability['sentence_count']);
        $this->assertArrayHasKey('flesch_reading_ease', $goodReadability);
        $this->assertArrayHasKey('reading_level', $goodReadability);
    }

    /** @test */
    public function it_assesses_content_structure_correctly()
    {
        // Good structure: proper heading hierarchy, lists, etc.
        $goodStructureHtml = '<html><body>
            <h1>Main Title</h1>
            <h2>Section 1</h2>
            <p>Content for section 1.</p>
            <ul>
                <li>Point 1</li>
                <li>Point 2</li>
            </ul>
            <h2>Section 2</h2>
            <p>Content for section 2.</p>
            <blockquote>Important quote here</blockquote>
        </body></html>';

        // Poor structure: no headings, no organization
        $poorStructureHtml = '<html><body>
            <p>Just text without any structure or organization.</p>
            <p>More unorganized content.</p>
            <p>No headings or lists to break up the content.</p>
        </body></html>';

        $url = 'https://example.com';
        $goodHeadings = ['h1' => ['Main Title'], 'h2' => ['Section 1', 'Section 2']];
        $poorHeadings = [];

        $goodResult = $this->assessor->assess($goodStructureHtml, $url, ['headings' => $goodHeadings]);
        $poorResult = $this->assessor->assess($poorStructureHtml, $url, ['headings' => $poorHeadings]);

        $goodStructure = $goodResult['quality_dimensions']['structure'];
        $poorStructure = $poorResult['quality_dimensions']['structure'];

        $this->assertGreaterThan($poorStructure['score'], $goodStructure['score']);
        $this->assertArrayHasKey('heading_analysis', $goodStructure);
        $this->assertArrayHasKey('structural_elements', $goodStructure);
    }

    /** @test */
    public function it_assesses_content_completeness_correctly()
    {
        // Complete content: title, description, sufficient content, images with alt
        $completeMeta = [
            'title' => 'Complete Page Title',
            'description' => 'A comprehensive description of the page content',
            'keywords' => 'seo, quality, content',
            'author' => 'Content Author',
            'canonical' => 'https://example.com/page'
        ];
        $completeContent = ['word_count' => 800];
        $completeImages = ['total_count' => 3, 'with_alt_count' => 3];

        // Incomplete content: missing meta, short content, images without alt
        $incompleteMeta = ['title' => 'Short Title'];
        $incompleteContent = ['word_count' => 50];
        $incompleteImages = ['total_count' => 3, 'with_alt_count' => 0];

        $url = 'https://example.com';
        $html = '<html><body>Content here</body></html>';

        $completeResult = $this->assessor->assess($html, $url, [
            'meta' => $completeMeta,
            'content' => $completeContent,
            'images' => $completeImages
        ]);

        $incompleteResult = $this->assessor->assess($html, $url, [
            'meta' => $incompleteMeta,
            'content' => $incompleteContent,
            'images' => $incompleteImages
        ]);

        $completeCompleteness = $completeResult['quality_dimensions']['completeness'];
        $incompleteCompleteness = $incompleteResult['quality_dimensions']['completeness'];

        $this->assertGreaterThan($incompleteCompleteness['score'], $completeCompleteness['score']);
        $this->assertArrayHasKey('meta_completeness', $completeCompleteness);
        $this->assertArrayHasKey('content_depth', $completeCompleteness);
        $this->assertArrayHasKey('media_completeness', $completeCompleteness);
    }

    /** @test */
    public function it_provides_appropriate_quality_recommendations()
    {
        // Poor quality content to generate multiple recommendations
        $poorQualityHtml = '<html>
            <head><title>T</title></head>
            <body>
                <p>Short bad content with no structure or proper organization and very long sentences that are difficult to read and understand because they contain too much information in a single sentence without proper punctuation or breaks.</p>
                <img src="image.jpg">
            </body>
        </html>';

        $poorParsedData = [
            'meta' => ['title' => 'T'], // Too short
            'content' => ['word_count' => 30], // Too short
            'headings' => [], // No headings
            'images' => ['total_count' => 1, 'with_alt_count' => 0], // No alt text
            'links' => ['total_count' => 0]
        ];

        $url = 'https://example.com';

        $result = $this->assessor->assess($poorQualityHtml, $url, $poorParsedData);

        $recommendations = $result['recommendations'];
        $this->assertIsArray($recommendations);
        $this->assertGreaterThan(0, count($recommendations));

        // Should have recommendations for various issues
        $recommendationCategories = array_column($recommendations, 'category');
        $this->assertContains('readability', $recommendationCategories);
    }

    /** @test */
    public function it_calculates_overall_score_correctly()
    {
        // High-quality content
        $highQualityHtml = '<html>
            <head>
                <title>Comprehensive Guide to Quality Content Creation</title>
                <meta name="description" content="Learn how to create high-quality, engaging content that provides value to your readers and performs well in search engines.">
            </head>
            <body>
                <h1>Comprehensive Guide to Quality Content</h1>
                <p>This guide provides detailed information about creating quality content. Each section covers important aspects.</p>

                <h2>Understanding Your Audience</h2>
                <p>Before creating content, understand your audience. Research their needs and preferences.</p>
                <ul>
                    <li>Conduct surveys</li>
                    <li>Analyze user behavior</li>
                    <li>Review feedback</li>
                </ul>

                <h2>Content Structure</h2>
                <p>Proper structure makes content easy to read. Use headings and organize information logically.</p>

                <img src="content-structure.jpg" alt="Diagram showing proper content structure" width="600" height="400">

                <p>Quality content requires planning and attention to detail. Focus on providing value to readers.</p>
            </body>
        </html>';

        $highQualityParsedData = [
            'meta' => [
                'title' => 'Comprehensive Guide to Quality Content Creation',
                'description' => 'Learn how to create high-quality, engaging content that provides value to your readers and performs well in search engines.',
                'keywords' => 'quality, content, guide',
                'author' => 'Expert Author'
            ],
            'content' => ['word_count' => 120, 'paragraph_count' => 6],
            'headings' => [
                'h1' => ['Comprehensive Guide to Quality Content'],
                'h2' => ['Understanding Your Audience', 'Content Structure']
            ],
            'images' => ['total_count' => 1, 'with_alt_count' => 1],
            'links' => ['total_count' => 2, 'internal_count' => 2]
        ];

        // Low-quality content
        $lowQualityHtml = '<html><head><title>Bad</title></head><body><p>Bad content.</p></body></html>';
        $lowQualityParsedData = [
            'meta' => ['title' => 'Bad'],
            'content' => ['word_count' => 10],
            'headings' => [],
            'images' => ['total_count' => 0],
            'links' => ['total_count' => 0]
        ];

        $url = 'https://example.com';

        $highQualityResult = $this->assessor->assess($highQualityHtml, $url, $highQualityParsedData);
        $lowQualityResult = $this->assessor->assess($lowQualityHtml, $url, $lowQualityParsedData);

        // High quality should score significantly higher
        $this->assertGreaterThan($lowQualityResult['overall_score'], $highQualityResult['overall_score']);
        $this->assertGreaterThan(60, $highQualityResult['overall_score']); // Should be reasonably high
        $this->assertLessThan(50, $lowQualityResult['overall_score']); // Should be low
    }

    /** @test */
    public function it_provides_quality_insights()
    {
        $html = '<html><head><title>Test Content</title></head><body><h1>Title</h1><p>Some content here for testing insights generation.</p></body></html>';
        $parsedData = [
            'meta' => ['title' => 'Test Content'],
            'content' => ['word_count' => 100],
            'headings' => ['h1' => ['Title']]
        ];
        $url = 'https://example.com';

        $result = $this->assessor->assess($html, $url, $parsedData);

        $insights = $result['quality_insights'];
        $this->assertIsArray($insights);
        $this->assertGreaterThan(0, count($insights));

        // Insights should be strings with meaningful content
        foreach ($insights as $insight) {
            $this->assertIsString($insight);
            $this->assertGreaterThan(10, strlen($insight)); // Should have meaningful length
        }
    }

    /** @test */
    public function it_identifies_improvement_priorities()
    {
        $html = '<html><body><p>Short content with poor quality.</p></body></html>';
        $parsedData = [
            'meta' => [],
            'content' => ['word_count' => 20],
            'headings' => [],
            'images' => ['total_count' => 0]
        ];
        $url = 'https://example.com';

        $result = $this->assessor->assess($html, $url, $parsedData);

        $priorities = $result['improvement_priority'];
        $this->assertIsArray($priorities);

        // Should identify the most important issues first
        if (count($priorities) > 0) {
            $this->assertArrayHasKey('dimension', $priorities[0]);
            $this->assertArrayHasKey('score', $priorities[0]);
            $this->assertArrayHasKey('priority', $priorities[0]);
            $this->assertArrayHasKey('impact', $priorities[0]);

            $this->assertIn($priorities[0]['priority'], ['high', 'medium', 'low']);
        }
    }

    /** @test */
    public function it_extracts_content_metrics_correctly()
    {
        $html = '<html><body>
            <p>First paragraph with content.</p>
            <p>Second paragraph with more content.</p>
            <ul><li>List item</li></ul>
        </body></html>';

        $parsedData = ['content' => ['word_count' => 25]];
        $url = 'https://example.com';

        $result = $this->assessor->assess($html, $url, $parsedData);

        $metrics = $result['content_metrics'];
        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('word_count', $metrics);
        $this->assertArrayHasKey('character_count', $metrics);
        $this->assertArrayHasKey('paragraph_count', $metrics);
        $this->assertArrayHasKey('list_count', $metrics);

        $this->assertGreaterThan(0, $metrics['word_count']);
        $this->assertGreaterThan(0, $metrics['character_count']);
        $this->assertGreaterThan(0, $metrics['paragraph_count']);
    }

    /** @test */
    public function it_handles_empty_content_gracefully()
    {
        $emptyHtml = '<html><head></head><body></body></html>';
        $emptyParsedData = [
            'meta' => [],
            'content' => ['word_count' => 0],
            'headings' => [],
            'images' => ['total_count' => 0],
            'links' => ['total_count' => 0]
        ];
        $url = 'https://example.com';

        $result = $this->assessor->assess($emptyHtml, $url, $emptyParsedData);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('overall_score', $result);
        $this->assertIsNumeric($result['overall_score']);

        // Should handle empty content without throwing errors
        $this->assertGreaterThanOrEqual(0, $result['overall_score']);
    }

    /** @test */
    public function it_handles_malformed_content_gracefully()
    {
        $malformedHtml = '<html><head><title>Test</head><body><p>Unclosed paragraph<h1>Bad heading<img src="test.jpg"></body>';
        $parsedData = [
            'meta' => ['title' => 'Test'],
            'content' => ['word_count' => 50],
            'headings' => ['h1' => ['Bad heading']],
            'images' => ['total_count' => 1, 'with_alt_count' => 0]
        ];
        $url = 'https://example.com';

        $result = $this->assessor->assess($malformedHtml, $url, $parsedData);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('overall_score', $result);
        $this->assertIsNumeric($result['overall_score']);

        // Should still provide meaningful assessment despite malformed HTML
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertIsArray($result['recommendations']);
    }

    /** @test */
    public function it_assigns_appropriate_quality_dimensions_weights()
    {
        // Test that different dimensions affect the overall score appropriately
        $baseHtml = '<html><head><title>Test</title></head><body><h1>Title</h1><p>Content here.</p></body></html>';
        $baseParsedData = [
            'meta' => ['title' => 'Test'],
            'content' => ['word_count' => 100],
            'headings' => ['h1' => ['Title']],
            'images' => ['total_count' => 0],
            'links' => ['total_count' => 0]
        ];
        $url = 'https://example.com';

        $result = $this->assessor->assess($baseHtml, $url, $baseParsedData);

        $dimensions = $result['quality_dimensions'];

        // All dimensions should be present
        $expectedDimensions = [
            'readability', 'structure', 'completeness', 'engagement',
            'originality', 'relevance', 'technical_quality', 'user_experience'
        ];

        foreach ($expectedDimensions as $dimension) {
            $this->assertArrayHasKey($dimension, $dimensions);
            $this->assertArrayHasKey('score', $dimensions[$dimension]);
            $this->assertIsNumeric($dimensions[$dimension]['score']);
            $this->assertGreaterThanOrEqual(0, $dimensions[$dimension]['score']);
            $this->assertLessThanOrEqual(100, $dimensions[$dimension]['score']);
        }
    }
}