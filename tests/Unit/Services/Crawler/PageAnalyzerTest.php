<?php

namespace Tests\Unit\Services\Crawler;

use App\Services\Crawler\PageAnalyzer;
use App\Services\Crawler\DomExtractor;
use App\Services\Crawler\ContentExtractor;
use App\Services\Crawler\MetaDataExtractor;
use Exception;
use PHPUnit\Framework\TestCase;
use Mockery;

class PageAnalyzerTest extends TestCase
{
    private PageAnalyzer $pageAnalyzer;
    private DomExtractor $domExtractor;
    private ContentExtractor $contentExtractor;
    private MetaDataExtractor $metaDataExtractor;
    private array $samplePageData;
    private array $spaPageData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->domExtractor = new DomExtractor();
        $this->contentExtractor = new ContentExtractor();
        $this->metaDataExtractor = new MetaDataExtractor();

        $this->pageAnalyzer = new PageAnalyzer(
            $this->domExtractor,
            $this->contentExtractor,
            $this->metaDataExtractor
        );

        $this->samplePageData = [
            'url' => 'https://example.com/test-page',
            'html' => $this->getSampleHtml(),
            'performance' => [
                'load_time' => 1200,
                'dom_content_loaded' => 800,
                'first_paint' => 600,
                'largest_contentful_paint' => 1000,
                'cumulative_layout_shift' => 0.1,
                'response_time' => 200
            ]
        ];

        $this->spaPageData = [
            'url' => 'https://example.com/spa-app',
            'html' => $this->getSpaHtml(),
            'performance' => [
                'load_time' => 2000,
                'dom_content_loaded' => 1500,
                'first_paint' => 1200,
                'largest_contentful_paint' => 1800
            ]
        ];
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testAnalyzeBasicStructure(): void
    {
        $result = $this->pageAnalyzer->analyze($this->samplePageData);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('meta', $result);
        $this->assertArrayHasKey('dom', $result);
        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('seo', $result);
        $this->assertArrayHasKey('performance', $result);
        $this->assertArrayHasKey('accessibility', $result);
        $this->assertArrayHasKey('mobile', $result);
        $this->assertArrayHasKey('social_media', $result);
        $this->assertArrayHasKey('structured_data', $result);
        $this->assertArrayHasKey('overall_score', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('analyzed_at', $result);

        $this->assertEquals('https://example.com/test-page', $result['url']);
        $this->assertIsFloat($result['overall_score']);
        $this->assertGreaterThanOrEqual(0, $result['overall_score']);
        $this->assertLessThanOrEqual(100, $result['overall_score']);
    }

    public function testAnalyzeTitleExtraction(): void
    {
        $result = $this->pageAnalyzer->analyze($this->samplePageData);
        $title = $result['title'];

        $this->assertIsArray($title);
        $this->assertArrayHasKey('text', $title);
        $this->assertArrayHasKey('length', $title);
        $this->assertArrayHasKey('word_count', $title);
        $this->assertArrayHasKey('optimal_length', $title);
        $this->assertArrayHasKey('seo_score', $title);

        $this->assertIsString($title['text']);
        $this->assertIsInt($title['length']);
        $this->assertIsInt($title['word_count']);
        $this->assertIsBool($title['optimal_length']);
        $this->assertIsFloat($title['seo_score']);
    }

    public function testAnalyzeSeo(): void
    {
        $result = $this->pageAnalyzer->analyze($this->samplePageData);
        $seo = $result['seo'];

        $this->assertIsArray($seo);
        $this->assertArrayHasKey('scores', $seo);
        $this->assertArrayHasKey('overall_score', $seo);
        $this->assertArrayHasKey('grade', $seo);
        $this->assertArrayHasKey('content_seo', $seo);
        $this->assertArrayHasKey('technical_seo', $seo);
        $this->assertArrayHasKey('recommendations', $seo);

        $scores = $seo['scores'];
        $this->assertArrayHasKey('title_score', $scores);
        $this->assertArrayHasKey('meta_description_score', $scores);
        $this->assertArrayHasKey('heading_structure_score', $scores);
        $this->assertArrayHasKey('content_quality_score', $scores);
        $this->assertArrayHasKey('internal_linking_score', $scores);
        $this->assertArrayHasKey('image_optimization_score', $scores);
        $this->assertArrayHasKey('structured_data_score', $scores);
        $this->assertArrayHasKey('technical_seo_score', $scores);

        foreach ($scores as $score) {
            $this->assertIsFloat($score);
            $this->assertGreaterThanOrEqual(0, $score);
            $this->assertLessThanOrEqual(100, $score);
        }

        $this->assertIsFloat($seo['overall_score']);
        $this->assertIsString($seo['grade']);
        $this->assertContains($seo['grade'], ['A', 'B', 'C', 'D', 'F']);
    }

    public function testAnalyzePerformance(): void
    {
        $result = $this->pageAnalyzer->analyze($this->samplePageData);
        $performance = $result['performance'];

        $this->assertIsArray($performance);
        $this->assertArrayHasKey('core_web_vitals', $performance);
        $this->assertArrayHasKey('loading_metrics', $performance);
        $this->assertArrayHasKey('page_metrics', $performance);
        $this->assertArrayHasKey('performance_score', $performance);
        $this->assertArrayHasKey('performance_grade', $performance);

        $coreWebVitals = $performance['core_web_vitals'];
        $this->assertArrayHasKey('largest_contentful_paint', $coreWebVitals);
        $this->assertArrayHasKey('first_input_delay', $coreWebVitals);
        $this->assertArrayHasKey('cumulative_layout_shift', $coreWebVitals);

        $loadingMetrics = $performance['loading_metrics'];
        $this->assertArrayHasKey('load_time', $loadingMetrics);
        $this->assertArrayHasKey('dom_content_loaded', $loadingMetrics);
        $this->assertArrayHasKey('first_paint', $loadingMetrics);

        $this->assertIsFloat($performance['performance_score']);
        $this->assertIsString($performance['performance_grade']);
        $this->assertContains($performance['performance_grade'], ['A', 'B', 'C', 'D', 'F']);
    }

    public function testAnalyzeAccessibility(): void
    {
        $result = $this->pageAnalyzer->analyze($this->samplePageData);
        $accessibility = $result['accessibility'];

        $this->assertIsArray($accessibility);
        $this->assertArrayHasKey('factors', $accessibility);
        $this->assertArrayHasKey('accessibility_score', $accessibility);
        $this->assertArrayHasKey('wcag_compliance', $accessibility);
        $this->assertArrayHasKey('recommendations', $accessibility);

        $factors = $accessibility['factors'];
        $this->assertArrayHasKey('semantic_structure', $factors);
        $this->assertArrayHasKey('dom_accessibility', $factors);
        $this->assertArrayHasKey('content_readability', $factors);
        $this->assertArrayHasKey('meta_accessibility', $factors);

        $this->assertIsFloat($accessibility['accessibility_score']);
        $this->assertGreaterThanOrEqual(0, $accessibility['accessibility_score']);
        $this->assertLessThanOrEqual(100, $accessibility['accessibility_score']);

        $wcagCompliance = $accessibility['wcag_compliance'];
        $this->assertArrayHasKey('level_a', $wcagCompliance);
        $this->assertArrayHasKey('level_aa', $wcagCompliance);
        $this->assertArrayHasKey('level_aaa', $wcagCompliance);
    }

    public function testAnalyzeMobileOptimization(): void
    {
        $result = $this->pageAnalyzer->analyze($this->samplePageData);
        $mobile = $result['mobile'];

        $this->assertIsArray($mobile);
        $this->assertArrayHasKey('viewport_optimization', $mobile);
        $this->assertArrayHasKey('mobile_meta_tags', $mobile);
        $this->assertArrayHasKey('touch_optimization', $mobile);
        $this->assertArrayHasKey('theme_customization', $mobile);
        $this->assertArrayHasKey('progressive_web_app', $mobile);
        $this->assertArrayHasKey('mobile_score', $mobile);
        $this->assertArrayHasKey('mobile_recommendations', $mobile);

        $this->assertIsInt($mobile['mobile_score']);
        $this->assertGreaterThanOrEqual(0, $mobile['mobile_score']);
        $this->assertLessThanOrEqual(100, $mobile['mobile_score']);

        $this->assertIsArray($mobile['mobile_recommendations']);
    }

    public function testAnalyzeSocialMedia(): void
    {
        $result = $this->pageAnalyzer->analyze($this->samplePageData);
        $socialMedia = $result['social_media'];

        $this->assertIsArray($socialMedia);
        $this->assertArrayHasKey('open_graph', $socialMedia);
        $this->assertArrayHasKey('twitter_card', $socialMedia);
        $this->assertArrayHasKey('facebook_meta', $socialMedia);
        $this->assertArrayHasKey('pinterest_meta', $socialMedia);
        $this->assertArrayHasKey('linkedin_optimization', $socialMedia);
        $this->assertArrayHasKey('social_media_score', $socialMedia);
        $this->assertArrayHasKey('social_recommendations', $socialMedia);

        $this->assertIsFloat($socialMedia['social_media_score']);
        $this->assertGreaterThanOrEqual(0, $socialMedia['social_media_score']);
        $this->assertLessThanOrEqual(100, $socialMedia['social_media_score']);

        $this->assertIsArray($socialMedia['social_recommendations']);
    }

    public function testAnalyzeStructuredData(): void
    {
        $result = $this->pageAnalyzer->analyze($this->samplePageData);
        $structuredData = $result['structured_data'];

        $this->assertIsArray($structuredData);
        $this->assertArrayHasKey('json_ld', $structuredData);
        $this->assertArrayHasKey('microdata', $structuredData);
        $this->assertArrayHasKey('rdfa', $structuredData);
        $this->assertArrayHasKey('dublin_core', $structuredData);
        $this->assertArrayHasKey('structured_data_score', $structuredData);
        $this->assertArrayHasKey('schema_recommendations', $structuredData);

        $this->assertIsFloat($structuredData['structured_data_score']);
        $this->assertGreaterThanOrEqual(0, $structuredData['structured_data_score']);
        $this->assertLessThanOrEqual(100, $structuredData['structured_data_score']);

        $this->assertIsArray($structuredData['schema_recommendations']);
    }

    public function testAnalyzeSPA(): void
    {
        $result = $this->pageAnalyzer->analyzeSPA($this->spaPageData);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('is_spa', $result);
        $this->assertArrayHasKey('spa_framework', $result);
        $this->assertArrayHasKey('spa_elements', $result);
        $this->assertArrayHasKey('dynamic_content', $result);
        $this->assertArrayHasKey('routing', $result);
        $this->assertArrayHasKey('state_management', $result);
        $this->assertArrayHasKey('lazy_loading', $result);
        $this->assertArrayHasKey('seo_challenges', $result);
        $this->assertArrayHasKey('recommendations', $result);

        $this->assertIsBool($result['is_spa']);
        $this->assertIsString($result['spa_framework']);
        $this->assertIsArray($result['spa_elements']);
        $this->assertIsArray($result['dynamic_content']);
        $this->assertIsArray($result['routing']);
        $this->assertIsArray($result['state_management']);
        $this->assertIsArray($result['lazy_loading']);
        $this->assertIsArray($result['seo_challenges']);
        $this->assertIsArray($result['recommendations']);

        // SPA should be detected
        $this->assertTrue($result['is_spa']);
        $this->assertEquals('angular', $result['spa_framework']);
    }

    public function testAnalyzeSections(): void
    {
        $selectors = [
            'header' => 'header',
            'main_content' => 'main',
            'sidebar' => 'aside',
            'footer' => 'footer'
        ];

        $result = $this->pageAnalyzer->analyzeSections($this->samplePageData, $selectors);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('sections', $result);
        $this->assertArrayHasKey('total_sections', $result);
        $this->assertArrayHasKey('combined_analysis', $result);

        $sections = $result['sections'];
        foreach ($selectors as $sectionName => $selector) {
            $this->assertArrayHasKey($sectionName, $sections);
            $section = $sections[$sectionName];
            $this->assertArrayHasKey('text', $section);
            $this->assertArrayHasKey('word_count', $section);
            $this->assertArrayHasKey('reading_time', $section);
        }

        $this->assertIsInt($result['total_sections']);
        $this->assertEquals(count($selectors), $result['total_sections']);
    }

    public function testAnalyzeAccessibilityCompliance(): void
    {
        $result = $this->pageAnalyzer->analyzeAccessibilityCompliance($this->samplePageData);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('content_accessibility', $result);
        $this->assertArrayHasKey('dom_accessibility', $result);
        $this->assertArrayHasKey('semantic_structure', $result);
        $this->assertArrayHasKey('meta_accessibility', $result);
        $this->assertArrayHasKey('overall_accessibility_score', $result);
        $this->assertArrayHasKey('wcag_compliance', $result);
        $this->assertArrayHasKey('accessibility_recommendations', $result);

        $contentAccessibility = $result['content_accessibility'];
        $this->assertIsArray($contentAccessibility);
        $this->assertArrayHasKey('reading_level', $contentAccessibility);
        $this->assertArrayHasKey('content_structure', $contentAccessibility);

        $metaAccessibility = $result['meta_accessibility'];
        $this->assertArrayHasKey('lang_attribute', $metaAccessibility);
        $this->assertArrayHasKey('viewport_responsive', $metaAccessibility);

        $this->assertIsFloat($result['overall_accessibility_score']);
        $this->assertGreaterThanOrEqual(0, $result['overall_accessibility_score']);
        $this->assertLessThanOrEqual(100, $result['overall_accessibility_score']);
    }

    public function testAnalyzeDuplicateContent(): void
    {
        $compareWith = [
            'This is sample content for testing duplicate detection.',
            'Completely different content that should not match.',
            'Some overlapping words but different overall meaning.'
        ];

        $result = $this->pageAnalyzer->analyzeDuplicateContent($this->samplePageData, $compareWith);

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
    }

    public function testComprehensiveRecommendations(): void
    {
        $result = $this->pageAnalyzer->analyze($this->samplePageData);
        $recommendations = $result['recommendations'];

        $this->assertIsArray($recommendations);

        // Should have category-specific recommendations
        $expectedCategories = ['seo', 'content', 'accessibility', 'mobile', 'social_media', 'metadata'];
        foreach ($expectedCategories as $category) {
            if (isset($recommendations[$category])) {
                $this->assertIsArray($recommendations[$category]);
            }
        }
    }

    public function testOverallScoreCalculation(): void
    {
        $result = $this->pageAnalyzer->analyze($this->samplePageData);

        // Overall score should be weighted average of component scores
        $seoScore = $result['seo']['overall_score'] ?? 0;
        $contentScore = $result['content']['content_quality']['overall_score'] ?? 0;
        $accessibilityScore = $result['accessibility']['accessibility_score'] ?? 0;
        $mobileScore = $result['mobile']['mobile_score'] ?? 0;
        $socialScore = $result['social_media']['social_media_score'] ?? 0;

        $expectedScore = ($seoScore * 0.3) + ($contentScore * 0.25) + ($accessibilityScore * 0.2) +
                         ($mobileScore * 0.15) + ($socialScore * 0.1);

        $this->assertEqualsWithDelta($expectedScore, $result['overall_score'], 0.1);
    }

    public function testAnalyzeWithEmptyHtml(): void
    {
        $emptyPageData = ['url' => 'https://example.com', 'html' => ''];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No HTML content provided for analysis');
        $this->pageAnalyzer->analyze($emptyPageData);
    }

    public function testAnalyzeWithMissingHtml(): void
    {
        $missingHtmlData = ['url' => 'https://example.com'];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No HTML content provided for analysis');
        $this->pageAnalyzer->analyze($missingHtmlData);
    }

    public function testAnalyzeSpaWithEmptyHtml(): void
    {
        $emptyPageData = ['url' => 'https://example.com', 'html' => ''];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No HTML content provided for SPA analysis');
        $this->pageAnalyzer->analyzeSPA($emptyPageData);
    }

    public function testAnalyzeSectionsWithEmptyHtml(): void
    {
        $emptyPageData = ['url' => 'https://example.com', 'html' => ''];
        $selectors = ['main' => 'main'];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No HTML content provided for section analysis');
        $this->pageAnalyzer->analyzeSections($emptyPageData, $selectors);
    }

    public function testDependencyInjection(): void
    {
        $mockDomExtractor = Mockery::mock(DomExtractor::class);
        $mockContentExtractor = Mockery::mock(ContentExtractor::class);
        $mockMetaDataExtractor = Mockery::mock(MetaDataExtractor::class);

        $mockDomExtractor->shouldReceive('extractFromHtml')
            ->once()
            ->andReturn(['headings' => [], 'links' => [], 'images' => []]);

        $mockContentExtractor->shouldReceive('extractFromHtml')
            ->once()
            ->andReturn(['text_analysis' => [], 'content_quality' => ['overall_score' => 75]]);

        $mockMetaDataExtractor->shouldReceive('extractFromHtml')
            ->once()
            ->andReturn(['basic_meta' => ['title' => ['content' => 'Test', 'length' => 4, 'word_count' => 1]]]);

        $pageAnalyzer = new PageAnalyzer($mockDomExtractor, $mockContentExtractor, $mockMetaDataExtractor);

        $result = $pageAnalyzer->analyze($this->samplePageData);

        $this->assertIsArray($result);
        $this->assertEquals('Test', $result['title']['text']);
    }

    public function testPerformanceGrading(): void
    {
        $fastPageData = array_merge($this->samplePageData, [
            'performance' => [
                'load_time' => 500,
                'largest_contentful_paint' => 1000,
                'cumulative_layout_shift' => 0.05
            ]
        ]);

        $slowPageData = array_merge($this->samplePageData, [
            'performance' => [
                'load_time' => 5000,
                'largest_contentful_paint' => 6000,
                'cumulative_layout_shift' => 0.5
            ]
        ]);

        $fastResult = $this->pageAnalyzer->analyze($fastPageData);
        $slowResult = $this->pageAnalyzer->analyze($slowPageData);

        $this->assertGreaterThan($slowResult['performance']['performance_score'],
                                 $fastResult['performance']['performance_score']);

        $this->assertContains($fastResult['performance']['performance_grade'], ['A', 'B']);
        $this->assertContains($slowResult['performance']['performance_grade'], ['D', 'F']);
    }

    private function getSampleHtml(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprehensive Test Page Title</title>
    <meta name="description" content="This is a comprehensive test page description for analyzing page content and metadata extraction capabilities">
    <meta name="keywords" content="test, analysis, seo, content, metadata">
    <meta name="author" content="Test Author">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://example.com/test-page">

    <!-- Open Graph -->
    <meta property="og:title" content="Test Page OG Title">
    <meta property="og:description" content="Test page OG description">
    <meta property="og:image" content="https://example.com/og-image.jpg">
    <meta property="og:url" content="https://example.com/test-page">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Test Page Twitter Title">
    <meta name="twitter:description" content="Test page Twitter description">

    <!-- Mobile -->
    <meta name="theme-color" content="#0066cc">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <link rel="stylesheet" href="/styles.css">
</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="/">Home</a></li>
                <li><a href="/about">About</a></li>
                <li><a href="https://external.com" rel="nofollow">External Link</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <h1>Main Page Title for Testing</h1>

        <section>
            <h2>Content Analysis Section</h2>
            <p>This paragraph contains comprehensive content for testing various analysis algorithms. The content includes multiple sentences to test readability calculations, keyword density analysis, and content quality assessment features.</p>

            <p>Additional content helps evaluate content depth and structure. This includes testing for proper paragraph organization, reading time calculations, and overall content quality metrics.</p>

            <h3>Image Testing Subsection</h3>
            <img src="/test-image.jpg" alt="Descriptive alt text for testing image accessibility" width="300" height="200">
            <img src="/missing-alt.jpg" width="300" height="200">

            <h3>List Testing</h3>
            <ul>
                <li>First list item for testing list extraction</li>
                <li>Second list item with more content</li>
                <li>Third list item for completeness</li>
            </ul>

            <ol>
                <li>Numbered list item one</li>
                <li>Numbered list item two</li>
            </ol>
        </section>

        <section>
            <h2>Forms and Interaction Testing</h2>
            <form action="/submit" method="post">
                <label for="name">Full Name:</label>
                <input type="text" id="name" name="name" required>

                <label for="email">Email Address:</label>
                <input type="email" id="email" name="email" required>

                <label for="message">Message:</label>
                <textarea id="message" name="message" rows="4"></textarea>

                <button type="submit">Submit Form</button>
            </form>
        </section>

        <section>
            <h2>Table Testing</h2>
            <table>
                <caption>Sample Data Table</caption>
                <thead>
                    <tr>
                        <th>Header One</th>
                        <th>Header Two</th>
                        <th>Header Three</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Data Cell 1</td>
                        <td>Data Cell 2</td>
                        <td>Data Cell 3</td>
                    </tr>
                    <tr>
                        <td>Data Cell 4</td>
                        <td>Data Cell 5</td>
                        <td>Data Cell 6</td>
                    </tr>
                </tbody>
            </table>
        </section>
    </main>

    <aside>
        <h3>Sidebar Content</h3>
        <p>This sidebar contains supplementary information for testing content section analysis and extraction capabilities.</p>
        <nav>
            <h4>Related Links</h4>
            <ul>
                <li><a href="/related-1">Related Article 1</a></li>
                <li><a href="/related-2">Related Article 2</a></li>
            </ul>
        </nav>
    </aside>

    <footer>
        <p>&copy; 2024 Test Website. All rights reserved.</p>
        <nav>
            <a href="/privacy">Privacy Policy</a>
            <a href="/terms">Terms of Service</a>
        </nav>
    </footer>

    <script src="/main.js"></script>
    <script>
        // Inline script for testing
        console.log('Page loaded');
    </script>

    <!-- JSON-LD Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Article",
        "headline": "Comprehensive Test Page",
        "author": {
            "@type": "Person",
            "name": "Test Author"
        },
        "datePublished": "2024-01-01",
        "description": "A comprehensive test page for analyzer testing"
    }
    </script>
</body>
</html>
HTML;
    }

    private function getSpaHtml(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Angular SPA Test Application</title>
</head>
<body ng-app="testApp">
    <div id="app" class="spa-container">
        <header>
            <nav class="main-navigation">
                <ul>
                    <li><a href="/" router-link>Home</a></li>
                    <li><a href="/about" router-link>About</a></li>
                    <li><a href="/contact" router-link>Contact</a></li>
                </ul>
            </nav>
        </header>

        <main class="main-content">
            <router-outlet></router-outlet>

            <div class="dynamic-content" data-router="main">
                <custom-component data-bind="mainData">
                    <h1>{{pageTitle}}</h1>
                    <p>{{pageDescription}}</p>
                </custom-component>

                <lazy-content>
                    <img loading="lazy" data-src="/lazy-image.jpg" alt="Lazy loaded image">
                    <div class="placeholder">Loading content...</div>
                </lazy-content>
            </div>

            <div ui-view="content"></div>
        </main>

        <aside class="sidebar">
            <widget-container>
                <news-widget></news-widget>
                <social-widget></social-widget>
            </widget-container>
        </aside>
    </div>

    <!-- SPA Framework Scripts -->
    <script src="/vendor/angular.min.js"></script>
    <script src="/vendor/angular-router.min.js"></script>
    <script src="/app/app.module.js"></script>
    <script src="/app/components/custom-component.js"></script>
    <script src="/app/services/api.service.js"></script>
    <script src="/app/app.routes.js"></script>
</body>
</html>
HTML;
    }
}