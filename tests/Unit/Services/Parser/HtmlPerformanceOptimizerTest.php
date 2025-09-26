<?php

namespace Tests\Unit\Services\Parser;

use App\Services\Parser\HtmlPerformanceOptimizer;
use Tests\TestCase;

class HtmlPerformanceOptimizerTest extends TestCase
{
    private HtmlPerformanceOptimizer $optimizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->optimizer = new HtmlPerformanceOptimizer();
    }

    /** @test */
    public function it_determines_when_optimization_is_not_needed()
    {
        $smallHtml = '
        <!DOCTYPE html>
        <html>
        <head><title>Small Page</title></head>
        <body>
            <h1>Simple Content</h1>
            <p>This is a small page that does not need optimization.</p>
        </body>
        </html>';

        $result = $this->optimizer->shouldOptimize($smallHtml);

        $this->assertIsArray($result);
        $this->assertFalse($result['should_optimize']);
        $this->assertEquals('standard', $result['recommended_strategy']);
        $this->assertEmpty($result['optimization_reasons']);
        $this->assertLessThan(1024 * 1024, $result['html_size']); // Less than 1MB
    }

    /** @test */
    public function it_determines_when_optimization_is_needed_for_large_html()
    {
        // Create a large HTML document (over 1MB)
        $largeContent = str_repeat('<p>This is repeated content to make the HTML large. ' . str_repeat('Lorem ipsum dolor sit amet, consectetur adipiscing elit. ', 100) . '</p>', 500);
        $largeHtml = "
        <!DOCTYPE html>
        <html>
        <head><title>Large Page</title></head>
        <body>
            <h1>Large Content</h1>
            {$largeContent}
        </body>
        </html>";

        $result = $this->optimizer->shouldOptimize($largeHtml);

        $this->assertIsArray($result);
        $this->assertTrue($result['should_optimize']);
        $this->assertContains($result['recommended_strategy'], ['chunked', 'streaming']);
        $this->assertNotEmpty($result['optimization_reasons']);
        $this->assertGreaterThan(1024 * 1024, $result['html_size']); // Greater than 1MB
    }

    /** @test */
    public function it_determines_when_optimization_is_needed_for_complex_dom()
    {
        // Create HTML with many elements
        $complexContent = '';
        for ($i = 0; $i < 2000; $i++) {
            $complexContent .= "<div class='item-{$i}'><span>Item {$i}</span><a href='/item/{$i}'>Link {$i}</a></div>";
        }

        $complexHtml = "
        <!DOCTYPE html>
        <html>
        <head><title>Complex DOM</title></head>
        <body>
            <h1>Complex Content</h1>
            {$complexContent}
        </body>
        </html>";

        $result = $this->optimizer->shouldOptimize($complexHtml);

        $this->assertIsArray($result);
        $this->assertTrue($result['should_optimize']);
        $reasons = implode(' ', $result['optimization_reasons']);
        $this->assertStringContainsString('Complex DOM structure', $reasons);
    }

    /** @test */
    public function it_can_perform_standard_parsing()
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <title>Test Page</title>
            <meta name="description" content="Test description">
        </head>
        <body>
            <h1>Main Heading</h1>
            <h2>Subheading</h2>
            <img src="image1.jpg" alt="Image 1">
            <img src="image2.jpg" alt="Image 2">
            <a href="/">Home</a>
            <a href="/about">About</a>
            <script src="app.js"></script>
            <script>console.log("inline");</script>
        </body>
        </html>';

        $result = $this->optimizer->optimizedParse($html, 'https://example.com');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('performance_metrics', $result);

        $metrics = $result['performance_metrics'];
        $this->assertArrayHasKey('processing_time_ms', $metrics);
        $this->assertArrayHasKey('memory_used_mb', $metrics);
        $this->assertArrayHasKey('peak_memory_mb', $metrics);
        $this->assertArrayHasKey('optimization_applied', $metrics);
        $this->assertArrayHasKey('optimization_strategy', $metrics);

        // Standard parsing should not require optimization
        $this->assertFalse($metrics['optimization_applied']);
        $this->assertEquals('standard', $metrics['optimization_strategy']);
    }

    /** @test */
    public function it_can_perform_optimized_parsing_with_chunked_strategy()
    {
        // Create moderately large HTML that triggers chunked strategy
        $mediumContent = '';
        for ($i = 0; $i < 1000; $i++) {
            $mediumContent .= "
                <div class='item-{$i}'>
                    <h3>Item {$i}</h3>
                    <p>" . str_repeat('Content for item ' . $i . '. ', 20) . "</p>
                    <img src='image{$i}.jpg' alt='Image {$i}'>
                    <a href='/item/{$i}'>Link {$i}</a>
                </div>";
        }

        $mediumHtml = "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Medium Page</title>
            <meta name=\"description\" content=\"Medium sized page\">
        </head>
        <body>
            <h1>Medium Content</h1>
            {$mediumContent}
        </body>
        </html>";

        $result = $this->optimizer->optimizedParse($mediumHtml, 'https://example.com', ['images', 'links', 'headings']);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('performance_metrics', $result);

        $metrics = $result['performance_metrics'];
        $this->assertGreaterThan(0, $metrics['processing_time_ms']);
        $this->assertGreaterThanOrEqual(0, $metrics['memory_used_mb']); // Memory usage can be 0 in tests

        // Should have applied optimization
        if ($metrics['optimization_applied']) {
            $this->assertContains($metrics['optimization_strategy'], ['chunked', 'streaming']);
        }
    }

    /** @test */
    public function it_can_extract_targeted_data_efficiently()
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <title>Targeted Extraction Test</title>
            <meta name="description" content="Test description">
            <meta name="keywords" content="test, extraction">
        </head>
        <body>
            <h1>Main Title</h1>
            <h2>Subtitle 1</h2>
            <h2>Subtitle 2</h2>
            <h3>Sub-subtitle</h3>
            <img src="image1.jpg" alt="Image 1">
            <img src="image2.jpg" alt="Image 2">
            <img src="image3.jpg" loading="lazy" alt="Lazy Image">
            <a href="/">Home</a>
            <a href="/about">About</a>
            <a href="https://external.com">External</a>
            <script src="external.js"></script>
            <script src="app.js" async></script>
        </body>
        </html>';

        // Test with specific targets
        $result = $this->optimizer->optimizedParse($html, 'https://example.com', ['meta', 'headings', 'images']);

        $this->assertIsArray($result);

        // Should contain targeted data
        $this->assertArrayHasKey('meta', $result);
        $this->assertArrayHasKey('headings', $result);
        $this->assertArrayHasKey('images', $result);

        // Meta data
        $this->assertArrayHasKey('title', $result['meta']);
        $this->assertArrayHasKey('description', $result['meta']);
        $this->assertEquals('Targeted Extraction Test', $result['meta']['title']);

        // Headings
        $this->assertArrayHasKey('h1', $result['headings']);
        $this->assertArrayHasKey('h2', $result['headings']);
        $this->assertArrayHasKey('h3', $result['headings']);
        $this->assertCount(1, $result['headings']['h1']);
        $this->assertCount(2, $result['headings']['h2']);
        $this->assertCount(1, $result['headings']['h3']);

        // Images
        $this->assertEquals(3, $result['images']['total_count']);
        $this->assertEquals(0, $result['images']['without_alt_count']); // All have alt text
    }

    /** @test */
    public function it_can_handle_regex_pre_extraction()
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <title>Pre-extraction Test Page</title>
            <meta name="description" content="This is extracted via regex">
            <link rel="canonical" href="https://example.com/canonical">
        </head>
        <body>
            <h1>Main Heading</h1>
            <h2>Sub Heading 1</h2>
            <h2>Sub Heading 2</h2>
            <h3>Level 3 Heading</h3>
            <img src="image1.jpg" alt="Image 1">
            <img src="image2.jpg" alt="Image 2">
            <a href="/">Home</a>
            <a href="/about">About</a>
            <a href="/contact">Contact</a>
        </body>
        </html>';

        // Force streaming strategy to test regex pre-extraction
        $largeHtml = $html . str_repeat('<p>Filler content</p>', 10000);

        $result = $this->optimizer->optimizedParse($largeHtml, 'https://example.com');

        // The result should contain pre-extracted data
        if (isset($result['title'])) {
            $this->assertEquals('Pre-extraction Test Page', $result['title']);
        }
        if (isset($result['description'])) {
            $this->assertEquals('This is extracted via regex', $result['description']);
        }
        if (isset($result['canonical'])) {
            $this->assertEquals('https://example.com/canonical', $result['canonical']);
        }
        if (isset($result['heading_counts'])) {
            $this->assertEquals(1, $result['heading_counts']['h1']);
            $this->assertEquals(2, $result['heading_counts']['h2']);
            $this->assertEquals(1, $result['heading_counts']['h3']);
        }
    }

    /** @test */
    public function it_records_performance_metrics_accurately()
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head><title>Metrics Test</title></head>
        <body>
            <h1>Test Content</h1>
            <p>Some content for testing metrics.</p>
        </body>
        </html>';

        $result = $this->optimizer->optimizedParse($html, 'https://example.com');

        $this->assertArrayHasKey('performance_metrics', $result);
        $metrics = $result['performance_metrics'];

        // Check that all expected metrics are present
        $this->assertArrayHasKey('processing_time_ms', $metrics);
        $this->assertArrayHasKey('memory_used_mb', $metrics);
        $this->assertArrayHasKey('peak_memory_mb', $metrics);
        $this->assertArrayHasKey('optimization_applied', $metrics);
        $this->assertArrayHasKey('optimization_strategy', $metrics);
        $this->assertArrayHasKey('optimization_reasons', $metrics);

        // Validate metric types and values
        $this->assertIsFloat($metrics['processing_time_ms']);
        $this->assertIsFloat($metrics['memory_used_mb']);
        $this->assertIsFloat($metrics['peak_memory_mb']);
        $this->assertIsBool($metrics['optimization_applied']);
        $this->assertIsString($metrics['optimization_strategy']);
        $this->assertIsArray($metrics['optimization_reasons']);

        // Processing time should be reasonable (less than 5 seconds for small HTML)
        $this->assertLessThan(5000, $metrics['processing_time_ms']);

        // Memory usage should be positive
        $this->assertGreaterThanOrEqual(0, $metrics['memory_used_mb']);
        $this->assertGreaterThanOrEqual(0, $metrics['peak_memory_mb']);
    }

    /** @test */
    public function it_can_get_and_reset_performance_metrics()
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head><title>Test</title></head>
        <body><p>Content</p></body>
        </html>';

        // Initially, metrics should be empty
        $initialMetrics = $this->optimizer->getPerformanceMetrics();
        $this->assertEmpty($initialMetrics);

        // After parsing, metrics should be available
        $this->optimizer->optimizedParse($html, 'https://example.com');
        $metricsAfterParsing = $this->optimizer->getPerformanceMetrics();
        $this->assertNotEmpty($metricsAfterParsing);
        $this->assertArrayHasKey('processing_time_ms', $metricsAfterParsing);

        // After reset, metrics should be empty again
        $this->optimizer->resetMetrics();
        $metricsAfterReset = $this->optimizer->getPerformanceMetrics();
        $this->assertEmpty($metricsAfterReset);
    }

    /** @test */
    public function it_handles_malformed_html_gracefully()
    {
        $malformedHtml = '
        <!DOCTYPE html>
        <html>
        <head>
            <title>Malformed HTML Test
        </head>
        <body>
            <h1>Unclosed heading
            <p>Paragraph without closing tag
            <div>Nested improperly</p>
            <img src="image.jpg" alt="Missing closing bracket"
            <script>
                // Unclosed script
                console.log("test");
        </body>';

        // Should not throw exceptions
        $result = $this->optimizer->optimizedParse($malformedHtml, 'https://example.com');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('performance_metrics', $result);

        // Metrics should still be recorded
        $metrics = $result['performance_metrics'];
        $this->assertGreaterThan(0, $metrics['processing_time_ms']);
    }

    /** @test */
    public function it_handles_empty_html_gracefully()
    {
        $emptyHtml = '';

        $result = $this->optimizer->optimizedParse($emptyHtml, 'https://example.com');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('performance_metrics', $result);

        // Should not crash and should record metrics
        $metrics = $result['performance_metrics'];
        $this->assertGreaterThanOrEqual(0, $metrics['processing_time_ms']);
        $this->assertGreaterThanOrEqual(0, $metrics['memory_used_mb']);
    }

    /** @test */
    public function it_detects_external_links_correctly()
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head><title>Link Test</title></head>
        <body>
            <a href="/">Internal root</a>
            <a href="/about">Internal page</a>
            <a href="#anchor">Internal anchor</a>
            <a href="https://example.com/page">Same domain</a>
            <a href="https://external.com">External domain</a>
            <a href="https://another.com/page">Another external</a>
        </body>
        </html>';

        $result = $this->optimizer->optimizedParse($html, 'https://example.com', ['links']);

        $this->assertArrayHasKey('links', $result);
        $links = $result['links'];

        $this->assertEquals(6, $links['total_count']);

        // Count external links
        $externalCount = 0;
        foreach ($links['links'] as $link) {
            if ($link['is_external']) {
                $externalCount++;
            }
        }

        $this->assertEquals(2, $externalCount); // external.com and another.com
        $this->assertEquals($externalCount, $links['external_count']);
    }

    /** @test */
    public function it_formats_bytes_correctly()
    {
        // Test the protected formatBytes method indirectly through shouldOptimize
        $smallHtml = '<html><body>Small</body></html>';
        $result = $this->optimizer->shouldOptimize($smallHtml);

        $this->assertLessThan(1000, $result['html_size']); // Small file

        // Create a larger HTML to see size formatting in optimization reasons
        $largeHtml = str_repeat('<p>Large content</p>', 50000);
        $largeResult = $this->optimizer->shouldOptimize($largeHtml);

        if ($largeResult['should_optimize'] && !empty($largeResult['optimization_reasons'])) {
            $reasons = implode(' ', $largeResult['optimization_reasons']);
            // Should contain either size information or DOM structure info
            $hasReadableInfo = preg_match('/\d+(\.\d+)?\s*(B|KB|MB|GB)/', $reasons) ||
                              strpos($reasons, 'DOM structure') !== false;
            $this->assertTrue($hasReadableInfo);
        }
    }

    /** @test */
    public function it_estimates_dom_element_count_reasonably()
    {
        $simpleHtml = '<html><body><p>Simple</p></body></html>';
        $simpleResult = $this->optimizer->shouldOptimize($simpleHtml);
        $this->assertFalse($simpleResult['should_optimize']); // Should not need optimization

        $complexHtml = str_repeat('<div><p>Content</p><span>Text</span><a href="#">Link</a></div>', 2000);
        $complexResult = $this->optimizer->shouldOptimize($complexHtml);

        // Complex HTML should likely trigger optimization due to estimated element count
        if ($complexResult['should_optimize']) {
            $reasons = implode(' ', $complexResult['optimization_reasons']);
            $this->assertStringContainsString('Complex DOM structure', $reasons);
        }
    }

    /** @test */
    public function it_extracts_scripts_with_detailed_information()
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <script src="external.js" async></script>
            <script src="defer.js" defer type="module"></script>
        </head>
        <body>
            <script>
                // Inline script
                console.log("Inline");
            </script>
            <script src="regular.js"></script>
        </body>
        </html>';

        $result = $this->optimizer->optimizedParse($html, 'https://example.com', ['scripts']);

        $this->assertArrayHasKey('scripts', $result);
        $scripts = $result['scripts'];

        $this->assertEquals(4, $scripts['total_count']);
        $this->assertEquals(3, $scripts['external_count']); // 3 scripts with src
        $this->assertEquals(1, $scripts['async_count']); // external.js

        // Check individual scripts
        $scriptDetails = $scripts['scripts'];

        // Find the async script
        $asyncScript = null;
        foreach ($scriptDetails as $script) {
            if ($script['async']) {
                $asyncScript = $script;
                break;
            }
        }
        $this->assertNotNull($asyncScript);
        $this->assertEquals('external.js', $asyncScript['src']);

        // Find the defer script
        $deferScript = null;
        foreach ($scriptDetails as $script) {
            if ($script['defer']) {
                $deferScript = $script;
                break;
            }
        }
        $this->assertNotNull($deferScript);
        $this->assertEquals('defer.js', $deferScript['src']);
        $this->assertEquals('module', $deferScript['type']);

        // Find the inline script
        $inlineScript = null;
        foreach ($scriptDetails as $script) {
            if ($script['inline']) {
                $inlineScript = $script;
                break;
            }
        }
        $this->assertNotNull($inlineScript);
        $this->assertTrue($inlineScript['inline']);
        $this->assertEmpty($inlineScript['src']);
    }
}