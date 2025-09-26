<?php

namespace Tests\Unit\Services\Crawler;

use App\Services\Crawler\MetaDataExtractor;
use Exception;
use PHPUnit\Framework\TestCase;

class MetaDataExtractorTest extends TestCase
{
    private MetaDataExtractor $metaDataExtractor;
    private string $sampleHtml;
    private string $socialMediaHtml;
    private string $structuredDataHtml;

    protected function setUp(): void
    {
        parent::setUp();
        $this->metaDataExtractor = new MetaDataExtractor();

        $this->sampleHtml = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sample Page Title</title>
    <meta name="description" content="This is a sample page description for testing purposes">
    <meta name="keywords" content="testing, sample, metadata, extraction">
    <meta name="author" content="Test Author">
    <meta name="robots" content="index, follow, max-snippet:-1">
    <link rel="canonical" href="https://example.com/sample-page">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'">
    <link rel="stylesheet" href="/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="dns-prefetch" href="//example.com">
</head>
<body>
    <h1>Sample Content</h1>
    <p>Content goes here</p>
</body>
</html>
HTML;

        $this->socialMediaHtml = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Social Media Optimized Page</title>
    <meta name="description" content="A page optimized for social media sharing">

    <!-- Open Graph Tags -->
    <meta property="og:title" content="Social Media Page Title">
    <meta property="og:description" content="Description for social media sharing">
    <meta property="og:image" content="https://example.com/social-image.jpg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:url" content="https://example.com/social-page">
    <meta property="og:type" content="article">
    <meta property="og:site_name" content="Example Site">

    <!-- Twitter Card Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@example">
    <meta name="twitter:creator" content="@author">
    <meta name="twitter:title" content="Twitter Optimized Title">
    <meta name="twitter:description" content="Twitter description">
    <meta name="twitter:image" content="https://example.com/twitter-image.jpg">

    <!-- Facebook Tags -->
    <meta property="fb:app_id" content="123456789">

    <!-- Mobile Tags -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="theme-color" content="#0066cc">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="manifest" href="/manifest.json">
</head>
<body>
    <h1>Social Media Content</h1>
</body>
</html>
HTML;

        $this->structuredDataHtml = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Structured Data Example</title>
</head>
<body>
    <!-- JSON-LD -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Article",
      "headline": "Example Article",
      "author": {
        "@type": "Person",
        "name": "John Doe"
      },
      "datePublished": "2024-01-01",
      "image": "https://example.com/article-image.jpg"
    }
    </script>

    <!-- Microdata -->
    <article itemscope itemtype="https://schema.org/BlogPosting">
        <h1 itemprop="headline">Blog Post Title</h1>
        <div itemprop="author" itemscope itemtype="https://schema.org/Person">
            <span itemprop="name">Jane Smith</span>
        </div>
        <time itemprop="datePublished" datetime="2024-01-01">January 1, 2024</time>
        <div itemprop="articleBody">
            <p>Blog post content goes here.</p>
        </div>
    </article>

    <!-- RDFa -->
    <div typeof="schema:Product">
        <span property="schema:name">Example Product</span>
        <span property="schema:price" content="29.99">$29.99</span>
        <div property="schema:description">Product description</div>
    </div>
</body>
</html>
HTML;
    }

    public function testExtractFromHtmlBasicStructure(): void
    {
        $result = $this->metaDataExtractor->extractFromHtml($this->sampleHtml, 'https://example.com');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('basic_meta', $result);
        $this->assertArrayHasKey('seo_meta', $result);
        $this->assertArrayHasKey('social_meta', $result);
        $this->assertArrayHasKey('technical_meta', $result);
        $this->assertArrayHasKey('structured_data', $result);
        $this->assertArrayHasKey('link_relations', $result);
        $this->assertArrayHasKey('dublin_core', $result);
        $this->assertArrayHasKey('custom_meta', $result);
        $this->assertArrayHasKey('validation', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('extracted_at', $result);
    }

    public function testBasicMetaTags(): void
    {
        $result = $this->metaDataExtractor->extractFromHtml($this->sampleHtml);
        $basicMeta = $result['basic_meta'];

        $this->assertIsArray($basicMeta);
        $this->assertArrayHasKey('title', $basicMeta);
        $this->assertArrayHasKey('description', $basicMeta);
        $this->assertArrayHasKey('keywords', $basicMeta);
        $this->assertArrayHasKey('author', $basicMeta);

        $title = $basicMeta['title'];
        $this->assertEquals('Sample Page Title', $title['content']);
        $this->assertEquals(17, $title['length']);
        $this->assertEquals(3, $title['word_count']);

        $description = $basicMeta['description'];
        $this->assertEquals('This is a sample page description for testing purposes', $description['content']);
        $this->assertEquals(54, $description['length']);
        $this->assertEquals(9, $description['word_count']);

        $keywords = $basicMeta['keywords'];
        $this->assertEquals('testing, sample, metadata, extraction', $keywords['content']);
        $this->assertEquals(4, $keywords['count']);
        $this->assertContains('testing', $keywords['keywords_array']);
        $this->assertContains('sample', $keywords['keywords_array']);

        $this->assertEquals('Test Author', $basicMeta['author']);
    }

    public function testSeoMetaTags(): void
    {
        $result = $this->metaDataExtractor->extractFromHtml($this->sampleHtml);
        $seoMeta = $result['seo_meta'];

        $this->assertIsArray($seoMeta);
        $this->assertArrayHasKey('robots', $seoMeta);
        $this->assertArrayHasKey('canonical', $seoMeta);
        $this->assertArrayHasKey('language', $seoMeta);

        $robots = $seoMeta['robots'];
        $this->assertEquals('index, follow, max-snippet:-1', $robots['content']);
        $this->assertContains('index', $robots['directives']);
        $this->assertContains('follow', $robots['directives']);
        $this->assertTrue($robots['indexable']);
        $this->assertTrue($robots['followable']);

        $canonical = $seoMeta['canonical'];
        $this->assertEquals('https://example.com/sample-page', $canonical['href']);
        $this->assertTrue($canonical['exists']);

        $language = $seoMeta['language'];
        $this->assertEquals('en', $language['html_lang']);
    }

    public function testTechnicalMetaTags(): void
    {
        $result = $this->metaDataExtractor->extractFromHtml($this->sampleHtml);
        $technicalMeta = $result['technical_meta'];

        $this->assertIsArray($technicalMeta);
        $this->assertArrayHasKey('encoding', $technicalMeta);
        $this->assertArrayHasKey('viewport', $technicalMeta);
        $this->assertArrayHasKey('compatibility', $technicalMeta);
        $this->assertArrayHasKey('security', $technicalMeta);

        $encoding = $technicalMeta['encoding'];
        $this->assertEquals('UTF-8', $encoding['charset']);

        $viewport = $technicalMeta['viewport'];
        $this->assertEquals('width=device-width, initial-scale=1.0', $viewport['content']);
        $this->assertTrue($viewport['responsive']);
        $this->assertEquals('device-width', $viewport['properties']['width']);
        $this->assertEquals('1.0', $viewport['properties']['initial-scale']);

        $compatibility = $technicalMeta['compatibility'];
        $this->assertEquals('IE=edge', $compatibility['content']);
        $this->assertTrue($compatibility['ie_edge_mode']);

        $security = $technicalMeta['security'];
        $this->assertEquals("default-src 'self'", $security['csp']);
        $this->assertTrue($security['has_csp']);
    }

    public function testExtractOpenGraph(): void
    {
        $result = $this->metaDataExtractor->extractOpenGraph($this->socialMediaHtml);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('properties', $result);
        $this->assertArrayHasKey('validation', $result);
        $this->assertArrayHasKey('completeness_score', $result);
        $this->assertArrayHasKey('recommendations', $result);

        $properties = $result['properties'];
        $this->assertEquals('Social Media Page Title', $properties['og:title']);
        $this->assertEquals('Description for social media sharing', $properties['og:description']);
        $this->assertEquals('https://example.com/social-image.jpg', $properties['og:image']);
        $this->assertEquals('1200', $properties['og:image:width']);
        $this->assertEquals('630', $properties['og:image:height']);
        $this->assertEquals('https://example.com/social-page', $properties['og:url']);
        $this->assertEquals('article', $properties['og:type']);
        $this->assertEquals('Example Site', $properties['og:site_name']);

        $this->assertGreaterThan(75, $result['completeness_score']);

        $validation = $result['validation'];
        $this->assertIsArray($validation['passed']);
        $this->assertEmpty($validation['errors']); // Should have no errors with complete OG tags
    }

    public function testExtractTwitterCard(): void
    {
        $result = $this->metaDataExtractor->extractTwitterCard($this->socialMediaHtml);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('properties', $result);
        $this->assertArrayHasKey('card_type', $result);
        $this->assertArrayHasKey('validation', $result);
        $this->assertArrayHasKey('completeness_score', $result);

        $properties = $result['properties'];
        $this->assertEquals('summary_large_image', $properties['card']);
        $this->assertEquals('@example', $properties['site']);
        $this->assertEquals('@author', $properties['creator']);
        $this->assertEquals('Twitter Optimized Title', $properties['title']);
        $this->assertEquals('Twitter description', $properties['description']);
        $this->assertEquals('https://example.com/twitter-image.jpg', $properties['image']);

        $this->assertEquals('summary_large_image', $result['card_type']);
        $this->assertGreaterThan(75, $result['completeness_score']);
    }

    public function testExtractJsonLd(): void
    {
        $result = $this->metaDataExtractor->extractJsonLd($this->structuredDataHtml);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('schemas', $result);
        $this->assertArrayHasKey('total_count', $result);
        $this->assertArrayHasKey('schema_types', $result);
        $this->assertArrayHasKey('validation', $result);

        $this->assertEquals(1, $result['total_count']);
        $this->assertContains('Article', $result['schema_types']);

        $schemas = $result['schemas'];
        $schema = $schemas[0];
        $this->assertEquals('https://schema.org', $schema['@context']);
        $this->assertEquals('Article', $schema['@type']);
        $this->assertEquals('Example Article', $schema['headline']);
        $this->assertEquals('John Doe', $schema['author']['name']);
    }

    public function testExtractMicrodata(): void
    {
        $result = $this->metaDataExtractor->extractMicrodata($this->structuredDataHtml);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total_count', $result);
        $this->assertArrayHasKey('schema_types', $result);

        $this->assertEquals(1, $result['total_count']);
        $this->assertContains('https://schema.org/BlogPosting', $result['schema_types']);

        $items = $result['items'];
        $item = $items[0];
        $this->assertEquals('https://schema.org/BlogPosting', $item['itemtype']);
        $this->assertArrayHasKey('properties', $item);
        $this->assertEquals('Blog Post Title', $item['properties']['headline']);
        $this->assertEquals('Jane Smith', $item['properties']['name']);
    }

    public function testExtractRdfa(): void
    {
        $result = $this->metaDataExtractor->extractRdfa($this->structuredDataHtml);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total_count', $result);
        $this->assertArrayHasKey('vocabularies', $result);

        $this->assertGreaterThan(0, $result['total_count']);
        $this->assertContains('schema', $result['vocabularies']);

        $items = $result['items'];
        $productItem = null;
        foreach ($items as $item) {
            if ($item['typeof'] === 'schema:Product') {
                $productItem = $item;
                break;
            }
        }

        $this->assertNotNull($productItem);
        $this->assertEquals('schema:Product', $productItem['typeof']);
    }

    public function testAnalyzeMobileMeta(): void
    {
        $result = $this->metaDataExtractor->analyzeMobileMeta($this->socialMediaHtml);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('viewport', $result);
        $this->assertArrayHasKey('mobile_app_meta', $result);
        $this->assertArrayHasKey('touch_icons', $result);
        $this->assertArrayHasKey('theme_color', $result);
        $this->assertArrayHasKey('manifest', $result);
        $this->assertArrayHasKey('mobile_optimization_score', $result);

        $mobileAppMeta = $result['mobile_app_meta'];
        $this->assertEquals('yes', $mobileAppMeta['apple_mobile_web_app_capable']);
        $this->assertEquals('black', $mobileAppMeta['apple_status_bar_style']);

        $themeColor = $result['theme_color'];
        $this->assertEquals('#0066cc', $themeColor['theme_color']);

        $touchIcons = $result['touch_icons'];
        $this->assertCount(1, $touchIcons);
        $this->assertEquals('/apple-touch-icon.png', $touchIcons[0]['href']);

        $manifest = $result['manifest'];
        $this->assertEquals('/manifest.json', $manifest['href']);
        $this->assertTrue($manifest['exists']);

        $this->assertGreaterThan(80, $result['mobile_optimization_score']);
    }

    public function testExtractPerformanceMeta(): void
    {
        $result = $this->metaDataExtractor->extractPerformanceMeta($this->sampleHtml);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('dns_prefetch', $result);
        $this->assertArrayHasKey('preconnect', $result);
        $this->assertArrayHasKey('preload', $result);
        $this->assertArrayHasKey('prefetch', $result);
        $this->assertArrayHasKey('resource_hints_score', $result);

        $dnsPrefetch = $result['dns_prefetch'];
        $this->assertCount(1, $dnsPrefetch);
        $this->assertEquals('//example.com', $dnsPrefetch[0]['href']);

        $preconnect = $result['preconnect'];
        $this->assertCount(1, $preconnect);
        $this->assertEquals('https://fonts.googleapis.com', $preconnect[0]['href']);

        $this->assertGreaterThan(25, $result['resource_hints_score']);
    }

    public function testLinkRelationsExtraction(): void
    {
        $result = $this->metaDataExtractor->extractFromHtml($this->sampleHtml, 'https://example.com');
        $linkRelations = $result['link_relations'];

        $this->assertIsArray($linkRelations);
        $this->assertArrayHasKey('canonical', $linkRelations);
        $this->assertArrayHasKey('stylesheet', $linkRelations);
        $this->assertArrayHasKey('preconnect', $linkRelations);
        $this->assertArrayHasKey('dns-prefetch', $linkRelations);

        $canonical = $linkRelations['canonical'];
        $this->assertCount(1, $canonical);
        $this->assertEquals('https://example.com/sample-page', $canonical[0]['href']);

        $stylesheet = $linkRelations['stylesheet'];
        $this->assertCount(1, $stylesheet);
        $this->assertEquals('/styles.css', $stylesheet[0]['href']);
        $this->assertEquals('https://example.com/styles.css', $stylesheet[0]['absolute_url']);
    }

    public function testValidateMetadata(): void
    {
        $result = $this->metaDataExtractor->extractFromHtml($this->sampleHtml, 'https://example.com');
        $validation = $result['validation'];

        $this->assertIsArray($validation);
        $this->assertArrayHasKey('errors', $validation);
        $this->assertArrayHasKey('warnings', $validation);
        $this->assertArrayHasKey('passed', $validation);
        $this->assertArrayHasKey('overall_score', $validation);

        $this->assertIsArray($validation['errors']);
        $this->assertIsArray($validation['warnings']);
        $this->assertIsArray($validation['passed']);
        $this->assertIsFloat($validation['overall_score']);

        $this->assertGreaterThan(75, $validation['overall_score']); // Should score well with complete metadata
    }

    public function testGenerateRecommendations(): void
    {
        $incompleteHtml = <<<HTML
<html>
<head>
    <title>A very long title that exceeds the recommended limit for SEO purposes and should trigger a warning</title>
</head>
<body>Content</body>
</html>
HTML;

        $result = $this->metaDataExtractor->extractFromHtml($incompleteHtml);
        $recommendations = $result['recommendations'];

        $this->assertIsArray($recommendations);
        $this->assertNotEmpty($recommendations);

        // Should recommend adding meta description
        $this->assertContains('Add a meta description', $recommendations);

        // Should recommend shortening title
        $titleRecommendation = array_filter($recommendations, function($rec) {
            return strpos($rec, 'title') !== false;
        });
        $this->assertNotEmpty($titleRecommendation);
    }

    public function testDublinCoreExtraction(): void
    {
        $dublinCoreHtml = <<<HTML
<html>
<head>
    <meta name="DC.Title" content="Dublin Core Title">
    <meta name="DC.Creator" content="Dublin Core Author">
    <meta name="DC.Subject" content="Dublin Core Subject">
    <meta name="DC.Description" content="Dublin Core Description">
    <meta name="DC.Date" content="2024-01-01">
    <meta name="DC.Type" content="Text">
    <meta name="DC.Format" content="text/html">
    <meta name="DC.Identifier" content="https://example.com/dc-page">
</head>
<body>Content</body>
</html>
HTML;

        $result = $this->metaDataExtractor->extractFromHtml($dublinCoreHtml);
        $dublinCore = $result['dublin_core'];

        $this->assertIsArray($dublinCore);
        $this->assertArrayHasKey('properties', $dublinCore);
        $this->assertArrayHasKey('completeness_score', $dublinCore);

        $properties = $dublinCore['properties'];
        $this->assertEquals('Dublin Core Title', $properties['title']);
        $this->assertEquals('Dublin Core Author', $properties['creator']);
        $this->assertEquals('Dublin Core Subject', $properties['subject']);
        $this->assertEquals('Dublin Core Description', $properties['description']);

        $this->assertEquals(100, $dublinCore['completeness_score']); // All core elements present
    }

    public function testCustomMetaTags(): void
    {
        $customMetaHtml = <<<HTML
<html>
<head>
    <meta name="custom-tag" content="Custom value">
    <meta name="application-name" content="My App">
    <meta name="msapplication-config" content="/browserconfig.xml">
    <meta property="custom:property" content="Custom property value">
    <meta name="description" content="Standard description">
    <meta property="og:title" content="Standard OG title">
</head>
<body>Content</body>
</html>
HTML;

        $result = $this->metaDataExtractor->extractFromHtml($customMetaHtml);
        $customMeta = $result['custom_meta'];

        $this->assertIsArray($customMeta);
        $this->assertArrayHasKey('tags', $customMeta);
        $this->assertArrayHasKey('count', $customMeta);

        $tags = $customMeta['tags'];
        $this->assertArrayHasKey('custom-tag', $tags);
        $this->assertEquals('Custom value', $tags['custom-tag']);
        $this->assertArrayHasKey('application-name', $tags);
        $this->assertEquals('My App', $tags['application-name']);

        // Should not include standard meta tags
        $this->assertArrayNotHasKey('description', $tags);
        $this->assertArrayNotHasKey('og:title', $tags);

        $this->assertGreaterThan(0, $customMeta['count']);
    }

    public function testExtractFromHtmlWithEmptyContent(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Empty HTML content provided');
        $this->metaDataExtractor->extractFromHtml('');
    }

    public function testExtractFromHtmlWithMalformedHtml(): void
    {
        $malformedHtml = '<html><head><title>Test</title><meta name="description" content="Test';

        $result = $this->metaDataExtractor->extractFromHtml($malformedHtml);

        // Should handle malformed HTML gracefully
        $this->assertIsArray($result);
        $basicMeta = $result['basic_meta'];
        $this->assertEquals('Test', $basicMeta['title']['content']);
    }

    public function testSocialMediaValidation(): void
    {
        $incompleteSocialHtml = <<<HTML
<html>
<head>
    <meta property="og:title" content="Title only">
    <meta name="twitter:card" content="summary">
</head>
<body>Content</body>
</html>
HTML;

        $ogResult = $this->metaDataExtractor->extractOpenGraph($incompleteSocialHtml);
        $twitterResult = $this->metaDataExtractor->extractTwitterCard($incompleteSocialHtml);

        $this->assertLessThan(75, $ogResult['completeness_score']);
        $this->assertNotEmpty($ogResult['validation']['errors']);

        $this->assertLessThan(75, $twitterResult['completeness_score']);
    }

    public function testStructuredDataValidation(): void
    {
        $invalidJsonLdHtml = <<<HTML
<html>
<head>
    <script type="application/ld+json">
    {
        "@type": "Article",
        "headline": "Missing context"
    }
    </script>
    <script type="application/ld+json">
    {
        "invalid": "json"
        "missing": "comma"
    }
    </script>
</head>
<body>Content</body>
</html>
HTML;

        $result = $this->metaDataExtractor->extractJsonLd($invalidJsonLdHtml);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['total_count']); // Only valid JSON should be counted
        $this->assertNotEmpty($result['validation']['errors']); // Should have validation errors
    }

    public function testMultiLanguageSupport(): void
    {
        $multiLangHtml = <<<HTML
<html lang="en">
<head>
    <title>English Title</title>
    <link rel="alternate" hreflang="es" href="https://example.com/es/">
    <link rel="alternate" hreflang="fr" href="https://example.com/fr/">
    <link rel="alternate" hreflang="x-default" href="https://example.com/">
</head>
<body>Content</body>
</html>
HTML;

        $result = $this->metaDataExtractor->extractFromHtml($multiLangHtml);
        $seoMeta = $result['seo_meta'];
        $hreflangLinks = $seoMeta['language']['hreflang_links'];

        $this->assertIsArray($hreflangLinks);
        $this->assertCount(3, $hreflangLinks);

        $languages = array_column($hreflangLinks, 'hreflang');
        $this->assertContains('es', $languages);
        $this->assertContains('fr', $languages);
        $this->assertContains('x-default', $languages);
    }

    public function testComplexStructuredData(): void
    {
        $complexStructuredDataHtml = <<<HTML
<html>
<head>
    <script type="application/ld+json">
    [
        {
            "@context": "https://schema.org",
            "@type": "Organization",
            "name": "Example Organization",
            "url": "https://example.com",
            "logo": {
                "@type": "ImageObject",
                "url": "https://example.com/logo.png"
            }
        },
        {
            "@context": "https://schema.org",
            "@type": "WebSite",
            "name": "Example Website",
            "url": "https://example.com",
            "potentialAction": {
                "@type": "SearchAction",
                "target": "https://example.com/search?q={search_term_string}",
                "query-input": "required name=search_term_string"
            }
        }
    ]
    </script>
</head>
<body>Content</body>
</html>
HTML;

        $result = $this->metaDataExtractor->extractJsonLd($complexStructuredDataHtml);

        $this->assertEquals(2, $result['total_count']);
        $this->assertContains('Organization', $result['schema_types']);
        $this->assertContains('WebSite', $result['schema_types']);

        $schemas = $result['schemas'];
        $this->assertIsArray($schemas[0]);
        $this->assertIsArray($schemas[1]);
    }

    public function testPerformanceOptimizationHints(): void
    {
        $performanceHtml = <<<HTML
<html>
<head>
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//cdn.example.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" href="/critical.css" as="style">
    <link rel="preload" href="/hero-image.jpg" as="image">
    <link rel="prefetch" href="/next-page.html">
    <link rel="prerender" href="/likely-next.html">
</head>
<body>Content</body>
</html>
HTML;

        $result = $this->metaDataExtractor->extractPerformanceMeta($performanceHtml);

        $this->assertCount(2, $result['dns_prefetch']);
        $this->assertCount(1, $result['preconnect']);
        $this->assertCount(2, $result['preload']);
        $this->assertCount(1, $result['prefetch']);
        $this->assertCount(1, $result['prerender']);

        $this->assertEquals(100, $result['resource_hints_score']); // All hint types present
    }

    public function testAccessibilityMetadata(): void
    {
        $accessibilityHtml = <<<HTML
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accessible Page</title>
</head>
<body>Content</body>
</html>
HTML;

        $result = $this->metaDataExtractor->extractFromHtml($accessibilityHtml);
        $technicalMeta = $result['technical_meta'];
        $seoMeta = $result['seo_meta'];

        $this->assertTrue($technicalMeta['viewport']['responsive']);
        $this->assertEquals('en', $seoMeta['language']['html_lang']);
        $this->assertEquals('UTF-8', $technicalMeta['encoding']['charset']);
    }
}