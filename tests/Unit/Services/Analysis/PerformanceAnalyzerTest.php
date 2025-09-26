<?php

namespace Tests\Unit\Services\Analysis;

use Tests\TestCase;
use App\Services\Analysis\PerformanceAnalyzer;

class PerformanceAnalyzerTest extends TestCase
{
    private PerformanceAnalyzer $performanceAnalyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->performanceAnalyzer = new PerformanceAnalyzer();
    }

    /** @test */
    public function it_can_analyze_basic_html_performance()
    {
        $html = '<html><head><title>Test</title></head><body><h1>Hello</h1><p>Content</p></body></html>';
        $url = 'https://example.com';
        $domData = [];
        $options = [];

        $result = $this->performanceAnalyzer->analyze($html, $url, $domData, $options);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('analyzed_at', $result);
        $this->assertArrayHasKey('analysis_duration_ms', $result);
        $this->assertArrayHasKey('overall_score', $result);
        $this->assertArrayHasKey('resource_optimization', $result);
        $this->assertArrayHasKey('image_optimization', $result);
        $this->assertArrayHasKey('script_optimization', $result);
        $this->assertArrayHasKey('css_optimization', $result);
        $this->assertArrayHasKey('content_optimization', $result);
        $this->assertArrayHasKey('cache_optimization', $result);
        $this->assertArrayHasKey('rendering_optimization', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('performance_budget', $result);
        $this->assertArrayHasKey('core_web_vitals_hints', $result);

        $this->assertIsNumeric($result['overall_score']);
        $this->assertGreaterThanOrEqual(0, $result['overall_score']);
        $this->assertLessThanOrEqual(100, $result['overall_score']);
    }

    /** @test */
    public function it_scores_optimized_html_higher()
    {
        // Well-optimized HTML with performance features
        $optimizedHtml = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Optimized Page</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preload" href="/critical.css" as="style">
    <style>
        /* Critical CSS inline */
        body { font-family: Arial; }
    </style>
</head>
<body>
    <img src="image.jpg" alt="Test" loading="lazy" width="300" height="200">
    <script src="script.js" async></script>
</body>
</html>';

        // Basic HTML without optimizations
        $basicHtml = '<html>
<head><title>Basic Page</title></head>
<body>
    <img src="image.jpg" alt="Test">
    <script src="script.js"></script>
</body>
</html>';

        $url = 'https://example.com';
        $domData = [];
        $options = [];

        $optimizedResult = $this->performanceAnalyzer->analyze($optimizedHtml, $url, $domData, $options);
        $basicResult = $this->performanceAnalyzer->analyze($basicHtml, $url, $domData, $options);

        $this->assertGreaterThan($basicResult['overall_score'], $optimizedResult['overall_score']);
    }

    /** @test */
    public function it_analyzes_image_optimization_correctly()
    {
        $htmlWithOptimizedImages = '<html><body>
            <img src="img1.webp" alt="Image 1" loading="lazy" width="300" height="200">
            <img src="img2.webp" alt="Image 2" loading="lazy" width="400" height="300" srcset="img2-small.webp 300w, img2-large.webp 800w">
        </body></html>';

        $htmlWithBasicImages = '<html><body>
            <img src="img1.jpg">
            <img src="img2.png">
        </body></html>';

        $url = 'https://example.com';

        $optimizedResult = $this->performanceAnalyzer->analyze($htmlWithOptimizedImages, $url);
        $basicResult = $this->performanceAnalyzer->analyze($htmlWithBasicImages, $url);

        $this->assertGreaterThan($basicResult['image_optimization']['score'], $optimizedResult['image_optimization']['score']);

        // Check specific metrics for optimized images
        $this->assertEquals(2, $optimizedResult['image_optimization']['total_images']);
        $this->assertEquals(2, $optimizedResult['image_optimization']['lazy_loading_images']);
        $this->assertEquals(1, $optimizedResult['image_optimization']['responsive_images']);
        $this->assertEquals(2, $optimizedResult['image_optimization']['webp_images']);
        $this->assertEquals(2, $optimizedResult['image_optimization']['dimensioned_images']);

        // Check basic images have lower optimization
        $this->assertEquals(2, $basicResult['image_optimization']['total_images']);
        $this->assertEquals(0, $basicResult['image_optimization']['lazy_loading_images']);
        $this->assertEquals(0, $basicResult['image_optimization']['responsive_images']);
        $this->assertEquals(0, $basicResult['image_optimization']['webp_images']);
    }

    /** @test */
    public function it_analyzes_script_optimization_correctly()
    {
        $htmlWithOptimizedScripts = '<html><body>
            <script src="script1.js" async></script>
            <script src="script2.js" defer></script>
        </body></html>';

        $htmlWithBlockingScripts = '<html><body>
            <script src="script1.js"></script>
            <script src="script2.js"></script>
            <script>console.log("inline");</script>
            <script>console.log("another inline");</script>
        </body></html>';

        $url = 'https://example.com';

        $optimizedResult = $this->performanceAnalyzer->analyze($htmlWithOptimizedScripts, $url);
        $blockingResult = $this->performanceAnalyzer->analyze($htmlWithBlockingScripts, $url);

        $this->assertGreaterThan($blockingResult['script_optimization']['score'], $optimizedResult['script_optimization']['score']);

        // Check optimized script metrics
        $this->assertEquals(2, $optimizedResult['script_optimization']['total_scripts']);
        $this->assertEquals(1, $optimizedResult['script_optimization']['async_scripts']);
        $this->assertEquals(1, $optimizedResult['script_optimization']['defer_scripts']);
        $this->assertEquals(0, $optimizedResult['script_optimization']['inline_scripts']);

        // Check blocking script metrics
        $this->assertEquals(4, $blockingResult['script_optimization']['total_scripts']);
        $this->assertEquals(0, $blockingResult['script_optimization']['async_scripts']);
        $this->assertEquals(0, $blockingResult['script_optimization']['defer_scripts']);
        $this->assertEquals(2, $blockingResult['script_optimization']['inline_scripts']);
    }

    /** @test */
    public function it_analyzes_css_optimization_correctly()
    {
        $htmlWithOptimizedCss = '<html>
<head>
    <style>body{font-family:Arial;color:#333}h1{font-size:24px}</style>
    <link rel="stylesheet" href="styles.css">
</head>
<body>Content</body>
</html>';

        $htmlWithBasicCss = '<html>
<head>
    <link rel="stylesheet" href="styles1.css">
    <link rel="stylesheet" href="styles2.css">
    <link rel="stylesheet" href="styles3.css">
    <link rel="stylesheet" href="styles4.css">
</head>
<body>Content</body>
</html>';

        $url = 'https://example.com';

        $optimizedResult = $this->performanceAnalyzer->analyze($htmlWithOptimizedCss, $url);
        $basicResult = $this->performanceAnalyzer->analyze($htmlWithBasicCss, $url);

        // Optimized version should score higher (fewer CSS files, has inline CSS)
        $this->assertGreaterThan($basicResult['css_optimization']['score'], $optimizedResult['css_optimization']['score']);

        // Check CSS metrics
        $this->assertEquals(1, $optimizedResult['css_optimization']['external_css_files']);
        $this->assertEquals(1, $optimizedResult['css_optimization']['inline_css_blocks']);

        $this->assertEquals(4, $basicResult['css_optimization']['external_css_files']);
        $this->assertEquals(0, $basicResult['css_optimization']['inline_css_blocks']);
    }

    /** @test */
    public function it_analyzes_content_optimization_correctly()
    {
        // Small, clean HTML
        $smallHtml = '<html><head><title>Small Page</title></head><body><h1>Title</h1><p>Short content.</p></body></html>';

        // Large HTML with excessive whitespace
        $largeHtml = '<html>
        <head>
            <title>Large Page</title>
        </head>
        <body>
            <h1>Title</h1>
            ' . str_repeat('<p>This is a paragraph with content that makes the page larger. </p>    ', 100) . '
        </body>
        </html>';

        $url = 'https://example.com';

        $smallResult = $this->performanceAnalyzer->analyze($smallHtml, $url);
        $largeResult = $this->performanceAnalyzer->analyze($largeHtml, $url);

        // Small HTML should score higher
        $this->assertGreaterThan($largeResult['content_optimization']['score'], $smallResult['content_optimization']['score']);

        // Check content metrics
        $this->assertLessThan($largeResult['content_optimization']['html_size_kb'], $smallResult['content_optimization']['html_size_kb']);
        $this->assertLessThan($largeResult['content_optimization']['dom_elements'], $smallResult['content_optimization']['dom_elements']);
    }

    /** @test */
    public function it_provides_appropriate_performance_recommendations()
    {
        $poorPerformanceHtml = '<html>
<head>
    <title>Poor Performance Page</title>
    <link rel="stylesheet" href="css1.css">
    <link rel="stylesheet" href="css2.css">
    <link rel="stylesheet" href="css3.css">
    <link rel="stylesheet" href="css4.css">
</head>
<body>
    <img src="large1.jpg">
    <img src="large2.jpg">
    <img src="large3.jpg">
    <script src="blocking1.js"></script>
    <script src="blocking2.js"></script>
    <script>console.log("inline1");</script>
    <script>console.log("inline2");</script>
    <script src="https://external1.com/script.js"></script>
    <script src="https://external2.com/script.js"></script>
    <script src="https://external3.com/script.js"></script>
    <script src="https://external4.com/script.js"></script>
</body>
</html>';

        $url = 'https://example.com';

        $result = $this->performanceAnalyzer->analyze($poorPerformanceHtml, $url);

        $recommendations = $result['recommendations'];
        $this->assertIsArray($recommendations);
        $this->assertGreaterThan(0, count($recommendations));

        // Should have recommendations for major issues
        $recommendationMessages = array_column($recommendations, 'message');

        // Check for expected recommendation types
        $hasImageRecommendation = false;
        $hasScriptRecommendation = false;
        $hasCssRecommendation = false;

        foreach ($recommendations as $rec) {
            if (strpos($rec['message'], 'lazy loading') !== false || strpos($rec['message'], 'image') !== false) {
                $hasImageRecommendation = true;
            }
            if (strpos($rec['message'], 'script') !== false || strpos($rec['message'], 'render-blocking') !== false) {
                $hasScriptRecommendation = true;
            }
            if (strpos($rec['message'], 'CSS') !== false) {
                $hasCssRecommendation = true;
            }
        }

        $this->assertTrue($hasImageRecommendation || $hasScriptRecommendation || $hasCssRecommendation,
            'Should have at least one performance recommendation');
    }

    /** @test */
    public function it_calculates_performance_budget_correctly()
    {
        $html = '<html>
<head>
    <title>Test Page</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <img src="image.jpg" alt="Test">
    <script src="script.js"></script>
</body>
</html>';

        $url = 'https://example.com';

        $result = $this->performanceAnalyzer->analyze($html, $url);

        $budget = $result['performance_budget'];
        $this->assertIsArray($budget);
        $this->assertArrayHasKey('estimated_total_size_kb', $budget);
        $this->assertArrayHasKey('html_size_kb', $budget);
        $this->assertArrayHasKey('estimated_css_size_kb', $budget);
        $this->assertArrayHasKey('estimated_js_size_kb', $budget);
        $this->assertArrayHasKey('estimated_image_size_kb', $budget);
        $this->assertArrayHasKey('budget_status', $budget);
        $this->assertArrayHasKey('recommendations', $budget);

        $this->assertIsNumeric($budget['estimated_total_size_kb']);
        $this->assertIsString($budget['budget_status']);
        $this->assertIsArray($budget['recommendations']);
    }

    /** @test */
    public function it_provides_core_web_vitals_hints()
    {
        $html = '<html>
<head>
    <title>Test</title>
    <link rel="preload" href="hero.jpg" as="image">
    <style>/* Critical CSS */</style>
</head>
<body>
    <img src="hero.jpg" alt="Hero" width="800" height="400">
    <script src="app.js" defer></script>
</body>
</html>';

        $url = 'https://example.com';

        $result = $this->performanceAnalyzer->analyze($html, $url);

        $vitals = $result['core_web_vitals_hints'];
        $this->assertIsArray($vitals);
        $this->assertArrayHasKey('lcp_optimization', $vitals);
        $this->assertArrayHasKey('fid_optimization', $vitals);
        $this->assertArrayHasKey('cls_optimization', $vitals);

        // Check LCP optimizations
        $lcp = $vitals['lcp_optimization'];
        $this->assertArrayHasKey('preload_hero_image', $lcp);
        $this->assertArrayHasKey('optimize_critical_path', $lcp);

        // Check FID optimizations
        $fid = $vitals['fid_optimization'];
        $this->assertArrayHasKey('defer_non_critical_js', $fid);

        // Check CLS optimizations
        $cls = $vitals['cls_optimization'];
        $this->assertArrayHasKey('image_dimensions_set', $cls);
    }

    /** @test */
    public function it_handles_empty_html_gracefully()
    {
        $emptyHtml = '';
        $url = 'https://example.com';

        $result = $this->performanceAnalyzer->analyze($emptyHtml, $url);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('overall_score', $result);
        $this->assertIsNumeric($result['overall_score']);

        // Should handle empty content without errors
        $this->assertEquals(0, $result['image_optimization']['total_images']);
        $this->assertEquals(0, $result['script_optimization']['total_scripts']);
    }

    /** @test */
    public function it_handles_malformed_html_gracefully()
    {
        $malformedHtml = '<html><head><title>Test</head><body><img src="test.jpg"><p>Unclosed paragraph<script>alert("test")</body>';
        $url = 'https://example.com';

        $result = $this->performanceAnalyzer->analyze($malformedHtml, $url);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('overall_score', $result);
        $this->assertIsNumeric($result['overall_score']);

        // Should still be able to analyze what it can
        $this->assertGreaterThanOrEqual(0, $result['image_optimization']['total_images']);
        $this->assertGreaterThanOrEqual(0, $result['script_optimization']['total_scripts']);
    }
}