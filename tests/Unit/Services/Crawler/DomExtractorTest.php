<?php

namespace Tests\Unit\Services\Crawler;

use App\Services\Crawler\DomExtractor;
use Tests\TestCase;

class DomExtractorTest extends TestCase
{
    private DomExtractor $domExtractor;
    private string $testHtml;

    protected function setUp(): void
    {
        parent::setUp();
        $this->domExtractor = new DomExtractor();
        $this->testHtml = $this->createTestHtml();
    }

    /** @test */
    public function it_can_extract_table_information()
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head><title>Test</title></head>
        <body>
            <table>
                <caption>Test Table</caption>
                <thead>
                    <tr><th scope="col">Header 1</th><th scope="col">Header 2</th></tr>
                </thead>
                <tbody>
                    <tr><td headers="h1">Cell 1</td><td headers="h2">Cell 2</td></tr>
                    <tr><td>Cell 3</td><td>Cell 4</td></tr>
                </tbody>
            </table>
            <table>
                <tr><td>Simple table</td></tr>
            </table>
        </body>
        </html>';

        $this->domExtractor->initialize($html, 'https://example.com');
        $result = $this->domExtractor->extractTables();

        $this->assertIsArray($result);
        $this->assertEquals(2, $result['total_count']);
        $this->assertArrayHasKey('tables', $result);
        $this->assertCount(2, $result['tables']);

        // Test first table (more complex)
        $firstTable = $result['tables'][0];
        $this->assertTrue($firstTable['has_caption']);
        $this->assertTrue($firstTable['has_thead']);
        $this->assertTrue($firstTable['has_tbody']);
        $this->assertEquals(2, $firstTable['headers_count']);
        $this->assertTrue($firstTable['has_scope_attributes']);
        $this->assertTrue($firstTable['has_headers_attributes']);

        // Test second table (simpler)
        $secondTable = $result['tables'][1];
        $this->assertFalse($secondTable['has_caption']);
        $this->assertFalse($secondTable['has_thead']);
        $this->assertFalse($secondTable['has_tbody']);
        $this->assertEquals(0, $secondTable['headers_count']);
        $this->assertFalse($secondTable['has_scope_attributes']);
    }

    /** @test */
    public function it_can_extract_form_information()
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head><title>Test</title></head>
        <body>
            <form action="/submit" method="POST" autocomplete="on">
                <fieldset>
                    <legend>Personal Info</legend>
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" required>
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required pattern="[^@]+@[^@]+\.[^@]+">
                </fieldset>
                <textarea name="comments" rows="4"></textarea>
                <select name="country">
                    <option value="us">US</option>
                    <option value="ca">Canada</option>
                </select>
                <button type="submit">Submit</button>
            </form>
            <form action="/search" method="GET">
                <input type="search" name="q">
            </form>
        </body>
        </html>';

        $this->domExtractor->initialize($html, 'https://example.com');
        $result = $this->domExtractor->extractForms();

        $this->assertIsArray($result);
        $this->assertEquals(2, $result['total_count']);
        $this->assertArrayHasKey('forms', $result);
        $this->assertCount(2, $result['forms']);

        // Test first form (complex)
        $firstForm = $result['forms'][0];
        $this->assertEquals('/submit', $firstForm['action']);
        $this->assertEquals('POST', $firstForm['method']);
        $this->assertTrue($firstForm['has_autocomplete']);
        $this->assertEquals(4, $firstForm['inputs_count']); // text, email inputs + textarea + select + button
        $this->assertEquals(2, $firstForm['labels_count']);
        $this->assertEquals(1, $firstForm['fieldsets_count']);
        $this->assertEquals(2, $firstForm['required_fields_count']);

        // Check input types distribution
        $this->assertArrayHasKey('input_types', $firstForm);
        $this->assertArrayHasKey('text', $firstForm['input_types']);
        $this->assertArrayHasKey('email', $firstForm['input_types']);

        // Test validation attributes
        $this->assertArrayHasKey('validation_attributes', $firstForm);

        // Test second form (simpler)
        $secondForm = $result['forms'][1];
        $this->assertEquals('/search', $secondForm['action']);
        $this->assertEquals('GET', $secondForm['method']);
        $this->assertEquals(1, $secondForm['inputs_count']);
    }

    /** @test */
    public function it_can_extract_multimedia_elements()
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head><title>Test</title></head>
        <body>
            <audio controls>
                <source src="audio.mp3" type="audio/mpeg">
                <source src="audio.ogg" type="audio/ogg">
            </audio>
            <video controls poster="poster.jpg" width="640" height="480">
                <source src="video.mp4" type="video/mp4">
                <track kind="captions" src="captions.vtt" srclang="en" label="English">
            </video>
            <iframe src="https://youtube.com/embed/123" title="Video" sandbox="allow-scripts">
            </iframe>
            <embed src="plugin.swf" type="application/x-shockwave-flash">
            <object data="document.pdf" type="application/pdf">
                <p>PDF cannot be displayed</p>
            </object>
        </body>
        </html>';

        $this->domExtractor->initialize($html, 'https://example.com');
        $result = $this->domExtractor->extractMultimedia();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('audio', $result);
        $this->assertArrayHasKey('video', $result);
        $this->assertArrayHasKey('iframes', $result);
        $this->assertArrayHasKey('embeds', $result);

        // Test audio
        $audio = $result['audio'];
        $this->assertEquals(1, $audio['total_count']);
        $this->assertEquals(1, $audio['with_controls']);
        $this->assertTrue($audio['audios'][0]['controls']);
        $this->assertEquals(2, $audio['audios'][0]['sources_count']);

        // Test video
        $video = $result['video'];
        $this->assertEquals(1, $video['total_count']);
        $this->assertEquals(1, $video['with_controls']);
        $this->assertEquals(1, $video['with_captions']);
        $this->assertTrue($video['videos'][0]['controls']);
        $this->assertEquals('poster.jpg', $video['videos'][0]['poster']);
        $this->assertEquals(1, $video['videos'][0]['tracks_count']);
        $this->assertTrue($video['videos'][0]['has_captions']);

        // Test iframes
        $iframes = $result['iframes'];
        $this->assertEquals(1, $iframes['total_count']);
        $this->assertEquals(1, $iframes['with_title']);
        $this->assertEquals(1, $iframes['sandboxed']);
        $this->assertTrue($iframes['iframes'][0]['has_title']);
        $this->assertTrue($iframes['iframes'][0]['is_sandboxed']);

        // Test embeds
        $embeds = $result['embeds'];
        $this->assertEquals(2, $embeds['total_count']); // embed + object
        $this->assertEquals('embed', $embeds['embeds'][0]['tag_name']);
        $this->assertEquals('object', $embeds['embeds'][1]['tag_name']);
    }

    /** @test */
    public function it_can_extract_navigation_elements()
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head><title>Test</title></head>
        <body>
            <a href="#main-content" class="skip-link">Skip to main content</a>
            <nav aria-label="Main navigation">
                <ul>
                    <li><a href="/">Home</a></li>
                    <li><a href="/about">About</a></li>
                </ul>
            </nav>
            <nav aria-label="Breadcrumb" class="breadcrumb">
                <ol>
                    <li><a href="/">Home</a></li>
                    <li><a href="/products">Products</a></li>
                    <li>Current</li>
                </ol>
            </nav>
            <div itemscope itemtype="http://schema.org/BreadcrumbList" class="breadcrumbs">
                <span itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">
                    <a itemprop="item" href="/"><span itemprop="name">Home</span></a>
                </span>
            </div>
            <main id="main-content">
                <p>Content with <a href="#section1">internal link</a></p>
                <section id="section1">
                    <h2>Section 1</h2>
                </section>
            </main>
        </body>
        </html>';

        $this->domExtractor->initialize($html, 'https://example.com');
        $result = $this->domExtractor->extractNavigation();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('nav_elements', $result);
        $this->assertArrayHasKey('breadcrumbs', $result);
        $this->assertArrayHasKey('skip_links', $result);
        $this->assertArrayHasKey('anchor_links', $result);

        // Test nav elements
        $navElements = $result['nav_elements'];
        $this->assertEquals(2, $navElements['total_count']);
        $this->assertEquals(2, $navElements['properly_labeled']);

        // Test breadcrumbs
        $breadcrumbs = $result['breadcrumbs'];
        $this->assertGreaterThanOrEqual(2, $breadcrumbs['total_count']);

        // Test skip links
        $skipLinks = $result['skip_links'];
        $this->assertEquals(1, $skipLinks['total_count']);
        $this->assertEquals('Skip to main content', $skipLinks['skip_links'][0]['text']);

        // Test anchor links
        $anchorLinks = $result['anchor_links'];
        $this->assertEquals(2, $anchorLinks['total_count']); // #main-content and #section1
        $this->assertEquals(2, $anchorLinks['with_valid_targets']);
    }

    /** @test */
    public function it_can_extract_accessibility_features()
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head><title>Test</title></head>
        <body>
            <header role="banner">
                <h1>Main Title</h1>
            </header>
            <nav role="navigation" aria-label="Main menu">
                <ul>
                    <li><a href="/">Home</a></li>
                </ul>
            </nav>
            <main role="main">
                <h2>Subheading</h2>
                <p>Content with <span aria-describedby="help1">interactive element</span></p>
                <div id="help1">Help text</div>
                <button aria-label="Close dialog" tabindex="0">×</button>
                <input type="text" aria-labelledby="label1">
                <label id="label1">Input label</label>
                <div tabindex="-1">Focusable div</div>
                <section>
                    <h4>Skipped H3</h4>
                </section>
            </main>
            <aside role="complementary">
                <h3>Sidebar</h3>
            </aside>
            <footer role="contentinfo">
                <p>Footer content</p>
            </footer>
        </body>
        </html>';

        $this->domExtractor->initialize($html, 'https://example.com');
        $result = $this->domExtractor->extractAccessibilityFeatures();

        $this->assertIsArray($result);

        // Test ARIA labels
        $ariaLabels = $result['aria_labels'];
        $this->assertGreaterThan(0, $ariaLabels['aria_label']);
        $this->assertGreaterThan(0, $ariaLabels['aria_labelledby']);
        $this->assertGreaterThan(0, $ariaLabels['aria_describedby']);

        // Test landmarks
        $landmarks = $result['landmarks'];
        $this->assertGreaterThan(0, $landmarks['landmarks']['main']);
        $this->assertGreaterThan(0, $landmarks['landmarks']['navigation']);
        $this->assertGreaterThan(0, $landmarks['landmarks']['banner']);
        $this->assertGreaterThan(0, $landmarks['landmarks']['contentinfo']);
        $this->assertGreaterThan(0, $landmarks['landmarks']['complementary']);

        // Test heading structure
        $headingStructure = $result['heading_structure'];
        $this->assertTrue($headingStructure['has_h1']);
        $this->assertFalse($headingStructure['multiple_h1']);
        // The HTML has H1, H2, H4, then H3 - so H3 is skipped between H2 and H4
        $this->assertContains(3, $headingStructure['skipped_levels']); // H3 skipped between H2 and H4

        // Test focus management
        $focusManagement = $result['focus_management'];
        $this->assertEquals(1, $focusManagement['tabindex_zero']);
        $this->assertEquals(1, $focusManagement['tabindex_negative']);
        $this->assertGreaterThan(0, $focusManagement['focusable_elements']);
    }

    /** @test */
    public function it_can_extract_performance_elements()
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <title>Test</title>
            <link rel="preload" href="critical.css" as="style">
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="dns-prefetch" href="//analytics.google.com">
            <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto&display=swap">
            <script src="https://cdn.example.com/lib.js" integrity="sha256-abc123" crossorigin="anonymous"></script>
        </head>
        <body>
            <img src="hero.jpg" loading="lazy" alt="Hero image">
            <img src="above-fold.jpg" alt="Above fold image">
            <iframe src="https://youtube.com/embed/123" loading="lazy"></iframe>
            <script src="app.js" async></script>
            <script src="analytics.js" defer></script>
            <script>
                // Inline script
                console.log("Hello");
            </script>
        </body>
        </html>';

        $this->domExtractor->initialize($html, 'https://example.com');
        $result = $this->domExtractor->extractPerformanceElements();

        $this->assertIsArray($result);

        // Test lazy loading
        $lazyLoading = $result['lazy_loading'];
        $this->assertEquals(1, $lazyLoading['img_lazy']);
        $this->assertEquals(1, $lazyLoading['iframe_lazy']);

        // Test preload hints
        $preloadHints = $result['preload_hints'];
        $this->assertEquals(1, $preloadHints['preload']);
        $this->assertEquals(1, $preloadHints['preconnect']);
        $this->assertEquals(1, $preloadHints['dns_prefetch']);

        // Test resource hints
        $resourceHints = $result['resource_hints'];
        $this->assertArrayHasKey('preload', $resourceHints);
        $this->assertArrayHasKey('preconnect', $resourceHints);
        $this->assertArrayHasKey('dns-prefetch', $resourceHints);

        // Test web fonts
        $webFonts = $result['web_fonts'];
        $this->assertEquals(1, $webFonts['total_count']);
        $this->assertEquals(1, $webFonts['google_fonts_count']);
        $this->assertTrue($webFonts['fonts'][0]['is_google_fonts']);

        // Test third-party scripts
        $thirdPartyScripts = $result['third_party_scripts'];
        $this->assertEquals(1, $thirdPartyScripts['total_count']);
        $this->assertEquals(1, $thirdPartyScripts['unique_domains']);
        $this->assertEquals(1, $thirdPartyScripts['with_integrity']);
        $this->assertContains('cdn.example.com', $thirdPartyScripts['domains']);
    }

    /** @test */
    public function it_can_extract_security_elements()
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <title>Test</title>
            <meta http-equiv="Content-Security-Policy" content="default-src \'self\'">
            <script src="https://cdn.example.com/lib.js" integrity="sha256-abc123" crossorigin="anonymous"></script>
            <link rel="stylesheet" href="https://fonts.googleapis.com/css" integrity="sha256-def456">
        </head>
        <body>
            <form action="/submit" method="POST">
                <input type="hidden" name="_token" value="csrf123">
                <input type="password" name="password">
            </form>
            <form action="/search" method="GET" autocomplete="off">
                <input type="text" name="q">
            </form>
            <iframe src="https://youtube.com/embed/123" sandbox="allow-scripts allow-same-origin"></iframe>
            <iframe src="https://untrusted.com/widget"></iframe>
            <a href="https://external.com" target="_blank" rel="noopener noreferrer">External link</a>
            <a href="https://another.com" target="_blank">Unsafe external link</a>
        </body>
        </html>';

        $this->domExtractor->initialize($html, 'https://example.com');
        $result = $this->domExtractor->extractSecurityElements();

        $this->assertIsArray($result);

        // Test CSP headers
        $cspHeaders = $result['csp_headers'];
        $this->assertTrue($cspHeaders['has_csp_meta']);
        $this->assertEquals('default-src \'self\'', $cspHeaders['csp_content']);

        // Test integrity attributes
        $integrityAttrs = $result['integrity_attributes'];
        $this->assertEquals(1, $integrityAttrs['script_integrity']);
        $this->assertEquals(1, $integrityAttrs['link_integrity']);

        // Test external links security
        $externalLinksSecurity = $result['external_links_security'];
        $this->assertEquals(2, $externalLinksSecurity['external_blank_links']);
        $this->assertEquals(1, $externalLinksSecurity['without_noopener']);
        $this->assertEquals(1, $externalLinksSecurity['without_noreferrer']);

        // Test form security
        $formSecurity = $result['form_security'];
        $this->assertEquals(1, $formSecurity['forms_without_csrf']); // GET form doesn't need CSRF
        $this->assertEquals(1, $formSecurity['forms_with_autocomplete_off']);
        $this->assertEquals(1, $formSecurity['password_fields']);

        // Test iframe security
        $iframeSecurity = $result['iframe_security'];
        $this->assertEquals(1, $iframeSecurity['iframes_without_sandbox']);
    }

    /** @test */
    public function it_can_extract_semantic_elements()
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head><title>Test</title></head>
        <body>
            <header>
                <h1>Site Title</h1>
                <nav>Navigation</nav>
            </header>
            <main>
                <article>
                    <header>
                        <h2>Article Title</h2>
                        <time datetime="2023-01-01">January 1, 2023</time>
                    </header>
                    <section>
                        <p>Article content with <mark>highlighted text</mark></p>
                        <figure>
                            <img src="image.jpg" alt="Description">
                            <figcaption>Image caption</figcaption>
                        </figure>
                    </section>
                    <details>
                        <summary>More info</summary>
                        <p>Hidden content</p>
                    </details>
                </article>
            </main>
            <aside>
                <section>
                    <h3>Related Articles</h3>
                </section>
            </aside>
            <footer>
                <p>Copyright info</p>
            </footer>
        </body>
        </html>';

        $this->domExtractor->initialize($html, 'https://example.com');
        $result = $this->domExtractor->extractSemanticElements();

        $this->assertIsArray($result);

        $usage = $result['semantic_elements_usage'];
        $this->assertGreaterThan(0, $usage['article']);
        $this->assertGreaterThan(0, $usage['section']);
        $this->assertGreaterThan(0, $usage['aside']);
        $this->assertGreaterThan(0, $usage['header']);
        $this->assertGreaterThan(0, $usage['footer']);
        $this->assertGreaterThan(0, $usage['main']);
        $this->assertGreaterThan(0, $usage['nav']);
        $this->assertGreaterThan(0, $usage['figure']);
        $this->assertGreaterThan(0, $usage['figcaption']);
        $this->assertGreaterThan(0, $usage['time']);
        $this->assertGreaterThan(0, $usage['mark']);
        $this->assertGreaterThan(0, $usage['details']);
        $this->assertGreaterThan(0, $usage['summary']);

        $this->assertGreaterThan(50, $result['semantic_score']); // Should have good semantic usage
        $this->assertGreaterThan(10, $result['total_semantic_elements']);
    }

    /** @test */
    public function it_can_extract_custom_data()
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head><title>Test</title></head>
        <body>
            <div data-component="carousel" data-slides="5" data-auto-play="true">
                <div slot="slide1">Slide content</div>
            </div>
            <custom-element data-value="123">
                <template>Template content</template>
            </custom-element>
            <div class="h-card">
                <span class="p-name">John Doe</span>
                <span class="p-org">Company</span>
            </div>
            <div class="h-event">
                <span class="p-name">Event Name</span>
                <time class="dt-start" datetime="2023-01-01">January 1</time>
            </div>
        </body>
        </html>';

        $this->domExtractor->initialize($html, 'https://example.com');
        $result = $this->domExtractor->extractCustomData();

        $this->assertIsArray($result);

        // Test data attributes
        $dataAttrs = $result['data_attributes'];
        $this->assertGreaterThan(0, $dataAttrs['elements_with_data_attrs']);
        $this->assertGreaterThan(0, $dataAttrs['total_data_attributes']);

        // Test microformats
        $microformats = $result['microformats'];
        $this->assertEquals(1, $microformats['hcard']);
        $this->assertEquals(1, $microformats['hcalendar']);

        // Test custom elements
        $customElements = $result['custom_elements'];
        $this->assertEquals(1, $customElements['unique_custom_elements']);
        $this->assertArrayHasKey('custom-element', $customElements['custom_elements']);

        // Test web components
        $webComponents = $result['web_components'];
        $this->assertEquals(1, $webComponents['template_elements']);
        $this->assertEquals(0, $webComponents['slot_elements']); // slot is not recognized in regular HTML parsing
        $this->assertEquals(1, $webComponents['shadow_dom_usage']);
    }

    /** @test */
    public function it_handles_malformed_html_gracefully()
    {
        $malformedHtml = '
        <!DOCTYPE html>
        <html>
        <head><title>Test</title>
        <body>
            <p>Unclosed paragraph
            <div>Nested improperly</p>
            <img src="image.jpg" alt="Missing closing tag"
            <table>
                <tr><td>Cell without proper table structure
            </div>
        </body>';

        $this->domExtractor->initialize($malformedHtml, 'https://example.com');

        // Should not throw exceptions
        $tables = $this->domExtractor->extractTables();
        $this->assertIsArray($tables);

        $multimedia = $this->domExtractor->extractMultimedia();
        $this->assertIsArray($multimedia);

        $accessibility = $this->domExtractor->extractAccessibilityFeatures();
        $this->assertIsArray($accessibility);
    }

    private function createTestHtml(): string
    {
        return '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Test Page</title>
        </head>
        <body>
            <h1>Main Heading</h1>
            <p>Test content</p>
            <img src="test.jpg" alt="Test image">
            <a href="https://example.com">External link</a>
            <a href="/internal">Internal link</a>
        </body>
        </html>';
    }
}