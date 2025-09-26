<?php

namespace Tests\Unit\Services\Crawler;

use App\Services\Crawler\DomExtractor;
use Exception;
use PHPUnit\Framework\TestCase;

class DomExtractorTest extends TestCase
{
    private DomExtractor $domExtractor;
    private string $sampleHtml;
    private string $spaHtml;

    protected function setUp(): void
    {
        parent::setUp();
        $this->domExtractor = new DomExtractor();

        $this->sampleHtml = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Page</title>
</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="/">Home</a></li>
                <li><a href="/about">About</a></li>
                <li><a href="https://external.com">External</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <h1>Main Title</h1>
        <section>
            <h2>Section Title</h2>
            <p>This is a paragraph with some <strong>bold text</strong>.</p>
            <img src="/test.jpg" alt="Test image" width="100" height="100">
            <img src="/missing-alt.jpg">
            <ul>
                <li>Item 1</li>
                <li>Item 2</li>
            </ul>
            <table>
                <caption>Test Table</caption>
                <thead>
                    <tr>
                        <th>Header 1</th>
                        <th>Header 2</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Cell 1</td>
                        <td>Cell 2</td>
                    </tr>
                </tbody>
            </table>
        </section>
        <form action="/submit" method="post">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
            <button type="submit">Submit</button>
        </form>
    </main>
    <aside>
        <h3>Sidebar</h3>
        <p>Sidebar content</p>
    </aside>
    <footer>
        <p>&copy; 2024 Test Site</p>
    </footer>
    <script src="/script.js"></script>
    <script>console.log('Inline script');</script>
    <link rel="stylesheet" href="/style.css">
    <style>.test { color: red; }</style>
</body>
</html>
HTML;

        $this->spaHtml = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SPA Test</title>
</head>
<body ng-app="testApp">
    <div id="app">
        <router-outlet></router-outlet>
        <div class="dynamic-content" data-router="main">
            <custom-component></custom-component>
            <img loading="lazy" data-src="/lazy-image.jpg" alt="Lazy image">
        </div>
    </div>
    <script src="/angular.js"></script>
    <script src="/app.js"></script>
</body>
</html>
HTML;
    }

    public function testExtractFromHtmlBasicStructure(): void
    {
        $result = $this->domExtractor->extractFromHtml($this->sampleHtml);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('headings', $result);
        $this->assertArrayHasKey('links', $result);
        $this->assertArrayHasKey('images', $result);
        $this->assertArrayHasKey('forms', $result);
        $this->assertArrayHasKey('tables', $result);
        $this->assertArrayHasKey('lists', $result);
        $this->assertArrayHasKey('semantic_structure', $result);
        $this->assertArrayHasKey('accessibility', $result);
        $this->assertArrayHasKey('scripts', $result);
        $this->assertArrayHasKey('stylesheets', $result);
    }

    public function testExtractHeadings(): void
    {
        $result = $this->domExtractor->extractFromHtml($this->sampleHtml);
        $headings = $result['headings'];

        $this->assertIsArray($headings);
        $this->assertArrayHasKey('structure', $headings);
        $this->assertArrayHasKey('hierarchy', $headings);
        $this->assertArrayHasKey('h1_count', $headings);
        $this->assertArrayHasKey('total_count', $headings);
        $this->assertArrayHasKey('hierarchy_valid', $headings);

        $this->assertEquals(1, $headings['h1_count']);
        $this->assertEquals(3, $headings['total_count']); // h1, h2, h3
        $this->assertTrue($headings['hierarchy_valid']);

        $this->assertEquals('Main Title', $headings['structure'][0]['text']);
        $this->assertEquals(1, $headings['structure'][0]['level']);
    }

    public function testExtractLinks(): void
    {
        $result = $this->domExtractor->extractFromHtml($this->sampleHtml, ['base_url' => 'https://example.com']);
        $links = $result['links'];

        $this->assertIsArray($links);
        $this->assertArrayHasKey('links', $links);
        $this->assertArrayHasKey('total_count', $links);
        $this->assertArrayHasKey('internal_count', $links);
        $this->assertArrayHasKey('external_count', $links);

        $this->assertEquals(3, $links['total_count']);
        $this->assertEquals(2, $links['internal_count']); // / and /about
        $this->assertEquals(1, $links['external_count']); // https://external.com

        // Test link classification
        $linkData = $links['links'];
        $this->assertEquals('internal', $linkData[0]['type']);
        $this->assertEquals('internal', $linkData[1]['type']);
        $this->assertEquals('external', $linkData[2]['type']);
        $this->assertTrue($linkData[2]['external']);
    }

    public function testExtractImages(): void
    {
        $result = $this->domExtractor->extractFromHtml($this->sampleHtml);
        $images = $result['images'];

        $this->assertIsArray($images);
        $this->assertArrayHasKey('images', $images);
        $this->assertArrayHasKey('total_count', $images);
        $this->assertArrayHasKey('missing_alt_count', $images);

        $this->assertEquals(2, $images['total_count']);
        $this->assertEquals(1, $images['missing_alt_count']); // One image without alt

        $imageData = $images['images'];
        $this->assertEquals('/test.jpg', $imageData[0]['src']);
        $this->assertEquals('Test image', $imageData[0]['alt']);
        $this->assertTrue($imageData[0]['has_alt']);

        $this->assertEquals('/missing-alt.jpg', $imageData[1]['src']);
        $this->assertEquals('', $imageData[1]['alt']);
        $this->assertFalse($imageData[1]['has_alt']);
    }

    public function testExtractForms(): void
    {
        $result = $this->domExtractor->extractFromHtml($this->sampleHtml);
        $forms = $result['forms'];

        $this->assertIsArray($forms);
        $this->assertArrayHasKey('forms', $forms);
        $this->assertArrayHasKey('total_count', $forms);

        $this->assertEquals(1, $forms['total_count']);

        $formData = $forms['forms'][0];
        $this->assertEquals('/submit', $formData['action']);
        $this->assertEquals('POST', $formData['method']);
        $this->assertIsArray($formData['inputs']);
        $this->assertIsArray($formData['labels']);
        $this->assertIsArray($formData['buttons']);

        $this->assertEquals(1, count($formData['inputs'])); // email input
        $this->assertEquals(1, count($formData['labels'])); // email label
        $this->assertEquals(1, count($formData['buttons'])); // submit button
    }

    public function testExtractTables(): void
    {
        $result = $this->domExtractor->extractFromHtml($this->sampleHtml);
        $tables = $result['tables'];

        $this->assertIsArray($tables);
        $this->assertArrayHasKey('tables', $tables);
        $this->assertArrayHasKey('total_count', $tables);

        $this->assertEquals(1, $tables['total_count']);

        $tableData = $tables['tables'][0];
        $this->assertEquals('Test Table', $tableData['caption']);
        $this->assertIsArray($tableData['headers']);
        $this->assertEquals(2, count($tableData['headers'])); // Header 1, Header 2
        $this->assertTrue($tableData['has_thead']);
        $this->assertTrue($tableData['has_tbody']);
    }

    public function testExtractLists(): void
    {
        $result = $this->domExtractor->extractFromHtml($this->sampleHtml);
        $lists = $result['lists'];

        $this->assertIsArray($lists);
        $this->assertArrayHasKey('lists', $lists);
        $this->assertArrayHasKey('total_count', $lists);

        $this->assertEquals(2, $lists['total_count']); // nav ul + content ul

        $listData = $lists['lists'];
        foreach ($listData as $list) {
            $this->assertEquals('ul', $list['type']);
            $this->assertIsInt($list['items']);
        }
    }

    public function testExtractSemanticStructure(): void
    {
        $result = $this->domExtractor->extractFromHtml($this->sampleHtml);
        $semantic = $result['semantic_structure'];

        $this->assertIsArray($semantic);
        $this->assertArrayHasKey('semantic_elements', $semantic);
        $this->assertArrayHasKey('has_main', $semantic);
        $this->assertArrayHasKey('has_nav', $semantic);
        $this->assertArrayHasKey('has_header', $semantic);
        $this->assertArrayHasKey('has_footer', $semantic);

        $this->assertTrue($semantic['has_main']);
        $this->assertTrue($semantic['has_nav']);
        $this->assertTrue($semantic['has_header']);
        $this->assertTrue($semantic['has_footer']);

        $elements = $semantic['semantic_elements'];
        $this->assertEquals(1, $elements['header']);
        $this->assertEquals(1, $elements['nav']);
        $this->assertEquals(1, $elements['main']);
        $this->assertEquals(1, $elements['section']);
        $this->assertEquals(1, $elements['aside']);
        $this->assertEquals(1, $elements['footer']);
    }

    public function testExtractAccessibility(): void
    {
        $result = $this->domExtractor->extractFromHtml($this->sampleHtml);
        $accessibility = $result['accessibility'];

        $this->assertIsArray($accessibility);
        $this->assertArrayHasKey('lang_attribute', $accessibility);
        $this->assertArrayHasKey('skip_links', $accessibility);
        $this->assertArrayHasKey('aria_labels', $accessibility);

        $this->assertTrue($accessibility['lang_attribute']); // html has lang="en"
        $this->assertIsArray($accessibility['skip_links']);
        $this->assertIsInt($accessibility['aria_labels']);
    }

    public function testExtractScripts(): void
    {
        $result = $this->domExtractor->extractFromHtml($this->sampleHtml);
        $scripts = $result['scripts'];

        $this->assertIsArray($scripts);
        $this->assertArrayHasKey('scripts', $scripts);
        $this->assertArrayHasKey('total_count', $scripts);
        $this->assertArrayHasKey('external_count', $scripts);
        $this->assertArrayHasKey('inline_count', $scripts);

        $this->assertEquals(2, $scripts['total_count']);
        $this->assertEquals(1, $scripts['external_count']); // /script.js
        $this->assertEquals(1, $scripts['inline_count']); // console.log script

        $scriptData = $scripts['scripts'];
        $this->assertEquals('/script.js', $scriptData[0]['src']);
        $this->assertFalse($scriptData[0]['inline']);
        $this->assertEquals('', $scriptData[1]['src']);
        $this->assertTrue($scriptData[1]['inline']);
    }

    public function testExtractStylesheets(): void
    {
        $result = $this->domExtractor->extractFromHtml($this->sampleHtml);
        $stylesheets = $result['stylesheets'];

        $this->assertIsArray($stylesheets);
        $this->assertArrayHasKey('stylesheets', $stylesheets);
        $this->assertArrayHasKey('total_count', $stylesheets);
        $this->assertArrayHasKey('external_count', $stylesheets);
        $this->assertArrayHasKey('inline_count', $stylesheets);

        $this->assertEquals(2, $stylesheets['total_count']);
        $this->assertEquals(1, $stylesheets['external_count']); // /style.css
        $this->assertEquals(1, $stylesheets['inline_count']); // inline style

        $stylesheetData = $stylesheets['stylesheets'];
        $this->assertEquals('/style.css', $stylesheetData[0]['href']);
        $this->assertEquals('external', $stylesheetData[0]['type']);
        $this->assertEquals('inline', $stylesheetData[1]['type']);
    }

    public function testSelectElements(): void
    {
        $result = $this->domExtractor->selectElements($this->sampleHtml, 'h1');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('selector', $result);
        $this->assertArrayHasKey('count', $result);
        $this->assertArrayHasKey('elements', $result);

        $this->assertEquals('h1', $result['selector']);
        $this->assertEquals(1, $result['count']);
        $this->assertEquals('Main Title', $result['elements'][0]['text']);
    }

    public function testSelectElementsById(): void
    {
        $result = $this->domExtractor->selectElements($this->sampleHtml, '#email');

        $this->assertEquals(1, $result['count']);
        $this->assertEquals('input', $result['elements'][0]['tag']);
    }

    public function testAnalyzeStructure(): void
    {
        $result = $this->domExtractor->analyzeStructure($this->sampleHtml);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('doctype', $result);
        $this->assertArrayHasKey('html_attributes', $result);
        $this->assertArrayHasKey('head_analysis', $result);
        $this->assertArrayHasKey('body_structure', $result);
        $this->assertArrayHasKey('nesting_depth', $result);
        $this->assertArrayHasKey('element_counts', $result);
        $this->assertArrayHasKey('semantic_score', $result);

        $this->assertIsArray($result['html_attributes']);
        $this->assertEquals('en', $result['html_attributes']['lang']);
        $this->assertIsInt($result['nesting_depth']);
        $this->assertGreaterThan(0, $result['nesting_depth']);
        $this->assertIsArray($result['element_counts']);
        $this->assertIsInt($result['semantic_score']);
    }

    public function testExtractSpaElements(): void
    {
        $result = $this->domExtractor->extractSpaElements($this->spaHtml);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('spa_indicators', $result);
        $this->assertArrayHasKey('dynamic_content_areas', $result);
        $this->assertArrayHasKey('router_elements', $result);
        $this->assertArrayHasKey('framework_detection', $result);

        $spaIndicators = $result['spa_indicators'];
        $this->assertTrue($spaIndicators['has_router_outlet']);
        $this->assertTrue($spaIndicators['has_ng_app']);

        $frameworks = $result['framework_detection'];
        $this->assertContains('angular', $frameworks);
    }

    public function testFindByText(): void
    {
        $result = $this->domExtractor->findByText($this->sampleHtml, 'Main Title');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('search_text', $result);
        $this->assertArrayHasKey('count', $result);
        $this->assertArrayHasKey('elements', $result);

        $this->assertEquals('Main Title', $result['search_text']);
        $this->assertEquals(1, $result['count']);
        $this->assertEquals('h1', $result['elements'][0]['tag']);
        $this->assertEquals('Main Title', $result['elements'][0]['text']);
    }

    public function testFindByTextCaseInsensitive(): void
    {
        $result = $this->domExtractor->findByText($this->sampleHtml, 'MAIN TITLE', ['case_sensitive' => false]);

        $this->assertEquals(1, $result['count']);
        $this->assertEquals('h1', $result['elements'][0]['tag']);
    }

    public function testFindByTextPartialMatch(): void
    {
        $result = $this->domExtractor->findByText($this->sampleHtml, 'bold', ['exact_match' => false]);

        $this->assertGreaterThan(0, $result['count']);
        $this->assertContains('bold text', $result['elements'][0]['text']);
    }

    public function testExtractFromHtmlWithEmptyContent(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Empty HTML content provided');
        $this->domExtractor->extractFromHtml('');
    }

    public function testExtractFromHtmlWithInvalidHtml(): void
    {
        $invalidHtml = '<html><body><div><p>Unclosed tags';

        $result = $this->domExtractor->extractFromHtml($invalidHtml);

        // Should still work with malformed HTML
        $this->assertIsArray($result);
        $this->assertArrayHasKey('headings', $result);
    }

    public function testCustomElements(): void
    {
        $customHtml = <<<HTML
<html>
<body>
    <my-custom-element data-value="test">Content</my-custom-element>
    <another-component></another-component>
    <x-ms-webview></x-ms-webview>
</body>
</html>
HTML;

        $result = $this->domExtractor->extractFromHtml($customHtml);
        $customElements = $result['custom_elements'];

        $this->assertIsArray($customElements);
        $this->assertEquals(2, $customElements['total_count']); // x-ms-webview excluded
        $this->assertContains('my-custom-element', $customElements['unique_tags']);
        $this->assertContains('another-component', $customElements['unique_tags']);
        $this->assertNotContains('x-ms-webview', $customElements['unique_tags']);
    }

    public function testWebComponentsDetection(): void
    {
        $webComponentsHtml = <<<HTML
<html>
<body>
    <my-element></my-element>
    <template id="my-template">
        <slot name="content"></slot>
    </template>
    <script src="webcomponents-polyfill.js"></script>
</body>
</html>
HTML;

        $result = $this->domExtractor->extractFromHtml($webComponentsHtml);
        $webComponents = $result['web_components'];

        $this->assertIsArray($webComponents);
        $this->assertTrue($webComponents['likely_using_web_components']);
        $this->assertTrue($webComponents['indicators']['custom_elements']);
        $this->assertEquals(1, $webComponents['indicators']['template_elements']);
        $this->assertEquals(1, $webComponents['indicators']['slot_elements']);
    }

    public function testAccessibilityAnalysis(): void
    {
        $accessibleHtml = <<<HTML
<html lang="en">
<body>
    <a href="#main" class="skip-link">Skip to main content</a>
    <main id="main">
        <h1>Main Heading</h1>
        <img src="test.jpg" alt="Descriptive alt text">
        <button aria-label="Close dialog">×</button>
        <form>
            <label for="name">Name:</label>
            <input type="text" id="name" name="name">
        </form>
    </main>
</body>
</html>
HTML;

        $result = $this->domExtractor->extractFromHtml($accessibleHtml);
        $accessibility = $result['accessibility'];

        $this->assertTrue($accessibility['lang_attribute']);
        $this->assertGreaterThan(0, count($accessibility['skip_links']));
        $this->assertGreaterThan(0, $accessibility['aria_labels']);
        $this->assertIsArray($accessibility['form_labels']);
    }

    public function testPerformanceMetadataExtraction(): void
    {
        $performanceHtml = <<<HTML
<html>
<head>
    <link rel="dns-prefetch" href="//example.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preload" href="critical.css" as="style">
    <link rel="prefetch" href="next-page.html">
</head>
<body>Content</body>
</html>
HTML;

        $result = $this->domExtractor->extractFromHtml($performanceHtml);

        // Performance hints should be included in link relations or scripts/stylesheets analysis
        $this->assertIsArray($result);
    }

    public function testMalformedHtmlHandling(): void
    {
        $malformedHtml = <<<HTML
<html>
<head>
    <title>Test</title>
<body>
    <div>
        <p>Unclosed paragraph
        <img src="test.jpg">
    </div>
    <script>
        // Unclosed script
HTML;

        $result = $this->domExtractor->extractFromHtml($malformedHtml);

        // Should handle malformed HTML gracefully
        $this->assertIsArray($result);
        $this->assertArrayHasKey('headings', $result);
        $this->assertArrayHasKey('images', $result);
    }

    public function testLargeDocumentHandling(): void
    {
        // Generate a large HTML document
        $largeHtml = '<html><body>';
        for ($i = 0; $i < 1000; $i++) {
            $largeHtml .= "<p>Paragraph $i with some content</p>";
            if ($i % 100 === 0) {
                $largeHtml .= "<h2>Section $i</h2>";
            }
        }
        $largeHtml .= '</body></html>';

        $result = $this->domExtractor->extractFromHtml($largeHtml);

        $this->assertIsArray($result);
        $this->assertGreaterThan(900, $result['content']['paragraph_count'] ?? 0);
    }

    public function testLanguageDetection(): void
    {
        $multiLangHtml = <<<HTML
<html lang="en">
<body>
    <div lang="fr">Bonjour le monde</div>
    <div lang="es">Hola mundo</div>
    <p>Hello world</p>
</body>
</html>
HTML;

        $result = $this->domExtractor->extractFromHtml($multiLangHtml);

        // Language detection should identify the primary language
        $this->assertIsArray($result);
        $this->assertTrue($result['accessibility']['lang_attribute']);
    }
}