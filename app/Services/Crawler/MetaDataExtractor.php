<?php

namespace App\Services\Crawler;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Meta Data Extractor for meta tags, Open Graph, Twitter Cards, and structured data
 *
 * Extracts and analyzes all forms of metadata from web pages including
 * standard meta tags, social media meta tags, and structured data formats.
 */
class MetaDataExtractor
{
    private DOMDocument $dom;
    private DOMXPath $xpath;
    private array $config;

    public function __construct()
    {
        $this->config = config('puppeteer.metadata_analysis', []);
        libxml_use_internal_errors(true);
    }

    /**
     * Extract all metadata from HTML
     *
     * @param string $html HTML content
     * @param string $url Base URL for relative link resolution
     * @param array $options Extraction options
     * @return array Complete metadata analysis
     */
    public function extractFromHtml(string $html, string $url = '', array $options = []): array
    {
        try {
            $this->loadHtml($html);

            return [
                'basic_meta' => $this->extractBasicMetaTags(),
                'seo_meta' => $this->extractSeoMetaTags(),
                'social_meta' => $this->extractSocialMetaTags(),
                'technical_meta' => $this->extractTechnicalMetaTags(),
                'structured_data' => $this->extractStructuredData(),
                'link_relations' => $this->extractLinkRelations($url),
                'dublin_core' => $this->extractDublinCore(),
                'custom_meta' => $this->extractCustomMetaTags(),
                'validation' => $this->validateMetadata($url),
                'recommendations' => $this->generateRecommendations(),
                'extracted_at' => time()
            ];

        } catch (Exception $e) {
            Log::error("Metadata extraction failed", ['error' => $e->getMessage()]);
            throw new Exception("Failed to extract metadata: " . $e->getMessage());
        }
    }

    /**
     * Extract Open Graph metadata
     *
     * @param string $html HTML content
     * @return array Open Graph data
     */
    public function extractOpenGraph(string $html): array
    {
        try {
            $this->loadHtml($html);

            $ogData = [];
            $elements = $this->xpath->query('//meta[@property and starts-with(@property, "og:")]');

            foreach ($elements as $element) {
                $property = $element->getAttribute('property');
                $content = $element->getAttribute('content');

                if (!empty($content)) {
                    // Handle nested properties (e.g., og:image:width)
                    $this->setNestedProperty($ogData, $property, $content);
                }
            }

            return [
                'properties' => $ogData,
                'validation' => $this->validateOpenGraph($ogData),
                'completeness_score' => $this->calculateOpenGraphCompleteness($ogData),
                'recommendations' => $this->generateOpenGraphRecommendations($ogData)
            ];

        } catch (Exception $e) {
            Log::error("Open Graph extraction failed", ['error' => $e->getMessage()]);
            return $this->getEmptyOpenGraphData();
        }
    }

    /**
     * Extract Twitter Card metadata
     *
     * @param string $html HTML content
     * @return array Twitter Card data
     */
    public function extractTwitterCard(string $html): array
    {
        try {
            $this->loadHtml($html);

            $twitterData = [];
            $elements = $this->xpath->query('//meta[@name and starts-with(@name, "twitter:")]');

            foreach ($elements as $element) {
                $name = $element->getAttribute('name');
                $content = $element->getAttribute('content');

                if (!empty($content)) {
                    $twitterData[str_replace('twitter:', '', $name)] = $content;
                }
            }

            return [
                'properties' => $twitterData,
                'card_type' => $twitterData['card'] ?? 'summary',
                'validation' => $this->validateTwitterCard($twitterData),
                'completeness_score' => $this->calculateTwitterCardCompleteness($twitterData),
                'recommendations' => $this->generateTwitterCardRecommendations($twitterData)
            ];

        } catch (Exception $e) {
            Log::error("Twitter Card extraction failed", ['error' => $e->getMessage()]);
            return $this->getEmptyTwitterCardData();
        }
    }

    /**
     * Extract JSON-LD structured data
     *
     * @param string $html HTML content
     * @return array JSON-LD data
     */
    public function extractJsonLd(string $html): array
    {
        try {
            $jsonLdData = [];

            if (preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches)) {
                foreach ($matches[1] as $jsonString) {
                    $decoded = json_decode(trim($jsonString), true);

                    if (json_last_error() === JSON_ERROR_NONE && $decoded) {
                        $jsonLdData[] = $decoded;
                    }
                }
            }

            return [
                'schemas' => $jsonLdData,
                'total_count' => count($jsonLdData),
                'schema_types' => $this->extractSchemaTypes($jsonLdData),
                'validation' => $this->validateJsonLd($jsonLdData),
                'completeness_score' => $this->calculateJsonLdCompleteness($jsonLdData),
                'recommendations' => $this->generateJsonLdRecommendations($jsonLdData)
            ];

        } catch (Exception $e) {
            Log::error("JSON-LD extraction failed", ['error' => $e->getMessage()]);
            return $this->getEmptyJsonLdData();
        }
    }

    /**
     * Extract microdata structured data
     *
     * @param string $html HTML content
     * @return array Microdata information
     */
    public function extractMicrodata(string $html): array
    {
        try {
            $this->loadHtml($html);

            $microdata = [];
            $elements = $this->xpath->query('//*[@itemscope]');

            foreach ($elements as $element) {
                $itemType = $element->getAttribute('itemtype');
                $itemData = $this->extractMicrodataProperties($element);

                $microdata[] = [
                    'itemtype' => $itemType,
                    'properties' => $itemData,
                    'xpath' => $element->getNodePath()
                ];
            }

            return [
                'items' => $microdata,
                'total_count' => count($microdata),
                'schema_types' => array_unique(array_column($microdata, 'itemtype')),
                'validation' => $this->validateMicrodata($microdata),
                'recommendations' => $this->generateMicrodataRecommendations($microdata)
            ];

        } catch (Exception $e) {
            Log::error("Microdata extraction failed", ['error' => $e->getMessage()]);
            return $this->getEmptyMicrodataData();
        }
    }

    /**
     * Extract RDFa structured data
     *
     * @param string $html HTML content
     * @return array RDFa information
     */
    public function extractRdfa(string $html): array
    {
        try {
            $this->loadHtml($html);

            $rdfa = [];
            $elements = $this->xpath->query('//*[@typeof or @property or @resource or @about]');

            foreach ($elements as $element) {
                $rdfaData = [
                    'typeof' => $element->getAttribute('typeof'),
                    'property' => $element->getAttribute('property'),
                    'resource' => $element->getAttribute('resource'),
                    'about' => $element->getAttribute('about'),
                    'content' => $element->getAttribute('content') ?: $element->textContent,
                    'xpath' => $element->getNodePath()
                ];

                // Only include elements with RDFa attributes
                if (array_filter($rdfaData, function($value, $key) {
                    return $key !== 'content' && $key !== 'xpath' && !empty($value);
                }, ARRAY_FILTER_USE_BOTH)) {
                    $rdfa[] = $rdfaData;
                }
            }

            return [
                'items' => $rdfa,
                'total_count' => count($rdfa),
                'vocabularies' => $this->extractRdfaVocabularies($rdfa),
                'validation' => $this->validateRdfa($rdfa),
                'recommendations' => $this->generateRdfaRecommendations($rdfa)
            ];

        } catch (Exception $e) {
            Log::error("RDFa extraction failed", ['error' => $e->getMessage()]);
            return $this->getEmptyRdfaData();
        }
    }

    /**
     * Analyze metadata for mobile optimization
     *
     * @param string $html HTML content
     * @return array Mobile metadata analysis
     */
    public function analyzeMobileMeta(string $html): array
    {
        try {
            $this->loadHtml($html);

            return [
                'viewport' => $this->analyzeViewportMeta(),
                'mobile_app_meta' => $this->extractMobileAppMeta(),
                'touch_icons' => $this->extractTouchIcons(),
                'theme_color' => $this->extractThemeColor(),
                'manifest' => $this->extractWebAppManifest(),
                'mobile_optimization_score' => $this->calculateMobileOptimizationScore(),
                'recommendations' => $this->generateMobileMetaRecommendations()
            ];

        } catch (Exception $e) {
            Log::error("Mobile metadata analysis failed", ['error' => $e->getMessage()]);
            return $this->getEmptyMobileMetaAnalysis();
        }
    }

    /**
     * Extract performance-related metadata
     *
     * @param string $html HTML content
     * @return array Performance metadata
     */
    public function extractPerformanceMeta(string $html): array
    {
        try {
            $this->loadHtml($html);

            return [
                'dns_prefetch' => $this->extractDnsPrefetchLinks(),
                'preconnect' => $this->extractPreconnectLinks(),
                'preload' => $this->extractPreloadLinks(),
                'prefetch' => $this->extractPrefetchLinks(),
                'prerender' => $this->extractPrerenderLinks(),
                'resource_hints_score' => $this->calculateResourceHintsScore(),
                'recommendations' => $this->generatePerformanceMetaRecommendations()
            ];

        } catch (Exception $e) {
            Log::error("Performance metadata extraction failed", ['error' => $e->getMessage()]);
            return $this->getEmptyPerformanceMetaAnalysis();
        }
    }

    /**
     * Load HTML into DOM document
     *
     * @param string $html HTML content
     * @return void
     * @throws Exception On loading failures
     */
    private function loadHtml(string $html): void
    {
        $this->dom = new DOMDocument();
        $this->dom->preserveWhiteSpace = false;
        $this->dom->formatOutput = true;

        if (empty(trim($html))) {
            throw new Exception("Empty HTML content provided");
        }

        $success = @$this->dom->loadHTML('<?xml encoding="UTF-8">' . $html,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOCDATA);

        if (!$success) {
            $errors = libxml_get_errors();
            $errorMsg = !empty($errors) ? $errors[0]->message : 'Unknown DOM parsing error';
            throw new Exception("Failed to parse HTML: " . trim($errorMsg));
        }

        $this->xpath = new DOMXPath($this->dom);
    }

    /**
     * Extract basic meta tags
     *
     * @return array Basic meta tags
     */
    private function extractBasicMetaTags(): array
    {
        $basicMeta = [];

        // Title
        $titleElement = $this->xpath->query('//title')->item(0);
        $basicMeta['title'] = [
            'content' => $titleElement ? trim($titleElement->textContent) : '',
            'length' => $titleElement ? strlen(trim($titleElement->textContent)) : 0,
            'word_count' => $titleElement ? str_word_count(trim($titleElement->textContent)) : 0
        ];

        // Meta description
        $descriptionElement = $this->xpath->query('//meta[@name="description"]')->item(0);
        $basicMeta['description'] = [
            'content' => $descriptionElement ? $descriptionElement->getAttribute('content') : '',
            'length' => $descriptionElement ? strlen($descriptionElement->getAttribute('content')) : 0,
            'word_count' => $descriptionElement ? str_word_count($descriptionElement->getAttribute('content')) : 0
        ];

        // Meta keywords
        $keywordsElement = $this->xpath->query('//meta[@name="keywords"]')->item(0);
        $keywordsContent = $keywordsElement ? $keywordsElement->getAttribute('content') : '';
        $basicMeta['keywords'] = [
            'content' => $keywordsContent,
            'keywords_array' => $keywordsContent ? array_map('trim', explode(',', $keywordsContent)) : [],
            'count' => $keywordsContent ? count(array_map('trim', explode(',', $keywordsContent))) : 0
        ];

        // Meta author
        $authorElement = $this->xpath->query('//meta[@name="author"]')->item(0);
        $basicMeta['author'] = $authorElement ? $authorElement->getAttribute('content') : '';

        // Meta generator
        $generatorElement = $this->xpath->query('//meta[@name="generator"]')->item(0);
        $basicMeta['generator'] = $generatorElement ? $generatorElement->getAttribute('content') : '';

        return $basicMeta;
    }

    /**
     * Extract SEO-specific meta tags
     *
     * @return array SEO meta tags
     */
    private function extractSeoMetaTags(): array
    {
        $seoMeta = [];

        // Robots meta
        $robotsElement = $this->xpath->query('//meta[@name="robots"]')->item(0);
        $robotsContent = $robotsElement ? $robotsElement->getAttribute('content') : '';
        $robotsDirectives = $robotsContent ? array_map('trim', explode(',', strtolower($robotsContent))) : [];

        $seoMeta['robots'] = [
            'content' => $robotsContent,
            'directives' => $robotsDirectives,
            'indexable' => !in_array('noindex', $robotsDirectives),
            'followable' => !in_array('nofollow', $robotsDirectives),
            'archivable' => !in_array('noarchive', $robotsDirectives),
            'snippetable' => !in_array('nosnippet', $robotsDirectives)
        ];

        // Canonical URL
        $canonicalElement = $this->xpath->query('//link[@rel="canonical"]')->item(0);
        $seoMeta['canonical'] = [
            'href' => $canonicalElement ? $canonicalElement->getAttribute('href') : '',
            'exists' => $canonicalElement !== null
        ];

        // Meta refresh
        $refreshElement = $this->xpath->query('//meta[@http-equiv="refresh"]')->item(0);
        $seoMeta['refresh'] = [
            'content' => $refreshElement ? $refreshElement->getAttribute('content') : '',
            'exists' => $refreshElement !== null
        ];

        // Language declarations
        $htmlLang = $this->xpath->query('//html')->item(0);
        $langMeta = $this->xpath->query('//meta[@name="language"]')->item(0);

        $seoMeta['language'] = [
            'html_lang' => $htmlLang ? $htmlLang->getAttribute('lang') : '',
            'meta_language' => $langMeta ? $langMeta->getAttribute('content') : '',
            'hreflang_links' => $this->extractHreflangLinks()
        ];

        return $seoMeta;
    }

    /**
     * Extract social media meta tags
     *
     * @return array Social media meta tags
     */
    private function extractSocialMetaTags(): array
    {
        return [
            'open_graph' => $this->extractOpenGraphProperties(),
            'twitter_card' => $this->extractTwitterCardProperties(),
            'facebook' => $this->extractFacebookProperties(),
            'pinterest' => $this->extractPinterestProperties(),
            'linkedin' => $this->extractLinkedInProperties()
        ];
    }

    /**
     * Extract technical meta tags
     *
     * @return array Technical meta tags
     */
    private function extractTechnicalMetaTags(): array
    {
        $technicalMeta = [];

        // Content-Type and charset
        $charsetElement = $this->xpath->query('//meta[@charset]')->item(0);
        $contentTypeElement = $this->xpath->query('//meta[@http-equiv="Content-Type"]')->item(0);

        $technicalMeta['encoding'] = [
            'charset' => $charsetElement ? $charsetElement->getAttribute('charset') : '',
            'content_type' => $contentTypeElement ? $contentTypeElement->getAttribute('content') : ''
        ];

        // Viewport
        $viewportElement = $this->xpath->query('//meta[@name="viewport"]')->item(0);
        $technicalMeta['viewport'] = $this->parseViewportContent($viewportElement);

        // X-UA-Compatible
        $compatibleElement = $this->xpath->query('//meta[@http-equiv="X-UA-Compatible"]')->item(0);
        $technicalMeta['compatibility'] = [
            'content' => $compatibleElement ? $compatibleElement->getAttribute('content') : '',
            'ie_edge_mode' => $compatibleElement && strpos($compatibleElement->getAttribute('content'), 'IE=edge') !== false
        ];

        // Content Security Policy
        $cspElement = $this->xpath->query('//meta[@http-equiv="Content-Security-Policy"]')->item(0);
        $technicalMeta['security'] = [
            'csp' => $cspElement ? $cspElement->getAttribute('content') : '',
            'has_csp' => $cspElement !== null
        ];

        // Format detection (mobile)
        $formatDetectionElement = $this->xpath->query('//meta[@name="format-detection"]')->item(0);
        $technicalMeta['format_detection'] = $formatDetectionElement ? $formatDetectionElement->getAttribute('content') : '';

        return $technicalMeta;
    }

    /**
     * Extract structured data
     *
     * @return array Structured data analysis
     */
    private function extractStructuredData(): array
    {
        return [
            'json_ld' => $this->extractJsonLdData(),
            'microdata' => $this->extractMicrodataItems(),
            'rdfa' => $this->extractRdfaItems(),
            'summary' => $this->summarizeStructuredData()
        ];
    }

    /**
     * Extract link relations
     *
     * @param string $baseUrl Base URL for relative link resolution
     * @return array Link relations
     */
    private function extractLinkRelations(string $baseUrl): array
    {
        $linkRelations = [];
        $linkElements = $this->xpath->query('//link[@rel]');

        foreach ($linkElements as $element) {
            $rel = $element->getAttribute('rel');
            $href = $element->getAttribute('href');

            if (!isset($linkRelations[$rel])) {
                $linkRelations[$rel] = [];
            }

            $linkData = [
                'href' => $href,
                'type' => $element->getAttribute('type'),
                'media' => $element->getAttribute('media'),
                'hreflang' => $element->getAttribute('hreflang'),
                'sizes' => $element->getAttribute('sizes'),
                'title' => $element->getAttribute('title'),
                'absolute_url' => $this->resolveUrl($href, $baseUrl)
            ];

            $linkRelations[$rel][] = array_filter($linkData);
        }

        return $linkRelations;
    }

    /**
     * Extract Dublin Core metadata
     *
     * @return array Dublin Core metadata
     */
    private function extractDublinCore(): array
    {
        $dublinCore = [];
        $dcElements = $this->xpath->query('//meta[starts-with(@name, "DC.") or starts-with(@name, "dc.")]');

        foreach ($dcElements as $element) {
            $name = $element->getAttribute('name');
            $content = $element->getAttribute('content');
            $property = strtolower(str_replace(['DC.', 'dc.'], '', $name));

            $dublinCore[$property] = $content;
        }

        return [
            'properties' => $dublinCore,
            'completeness_score' => $this->calculateDublinCoreCompleteness($dublinCore)
        ];
    }

    /**
     * Extract custom meta tags
     *
     * @return array Custom meta tags
     */
    private function extractCustomMetaTags(): array
    {
        $customMeta = [];
        $allMetaElements = $this->xpath->query('//meta[@name or @property]');

        $standardNames = [
            'description', 'keywords', 'author', 'robots', 'viewport', 'generator',
            'language', 'revisit-after', 'rating', 'format-detection'
        ];

        foreach ($allMetaElements as $element) {
            $name = $element->getAttribute('name') ?: $element->getAttribute('property');
            $content = $element->getAttribute('content');

            // Skip standard meta tags and social media tags
            if (!in_array($name, $standardNames) &&
                !str_starts_with($name, 'og:') &&
                !str_starts_with($name, 'twitter:') &&
                !str_starts_with($name, 'fb:')) {

                $customMeta[$name] = $content;
            }
        }

        return [
            'tags' => $customMeta,
            'count' => count($customMeta)
        ];
    }

    /**
     * Validate metadata completeness and correctness
     *
     * @param string $url Page URL for validation context
     * @return array Validation results
     */
    private function validateMetadata(string $url): array
    {
        $validation = [
            'errors' => [],
            'warnings' => [],
            'passed' => [],
            'overall_score' => 0
        ];

        // Validate title
        $title = $this->extractBasicMetaTags()['title'];
        if (empty($title['content'])) {
            $validation['errors'][] = 'Missing page title';
        } elseif ($title['length'] > 60) {
            $validation['warnings'][] = 'Title tag is too long (over 60 characters)';
        } elseif ($title['length'] < 30) {
            $validation['warnings'][] = 'Title tag is too short (under 30 characters)';
        } else {
            $validation['passed'][] = 'Title tag length is optimal';
        }

        // Validate description
        $description = $this->extractBasicMetaTags()['description'];
        if (empty($description['content'])) {
            $validation['errors'][] = 'Missing meta description';
        } elseif ($description['length'] > 160) {
            $validation['warnings'][] = 'Meta description is too long (over 160 characters)';
        } elseif ($description['length'] < 120) {
            $validation['warnings'][] = 'Meta description is too short (under 120 characters)';
        } else {
            $validation['passed'][] = 'Meta description length is optimal';
        }

        // Validate viewport
        $viewport = $this->extractTechnicalMetaTags()['viewport'];
        if (empty($viewport['content'])) {
            $validation['errors'][] = 'Missing viewport meta tag';
        } else {
            $validation['passed'][] = 'Viewport meta tag present';
        }

        // Validate charset
        $encoding = $this->extractTechnicalMetaTags()['encoding'];
        if (empty($encoding['charset']) && empty($encoding['content_type'])) {
            $validation['errors'][] = 'Missing character encoding declaration';
        } else {
            $validation['passed'][] = 'Character encoding declared';
        }

        // Calculate overall score
        $totalChecks = count($validation['errors']) + count($validation['warnings']) + count($validation['passed']);
        $passedChecks = count($validation['passed']) + (count($validation['warnings']) * 0.5);
        $validation['overall_score'] = $totalChecks > 0 ? round(($passedChecks / $totalChecks) * 100, 1) : 0;

        return $validation;
    }

    /**
     * Generate metadata recommendations
     *
     * @return array Recommendations
     */
    private function generateRecommendations(): array
    {
        $recommendations = [];
        $basicMeta = $this->extractBasicMetaTags();
        $seoMeta = $this->extractSeoMetaTags();
        $socialMeta = $this->extractSocialMetaTags();

        // Title recommendations
        if (empty($basicMeta['title']['content'])) {
            $recommendations[] = 'Add a page title';
        } elseif ($basicMeta['title']['length'] > 60) {
            $recommendations[] = 'Shorten the title tag to under 60 characters';
        } elseif ($basicMeta['title']['length'] < 30) {
            $recommendations[] = 'Lengthen the title tag to at least 30 characters';
        }

        // Description recommendations
        if (empty($basicMeta['description']['content'])) {
            $recommendations[] = 'Add a meta description';
        } elseif ($basicMeta['description']['length'] > 160) {
            $recommendations[] = 'Shorten the meta description to under 160 characters';
        } elseif ($basicMeta['description']['length'] < 120) {
            $recommendations[] = 'Lengthen the meta description to at least 120 characters';
        }

        // Social media recommendations
        if (empty($socialMeta['open_graph']['og:title'])) {
            $recommendations[] = 'Add Open Graph title for better social media sharing';
        }

        if (empty($socialMeta['twitter_card']['card'])) {
            $recommendations[] = 'Add Twitter Card meta tags for better Twitter sharing';
        }

        // Canonical URL recommendation
        if (empty($seoMeta['canonical']['href'])) {
            $recommendations[] = 'Add a canonical URL to avoid duplicate content issues';
        }

        return $recommendations;
    }

    // Helper methods for specific extractions

    private function extractOpenGraphProperties(): array
    {
        $ogData = [];
        $elements = $this->xpath->query('//meta[@property and starts-with(@property, "og:")]');

        foreach ($elements as $element) {
            $property = str_replace('og:', '', $element->getAttribute('property'));
            $content = $element->getAttribute('content');
            $ogData[$property] = $content;
        }

        return $ogData;
    }

    private function extractTwitterCardProperties(): array
    {
        $twitterData = [];
        $elements = $this->xpath->query('//meta[@name and starts-with(@name, "twitter:")]');

        foreach ($elements as $element) {
            $name = str_replace('twitter:', '', $element->getAttribute('name'));
            $content = $element->getAttribute('content');
            $twitterData[$name] = $content;
        }

        return $twitterData;
    }

    private function extractFacebookProperties(): array
    {
        $fbData = [];
        $elements = $this->xpath->query('//meta[@property and starts-with(@property, "fb:")]');

        foreach ($elements as $element) {
            $property = str_replace('fb:', '', $element->getAttribute('property'));
            $content = $element->getAttribute('content');
            $fbData[$property] = $content;
        }

        return $fbData;
    }

    private function extractPinterestProperties(): array
    {
        $pinterestData = [];

        // Pinterest description
        $descElement = $this->xpath->query('//meta[@name="pinterest-description"]')->item(0);
        if ($descElement) {
            $pinterestData['description'] = $descElement->getAttribute('content');
        }

        // Pinterest rich pins
        $richPinsElement = $this->xpath->query('//meta[@name="pinterest-rich-pin"]')->item(0);
        if ($richPinsElement) {
            $pinterestData['rich_pin'] = $richPinsElement->getAttribute('content');
        }

        return $pinterestData;
    }

    private function extractLinkedInProperties(): array
    {
        // LinkedIn doesn't have specific meta tags, but uses Open Graph
        return ['uses_open_graph' => !empty($this->extractOpenGraphProperties())];
    }

    private function extractHreflangLinks(): array
    {
        $hreflangLinks = [];
        $elements = $this->xpath->query('//link[@rel="alternate" and @hreflang]');

        foreach ($elements as $element) {
            $hreflangLinks[] = [
                'hreflang' => $element->getAttribute('hreflang'),
                'href' => $element->getAttribute('href')
            ];
        }

        return $hreflangLinks;
    }

    private function parseViewportContent($viewportElement): array
    {
        if (!$viewportElement) {
            return ['content' => '', 'responsive' => false, 'properties' => []];
        }

        $content = $viewportElement->getAttribute('content');
        $properties = [];

        if (!empty($content)) {
            $parts = explode(',', $content);
            foreach ($parts as $part) {
                if (strpos($part, '=') !== false) {
                    list($key, $value) = explode('=', trim($part), 2);
                    $properties[trim($key)] = trim($value);
                }
            }
        }

        return [
            'content' => $content,
            'responsive' => isset($properties['width']) && $properties['width'] === 'device-width',
            'properties' => $properties
        ];
    }

    private function extractJsonLdData(): array
    {
        $jsonLdData = [];

        if (preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $this->dom->saveHTML(), $matches)) {
            foreach ($matches[1] as $jsonString) {
                $decoded = json_decode(trim($jsonString), true);
                if (json_last_error() === JSON_ERROR_NONE && $decoded) {
                    $jsonLdData[] = $decoded;
                }
            }
        }

        return $jsonLdData;
    }

    private function extractMicrodataItems(): array
    {
        $items = [];
        $elements = $this->xpath->query('//*[@itemscope]');

        foreach ($elements as $element) {
            $items[] = [
                'itemtype' => $element->getAttribute('itemtype'),
                'properties' => $this->extractMicrodataProperties($element)
            ];
        }

        return $items;
    }

    private function extractMicrodataProperties($element): array
    {
        $properties = [];
        $propertyElements = $this->xpath->query('.//*[@itemprop]', $element);

        foreach ($propertyElements as $propElement) {
            $propName = $propElement->getAttribute('itemprop');
            $propValue = $propElement->getAttribute('content') ?: $propElement->textContent;
            $properties[$propName] = trim($propValue);
        }

        return $properties;
    }

    private function extractRdfaItems(): array
    {
        $items = [];
        $elements = $this->xpath->query('//*[@typeof]');

        foreach ($elements as $element) {
            $items[] = [
                'typeof' => $element->getAttribute('typeof'),
                'properties' => $this->extractRdfaProperties($element)
            ];
        }

        return $items;
    }

    private function extractRdfaProperties($element): array
    {
        $properties = [];
        $propertyElements = $this->xpath->query('.//*[@property]', $element);

        foreach ($propertyElements as $propElement) {
            $propName = $propElement->getAttribute('property');
            $propValue = $propElement->getAttribute('content') ?: $propElement->textContent;
            $properties[$propName] = trim($propValue);
        }

        return $properties;
    }

    private function summarizeStructuredData(): array
    {
        $jsonLd = $this->extractJsonLdData();
        $microdata = $this->extractMicrodataItems();
        $rdfa = $this->extractRdfaItems();

        return [
            'json_ld_count' => count($jsonLd),
            'microdata_count' => count($microdata),
            'rdfa_count' => count($rdfa),
            'total_structured_data' => count($jsonLd) + count($microdata) + count($rdfa),
            'primary_format' => $this->determinePrimaryStructuredDataFormat($jsonLd, $microdata, $rdfa)
        ];
    }

    private function determinePrimaryStructuredDataFormat($jsonLd, $microdata, $rdfa): string
    {
        $counts = [
            'json_ld' => count($jsonLd),
            'microdata' => count($microdata),
            'rdfa' => count($rdfa)
        ];

        $maxFormat = array_keys($counts, max($counts))[0];
        return max($counts) > 0 ? $maxFormat : 'none';
    }

    // Mobile metadata methods

    private function analyzeViewportMeta(): array
    {
        $viewportElement = $this->xpath->query('//meta[@name="viewport"]')->item(0);
        return $this->parseViewportContent($viewportElement);
    }

    private function extractMobileAppMeta(): array
    {
        $appMeta = [];

        // Apple mobile web app
        $appleCapableElement = $this->xpath->query('//meta[@name="apple-mobile-web-app-capable"]')->item(0);
        $appMeta['apple_mobile_web_app_capable'] = $appleCapableElement ? $appleCapableElement->getAttribute('content') : '';

        $appleStatusBarElement = $this->xpath->query('//meta[@name="apple-mobile-web-app-status-bar-style"]')->item(0);
        $appMeta['apple_status_bar_style'] = $appleStatusBarElement ? $appleStatusBarElement->getAttribute('content') : '';

        $appleTitleElement = $this->xpath->query('//meta[@name="apple-mobile-web-app-title"]')->item(0);
        $appMeta['apple_mobile_web_app_title'] = $appleTitleElement ? $appleTitleElement->getAttribute('content') : '';

        // Microsoft tiles
        $tileColorElement = $this->xpath->query('//meta[@name="msapplication-TileColor"]')->item(0);
        $appMeta['ms_tile_color'] = $tileColorElement ? $tileColorElement->getAttribute('content') : '';

        $tileImageElement = $this->xpath->query('//meta[@name="msapplication-TileImage"]')->item(0);
        $appMeta['ms_tile_image'] = $tileImageElement ? $tileImageElement->getAttribute('content') : '';

        return array_filter($appMeta);
    }

    private function extractTouchIcons(): array
    {
        $touchIcons = [];
        $iconElements = $this->xpath->query('//link[@rel="apple-touch-icon" or @rel="apple-touch-icon-precomposed"]');

        foreach ($iconElements as $element) {
            $touchIcons[] = [
                'rel' => $element->getAttribute('rel'),
                'href' => $element->getAttribute('href'),
                'sizes' => $element->getAttribute('sizes')
            ];
        }

        return $touchIcons;
    }

    private function extractThemeColor(): array
    {
        $themeColorElement = $this->xpath->query('//meta[@name="theme-color"]')->item(0);
        $msThemeColorElement = $this->xpath->query('//meta[@name="msapplication-navbutton-color"]')->item(0);

        return [
            'theme_color' => $themeColorElement ? $themeColorElement->getAttribute('content') : '',
            'ms_theme_color' => $msThemeColorElement ? $msThemeColorElement->getAttribute('content') : ''
        ];
    }

    private function extractWebAppManifest(): array
    {
        $manifestElement = $this->xpath->query('//link[@rel="manifest"]')->item(0);

        return [
            'href' => $manifestElement ? $manifestElement->getAttribute('href') : '',
            'exists' => $manifestElement !== null
        ];
    }

    private function calculateMobileOptimizationScore(): int
    {
        $score = 0;

        $viewport = $this->analyzeViewportMeta();
        if ($viewport['responsive']) $score += 30;

        $touchIcons = $this->extractTouchIcons();
        if (!empty($touchIcons)) $score += 20;

        $themeColor = $this->extractThemeColor();
        if (!empty($themeColor['theme_color'])) $score += 20;

        $manifest = $this->extractWebAppManifest();
        if ($manifest['exists']) $score += 30;

        return $score;
    }

    private function generateMobileMetaRecommendations(): array
    {
        $recommendations = [];

        $viewport = $this->analyzeViewportMeta();
        if (!$viewport['responsive']) {
            $recommendations[] = 'Add responsive viewport meta tag';
        }

        $touchIcons = $this->extractTouchIcons();
        if (empty($touchIcons)) {
            $recommendations[] = 'Add Apple touch icons for iOS home screen';
        }

        $themeColor = $this->extractThemeColor();
        if (empty($themeColor['theme_color'])) {
            $recommendations[] = 'Add theme-color meta tag for mobile browsers';
        }

        $manifest = $this->extractWebAppManifest();
        if (!$manifest['exists']) {
            $recommendations[] = 'Add web app manifest for progressive web app features';
        }

        return $recommendations;
    }

    // Performance metadata methods

    private function extractDnsPrefetchLinks(): array
    {
        return $this->extractLinksByRel('dns-prefetch');
    }

    private function extractPreconnectLinks(): array
    {
        return $this->extractLinksByRel('preconnect');
    }

    private function extractPreloadLinks(): array
    {
        return $this->extractLinksByRel('preload');
    }

    private function extractPrefetchLinks(): array
    {
        return $this->extractLinksByRel('prefetch');
    }

    private function extractPrerenderLinks(): array
    {
        return $this->extractLinksByRel('prerender');
    }

    private function extractLinksByRel(string $rel): array
    {
        $links = [];
        $elements = $this->xpath->query("//link[@rel='{$rel}']");

        foreach ($elements as $element) {
            $links[] = [
                'href' => $element->getAttribute('href'),
                'as' => $element->getAttribute('as'),
                'type' => $element->getAttribute('type'),
                'crossorigin' => $element->getAttribute('crossorigin')
            ];
        }

        return array_map('array_filter', $links);
    }

    private function calculateResourceHintsScore(): int
    {
        $score = 0;

        if (!empty($this->extractDnsPrefetchLinks())) $score += 25;
        if (!empty($this->extractPreconnectLinks())) $score += 25;
        if (!empty($this->extractPreloadLinks())) $score += 25;
        if (!empty($this->extractPrefetchLinks())) $score += 25;

        return $score;
    }

    private function generatePerformanceMetaRecommendations(): array
    {
        $recommendations = [];

        if (empty($this->extractDnsPrefetchLinks()) && empty($this->extractPreconnectLinks())) {
            $recommendations[] = 'Consider adding dns-prefetch or preconnect for external domains';
        }

        if (empty($this->extractPreloadLinks())) {
            $recommendations[] = 'Consider preloading critical resources';
        }

        return $recommendations;
    }

    // Validation and scoring methods

    private function validateOpenGraph($ogData): array
    {
        $validation = ['errors' => [], 'warnings' => [], 'passed' => []];

        if (empty($ogData['og:title'])) {
            $validation['errors'][] = 'Missing og:title';
        } else {
            $validation['passed'][] = 'og:title present';
        }

        if (empty($ogData['og:description'])) {
            $validation['errors'][] = 'Missing og:description';
        } else {
            $validation['passed'][] = 'og:description present';
        }

        if (empty($ogData['og:image'])) {
            $validation['warnings'][] = 'Missing og:image';
        } else {
            $validation['passed'][] = 'og:image present';
        }

        if (empty($ogData['og:url'])) {
            $validation['warnings'][] = 'Missing og:url';
        } else {
            $validation['passed'][] = 'og:url present';
        }

        return $validation;
    }

    private function calculateOpenGraphCompleteness($ogData): int
    {
        $requiredProperties = ['title', 'description', 'image', 'url'];
        $presentCount = 0;

        foreach ($requiredProperties as $property) {
            if (!empty($ogData[$property])) {
                $presentCount++;
            }
        }

        return round(($presentCount / count($requiredProperties)) * 100);
    }

    private function generateOpenGraphRecommendations($ogData): array
    {
        $recommendations = [];

        if (empty($ogData['title'])) {
            $recommendations[] = 'Add og:title for social media sharing';
        }

        if (empty($ogData['description'])) {
            $recommendations[] = 'Add og:description for social media sharing';
        }

        if (empty($ogData['image'])) {
            $recommendations[] = 'Add og:image for social media sharing';
        }

        return $recommendations;
    }

    private function validateTwitterCard($twitterData): array
    {
        $validation = ['errors' => [], 'warnings' => [], 'passed' => []];

        if (empty($twitterData['card'])) {
            $validation['errors'][] = 'Missing twitter:card';
        } else {
            $validation['passed'][] = 'twitter:card present';
        }

        $cardType = $twitterData['card'] ?? '';
        if ($cardType === 'summary_large_image' && empty($twitterData['image'])) {
            $validation['errors'][] = 'Missing twitter:image for summary_large_image card';
        }

        return $validation;
    }

    private function calculateTwitterCardCompleteness($twitterData): int
    {
        $requiredProperties = ['card'];
        $optionalProperties = ['title', 'description', 'image'];
        $presentCount = 0;

        foreach ($requiredProperties as $property) {
            if (!empty($twitterData[$property])) {
                $presentCount++;
            }
        }

        foreach ($optionalProperties as $property) {
            if (!empty($twitterData[$property])) {
                $presentCount += 0.5; // Optional properties get half weight
            }
        }

        $totalPossible = count($requiredProperties) + (count($optionalProperties) * 0.5);
        return round(($presentCount / $totalPossible) * 100);
    }

    private function generateTwitterCardRecommendations($twitterData): array
    {
        $recommendations = [];

        if (empty($twitterData['card'])) {
            $recommendations[] = 'Add twitter:card meta tag';
        }

        if (empty($twitterData['title'])) {
            $recommendations[] = 'Add twitter:title for better Twitter sharing';
        }

        return $recommendations;
    }

    private function extractSchemaTypes($jsonLdData): array
    {
        $types = [];

        foreach ($jsonLdData as $schema) {
            if (isset($schema['@type'])) {
                $type = is_array($schema['@type']) ? $schema['@type'][0] : $schema['@type'];
                $types[] = $type;
            }
        }

        return array_unique($types);
    }

    private function validateJsonLd($jsonLdData): array
    {
        $validation = ['errors' => [], 'warnings' => [], 'passed' => []];

        if (empty($jsonLdData)) {
            $validation['warnings'][] = 'No JSON-LD structured data found';
            return $validation;
        }

        foreach ($jsonLdData as $index => $schema) {
            if (!isset($schema['@context'])) {
                $validation['errors'][] = "Missing @context in schema {$index}";
            }

            if (!isset($schema['@type'])) {
                $validation['errors'][] = "Missing @type in schema {$index}";
            }
        }

        if (empty($validation['errors'])) {
            $validation['passed'][] = 'JSON-LD schemas are well-formed';
        }

        return $validation;
    }

    private function calculateJsonLdCompleteness($jsonLdData): int
    {
        if (empty($jsonLdData)) {
            return 0;
        }

        $totalSchemas = count($jsonLdData);
        $completeSchemas = 0;

        foreach ($jsonLdData as $schema) {
            if (isset($schema['@context']) && isset($schema['@type'])) {
                $completeSchemas++;
            }
        }

        return round(($completeSchemas / $totalSchemas) * 100);
    }

    private function generateJsonLdRecommendations($jsonLdData): array
    {
        $recommendations = [];

        if (empty($jsonLdData)) {
            $recommendations[] = 'Add JSON-LD structured data for better search engine understanding';
        }

        return $recommendations;
    }

    private function validateMicrodata($microdata): array
    {
        $validation = ['errors' => [], 'warnings' => [], 'passed' => []];

        if (empty($microdata)) {
            $validation['warnings'][] = 'No Microdata found';
            return $validation;
        }

        foreach ($microdata as $index => $item) {
            if (empty($item['itemtype'])) {
                $validation['warnings'][] = "Missing itemtype in microdata item {$index}";
            }

            if (empty($item['properties'])) {
                $validation['warnings'][] = "No properties found in microdata item {$index}";
            }
        }

        if (empty($validation['errors']) && !empty($microdata)) {
            $validation['passed'][] = 'Microdata structure is valid';
        }

        return $validation;
    }

    private function generateMicrodataRecommendations($microdata): array
    {
        $recommendations = [];

        if (empty($microdata)) {
            $recommendations[] = 'Consider adding Microdata for structured content';
        }

        return $recommendations;
    }

    private function extractRdfaVocabularies($rdfa): array
    {
        $vocabularies = [];

        foreach ($rdfa as $item) {
            if (!empty($item['typeof'])) {
                // Extract vocabulary from typeof attribute
                if (strpos($item['typeof'], ':') !== false) {
                    $parts = explode(':', $item['typeof']);
                    $vocabularies[] = $parts[0];
                }
            }
        }

        return array_unique($vocabularies);
    }

    private function validateRdfa($rdfa): array
    {
        $validation = ['errors' => [], 'warnings' => [], 'passed' => []];

        if (empty($rdfa)) {
            $validation['warnings'][] = 'No RDFa found';
            return $validation;
        }

        if (!empty($rdfa)) {
            $validation['passed'][] = 'RDFa attributes found';
        }

        return $validation;
    }

    private function generateRdfaRecommendations($rdfa): array
    {
        $recommendations = [];

        if (empty($rdfa)) {
            $recommendations[] = 'Consider adding RDFa markup for semantic content';
        }

        return $recommendations;
    }

    private function calculateDublinCoreCompleteness($dublinCore): int
    {
        $coreElements = ['title', 'creator', 'subject', 'description', 'date', 'type', 'format', 'identifier'];
        $presentCount = 0;

        foreach ($coreElements as $element) {
            if (!empty($dublinCore[$element])) {
                $presentCount++;
            }
        }

        return round(($presentCount / count($coreElements)) * 100);
    }

    // Utility methods

    private function setNestedProperty(array &$array, string $property, string $value): void
    {
        $keys = explode(':', $property);
        $current = &$array;

        foreach ($keys as $key) {
            if (!isset($current[$key])) {
                $current[$key] = [];
            }
            $current = &$current[$key];
        }

        $current = $value;
    }

    private function resolveUrl(string $href, string $baseUrl): string
    {
        if (empty($href) || empty($baseUrl)) {
            return $href;
        }

        if (filter_var($href, FILTER_VALIDATE_URL)) {
            return $href; // Already absolute
        }

        $baseParts = parse_url($baseUrl);
        if (!$baseParts) {
            return $href;
        }

        $scheme = $baseParts['scheme'] ?? 'https';
        $host = $baseParts['host'] ?? '';

        if (strpos($href, '//') === 0) {
            return $scheme . ':' . $href;
        }

        if (strpos($href, '/') === 0) {
            return $scheme . '://' . $host . $href;
        }

        $path = $baseParts['path'] ?? '/';
        $basePath = rtrim(dirname($path), '/');

        return $scheme . '://' . $host . $basePath . '/' . $href;
    }

    // Empty data methods for error handling

    private function getEmptyOpenGraphData(): array
    {
        return [
            'properties' => [],
            'validation' => ['errors' => ['Extraction failed'], 'warnings' => [], 'passed' => []],
            'completeness_score' => 0,
            'recommendations' => []
        ];
    }

    private function getEmptyTwitterCardData(): array
    {
        return [
            'properties' => [],
            'card_type' => 'unknown',
            'validation' => ['errors' => ['Extraction failed'], 'warnings' => [], 'passed' => []],
            'completeness_score' => 0,
            'recommendations' => []
        ];
    }

    private function getEmptyJsonLdData(): array
    {
        return [
            'schemas' => [],
            'total_count' => 0,
            'schema_types' => [],
            'validation' => ['errors' => ['Extraction failed'], 'warnings' => [], 'passed' => []],
            'completeness_score' => 0,
            'recommendations' => []
        ];
    }

    private function getEmptyMicrodataData(): array
    {
        return [
            'items' => [],
            'total_count' => 0,
            'schema_types' => [],
            'validation' => ['errors' => ['Extraction failed'], 'warnings' => [], 'passed' => []],
            'recommendations' => []
        ];
    }

    private function getEmptyRdfaData(): array
    {
        return [
            'items' => [],
            'total_count' => 0,
            'vocabularies' => [],
            'validation' => ['errors' => ['Extraction failed'], 'warnings' => [], 'passed' => []],
            'recommendations' => []
        ];
    }

    private function getEmptyMobileMetaAnalysis(): array
    {
        return [
            'viewport' => ['content' => '', 'responsive' => false, 'properties' => []],
            'mobile_app_meta' => [],
            'touch_icons' => [],
            'theme_color' => ['theme_color' => '', 'ms_theme_color' => ''],
            'manifest' => ['href' => '', 'exists' => false],
            'mobile_optimization_score' => 0,
            'recommendations' => []
        ];
    }

    private function getEmptyPerformanceMetaAnalysis(): array
    {
        return [
            'dns_prefetch' => [],
            'preconnect' => [],
            'preload' => [],
            'prefetch' => [],
            'prerender' => [],
            'resource_hints_score' => 0,
            'recommendations' => []
        ];
    }
}