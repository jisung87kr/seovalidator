<?php

namespace App\Services\Parser;

use DOMDocument;
use DOMXPath;
use DOMNode;
use DOMNodeList;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class HtmlParserService
{
    private DOMDocument $dom;
    private DOMXPath $xpath;
    private string $baseUrl;

    /**
     * Parse HTML content and extract SEO-relevant data
     */
    public function parse(string $html, string $url): array
    {
        $this->baseUrl = $url;

        Log::debug('Starting HTML parsing', [
            'url' => $url,
            'html_size' => strlen($html)
        ]);

        try {
            $this->initializeDom($html);

            $parsedData = [
                'meta' => $this->extractMetaTags(),
                'headings' => $this->extractHeadings(),
                'images' => $this->extractImages(),
                'links' => $this->extractLinks(),
                'content' => $this->extractContent($html),
                'technical' => $this->extractTechnicalData($html),
                'structured_data' => $this->extractStructuredData(),
                'social_media' => $this->extractSocialMediaTags(),
                'seo_tags' => $this->extractSeoSpecificTags(),
                'performance' => $this->extractPerformanceHints()
            ];

            Log::debug('HTML parsing completed successfully', [
                'url' => $url,
                'meta_tags_found' => count($parsedData['meta']),
                'headings_found' => array_sum(array_map('count', $parsedData['headings'])),
                'images_found' => $parsedData['images']['total_count'],
                'links_found' => $parsedData['links']['total_count']
            ]);

            return $parsedData;

        } catch (\Exception $e) {
            Log::error('HTML parsing failed', [
                'url' => $url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new HtmlParsingException("Failed to parse HTML content: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Initialize DOM document with HTML content
     */
    private function initializeDom(string $html): void
    {
        $this->dom = new DOMDocument();

        // Suppress errors for malformed HTML
        libxml_use_internal_errors(true);

        // Load HTML with UTF-8 encoding
        $this->dom->loadHTML(
            '<?xml encoding="UTF-8">' . $html,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        // Clear libxml errors
        libxml_clear_errors();

        $this->xpath = new DOMXPath($this->dom);
    }

    /**
     * Extract meta tags and related information
     */
    private function extractMetaTags(): array
    {
        $meta = [
            'title' => $this->getTitle(),
            'description' => $this->getMetaContent('name', 'description'),
            'keywords' => $this->getMetaContent('name', 'keywords'),
            'author' => $this->getMetaContent('name', 'author'),
            'robots' => $this->getMetaContent('name', 'robots'),
            'viewport' => $this->getMetaContent('name', 'viewport'),
            'charset' => $this->getCharset(),
            'canonical' => $this->getCanonicalUrl(),
            'alternate_languages' => $this->getAlternateLanguages(),
            'refresh' => $this->getMetaContent('http-equiv', 'refresh')
        ];

        // Calculate lengths
        $meta['title_length'] = strlen($meta['title']);
        $meta['description_length'] = strlen($meta['description']);
        $meta['keywords_count'] = $meta['keywords'] ? count(explode(',', $meta['keywords'])) : 0;

        return $meta;
    }

    /**
     * Extract page title
     */
    private function getTitle(): string
    {
        $titleNodes = $this->xpath->query('//title');
        return $titleNodes->length > 0
            ? trim($titleNodes->item(0)->textContent)
            : '';
    }

    /**
     * Get meta content by attribute and value
     */
    private function getMetaContent(string $attribute, string $value): string
    {
        $metaNodes = $this->xpath->query("//meta[@{$attribute}='{$value}']/@content");
        return $metaNodes->length > 0
            ? trim($metaNodes->item(0)->value)
            : '';
    }

    /**
     * Get page charset
     */
    private function getCharset(): string
    {
        // Try charset attribute
        $charsetNodes = $this->xpath->query('//meta[@charset]/@charset');
        if ($charsetNodes->length > 0) {
            return $charsetNodes->item(0)->value;
        }

        // Try http-equiv content-type
        $contentTypeNodes = $this->xpath->query('//meta[@http-equiv="content-type"]/@content');
        if ($contentTypeNodes->length > 0) {
            $content = $contentTypeNodes->item(0)->value;
            if (preg_match('/charset=([^;]+)/i', $content, $matches)) {
                return trim($matches[1]);
            }
        }

        return '';
    }

    /**
     * Get canonical URL
     */
    private function getCanonicalUrl(): string
    {
        $canonicalNodes = $this->xpath->query('//link[@rel="canonical"]/@href');
        return $canonicalNodes->length > 0
            ? $this->resolveUrl($canonicalNodes->item(0)->value)
            : '';
    }

    /**
     * Get alternate language links
     */
    private function getAlternateLanguages(): array
    {
        $alternates = [];
        $alternateNodes = $this->xpath->query('//link[@rel="alternate" and @hreflang]');

        foreach ($alternateNodes as $node) {
            $hreflang = $node->getAttribute('hreflang');
            $href = $this->resolveUrl($node->getAttribute('href'));
            $alternates[$hreflang] = $href;
        }

        return $alternates;
    }

    /**
     * Extract heading structure
     */
    private function extractHeadings(): array
    {
        $headings = [];

        for ($level = 1; $level <= 6; $level++) {
            $headingNodes = $this->xpath->query("//h{$level}");
            $headings["h{$level}"] = [];

            foreach ($headingNodes as $heading) {
                $text = trim($heading->textContent);
                if (!empty($text)) {
                    $headings["h{$level}"][] = [
                        'text' => $text,
                        'length' => strlen($text),
                        'position' => count($headings["h{$level}"]) + 1
                    ];
                }
            }
        }

        return $headings;
    }

    /**
     * Extract image information
     */
    private function extractImages(): array
    {
        $imageNodes = $this->xpath->query('//img');
        $images = [];
        $withoutAlt = 0;
        $withoutTitle = 0;
        $withoutSrc = 0;

        foreach ($imageNodes as $img) {
            $src = $img->getAttribute('src');
            $alt = $img->getAttribute('alt');
            $title = $img->getAttribute('title');
            $width = $img->getAttribute('width');
            $height = $img->getAttribute('height');

            if (empty($src)) {
                $withoutSrc++;
                continue;
            }

            if (empty($alt)) $withoutAlt++;
            if (empty($title)) $withoutTitle++;

            $images[] = [
                'src' => $this->resolveUrl($src),
                'alt' => $alt,
                'title' => $title,
                'width' => $width ?: null,
                'height' => $height ?: null,
                'has_alt' => !empty($alt),
                'has_title' => !empty($title),
                'alt_length' => strlen($alt),
                'is_decorative' => empty($alt) && $img->hasAttribute('role') && $img->getAttribute('role') === 'presentation'
            ];
        }

        return [
            'total_count' => count($images),
            'without_alt_count' => $withoutAlt,
            'without_title_count' => $withoutTitle,
            'without_src_count' => $withoutSrc,
            'images' => $images
        ];
    }

    /**
     * Extract link information
     */
    private function extractLinks(): array
    {
        $linkNodes = $this->xpath->query('//a[@href]');
        $links = [];
        $internalLinks = 0;
        $externalLinks = 0;
        $noFollowLinks = 0;
        $emptyAnchorText = 0;

        $parsedBaseUrl = parse_url($this->baseUrl);
        $baseDomain = $parsedBaseUrl['host'] ?? '';

        foreach ($linkNodes as $link) {
            $href = $link->getAttribute('href');
            $anchorText = trim($link->textContent);
            $rel = $link->getAttribute('rel');
            $title = $link->getAttribute('title');

            if (empty($anchorText)) {
                $emptyAnchorText++;
            }

            $resolvedUrl = $this->resolveUrl($href);
            $isExternal = $this->isExternalLink($resolvedUrl, $baseDomain);

            if ($isExternal) {
                $externalLinks++;
            } else {
                $internalLinks++;
            }

            if (str_contains(strtolower($rel), 'nofollow')) {
                $noFollowLinks++;
            }

            $links[] = [
                'href' => $resolvedUrl,
                'anchor_text' => $anchorText,
                'anchor_text_length' => strlen($anchorText),
                'title' => $title,
                'rel' => $rel,
                'is_external' => $isExternal,
                'is_nofollow' => str_contains(strtolower($rel), 'nofollow'),
                'has_title' => !empty($title),
                'is_empty_anchor' => empty($anchorText)
            ];
        }

        return [
            'total_count' => count($links),
            'internal_count' => $internalLinks,
            'external_count' => $externalLinks,
            'nofollow_count' => $noFollowLinks,
            'empty_anchor_count' => $emptyAnchorText,
            'links' => $links
        ];
    }

    /**
     * Extract content information
     */
    private function extractContent(string $html): array
    {
        // Get body content
        $bodyNodes = $this->xpath->query('//body');
        $bodyContent = $bodyNodes->length > 0 ? $bodyNodes->item(0)->textContent : $html;

        // Clean text content
        $textContent = $this->cleanTextContent($bodyContent);

        // Calculate metrics
        $wordCount = str_word_count($textContent);
        $characterCount = strlen($textContent);
        $htmlSize = strlen($html);

        return [
            'word_count' => $wordCount,
            'character_count' => $characterCount,
            'character_count_no_spaces' => strlen(str_replace(' ', '', $textContent)),
            'html_size' => $htmlSize,
            'text_to_html_ratio' => $htmlSize > 0 ? round(($characterCount / $htmlSize) * 100, 2) : 0,
            'reading_time_minutes' => ceil($wordCount / 200), // Average reading speed
            'sentences' => $this->countSentences($textContent),
            'paragraphs' => ($result = $this->xpath->query('//p')) !== false ? $result->length : 0,
            'average_words_per_sentence' => $this->calculateAverageWordsPerSentence($textContent)
        ];
    }

    /**
     * Extract technical SEO data
     */
    private function extractTechnicalData(string $html): array
    {
        return [
            'doctype' => $this->getDoctype($html),
            'lang_attribute' => $this->getLangAttribute(),
            'schema_markup_present' => $this->hasSchemaMarkup(),
            'open_graph_present' => $this->hasOpenGraph(),
            'twitter_cards_present' => $this->hasTwitterCards(),
            'amp_present' => $this->isAmpPage(),
            'ssl_required' => $this->requiresSSL(),
            'external_resources' => $this->getExternalResources(),
            'inline_styles_count' => ($result = $this->xpath->query('//style')) !== false ? $result->length : 0,
            'inline_scripts_count' => ($result = $this->xpath->query('//script[not(@src)]')) !== false ? $result->length : 0
        ];
    }

    /**
     * Extract structured data (JSON-LD, Microdata)
     */
    private function extractStructuredData(): array
    {
        $structuredData = [
            'json_ld' => $this->extractJsonLd(),
            'microdata' => $this->extractMicrodata(),
            'rdfa' => $this->extractRdfa(),
            'schemas_found' => []
        ];

        // Combine all schema types found
        $allSchemas = array_merge(
            array_keys($structuredData['json_ld']),
            array_keys($structuredData['microdata']),
            array_keys($structuredData['rdfa'])
        );

        $structuredData['schemas_found'] = array_unique($allSchemas);

        return $structuredData;
    }

    /**
     * Extract social media tags
     */
    private function extractSocialMediaTags(): array
    {
        return [
            'open_graph' => $this->extractOpenGraphTags(),
            'twitter_cards' => $this->extractTwitterCardTags(),
            'facebook' => $this->extractFacebookTags(),
            'linkedin' => $this->extractLinkedInTags()
        ];
    }

    /**
     * Extract Open Graph tags
     */
    private function extractOpenGraphTags(): array
    {
        $ogTags = [];
        $ogNodes = $this->xpath->query('//meta[starts-with(@property, "og:")]');

        foreach ($ogNodes as $node) {
            $property = $node->getAttribute('property');
            $content = $node->getAttribute('content');
            $ogTags[str_replace('og:', '', $property)] = $content;
        }

        return $ogTags;
    }

    /**
     * Extract Twitter Card tags
     */
    private function extractTwitterCardTags(): array
    {
        $twitterTags = [];
        $twitterNodes = $this->xpath->query('//meta[starts-with(@name, "twitter:")]');

        foreach ($twitterNodes as $node) {
            $name = $node->getAttribute('name');
            $content = $node->getAttribute('content');
            $twitterTags[str_replace('twitter:', '', $name)] = $content;
        }

        return $twitterTags;
    }

    /**
     * Extract Facebook-specific tags
     */
    private function extractFacebookTags(): array
    {
        return [
            'app_id' => $this->getMetaContent('property', 'fb:app_id'),
            'admins' => $this->getMetaContent('property', 'fb:admins')
        ];
    }

    /**
     * Extract LinkedIn-specific tags
     */
    private function extractLinkedInTags(): array
    {
        return [
            'partner_id' => $this->getMetaContent('property', 'linkedin:partner-id')
        ];
    }

    /**
     * Extract SEO-specific tags
     */
    private function extractSeoSpecificTags(): array
    {
        return [
            'next_page' => $this->getMetaContent('rel', 'next'),
            'prev_page' => $this->getMetaContent('rel', 'prev'),
            'amp_html' => $this->xpath->query('//link[@rel="amphtml"]/@href')->item(0)?->value ?? '',
            'mobile_alternate' => $this->xpath->query('//link[@rel="alternate" and @media="only screen and (max-width: 640px)"]/@href')->item(0)?->value ?? ''
        ];
    }

    /**
     * Extract performance hints
     */
    private function extractPerformanceHints(): array
    {
        return [
            'dns_prefetch_count' => ($result = $this->xpath->query('//link[@rel="dns-prefetch"]')) !== false ? $result->length : 0,
            'preconnect_count' => ($result = $this->xpath->query('//link[@rel="preconnect"]')) !== false ? $result->length : 0,
            'prefetch_count' => ($result = $this->xpath->query('//link[@rel="prefetch"]')) !== false ? $result->length : 0,
            'preload_count' => ($result = $this->xpath->query('//link[@rel="preload"]')) !== false ? $result->length : 0,
            'external_css_count' => ($result = $this->xpath->query('//link[@rel="stylesheet" and @href]')) !== false ? $result->length : 0,
            'external_js_count' => ($result = $this->xpath->query('//script[@src]')) !== false ? $result->length : 0
        ];
    }

    /**
     * Extract JSON-LD structured data
     */
    private function extractJsonLd(): array
    {
        $jsonLdData = [];
        $scriptNodes = $this->xpath->query('//script[@type="application/ld+json"]');

        foreach ($scriptNodes as $script) {
            try {
                $json = json_decode($script->textContent, true);
                if ($json && isset($json['@type'])) {
                    $type = is_array($json['@type']) ? $json['@type'][0] : $json['@type'];
                    $jsonLdData[$type] = $json;
                }
            } catch (\Exception $e) {
                Log::warning('Failed to parse JSON-LD', ['error' => $e->getMessage()]);
            }
        }

        return $jsonLdData;
    }

    /**
     * Extract Microdata
     */
    private function extractMicrodata(): array
    {
        $microdata = [];
        $itemNodes = $this->xpath->query('//*[@itemscope]');

        foreach ($itemNodes as $item) {
            $itemType = $item->getAttribute('itemtype');
            if ($itemType) {
                $type = basename($itemType);
                if (!isset($microdata[$type])) {
                    $microdata[$type] = [];
                }
                $microdata[$type][] = $this->extractMicrodataItem($item);
            }
        }

        return $microdata;
    }

    /**
     * Extract RDFa data
     */
    private function extractRdfa(): array
    {
        $rdfa = [];
        $rdfaNodes = $this->xpath->query('//*[@typeof]');

        foreach ($rdfaNodes as $node) {
            $typeof = $node->getAttribute('typeof');
            if ($typeof) {
                if (!isset($rdfa[$typeof])) {
                    $rdfa[$typeof] = [];
                }
                $rdfa[$typeof][] = $this->extractRdfaItem($node);
            }
        }

        return $rdfa;
    }

    /**
     * Helper methods
     */

    private function cleanTextContent(string $text): string
    {
        // Remove extra whitespace and normalize
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    private function countSentences(string $text): int
    {
        return preg_match_all('/[.!?]+/', $text);
    }

    private function calculateAverageWordsPerSentence(string $text): float
    {
        $sentences = $this->countSentences($text);
        $words = str_word_count($text);
        return $sentences > 0 ? round($words / $sentences, 2) : 0;
    }

    private function resolveUrl(string $url): string
    {
        if (empty($url)) {
            return '';
        }

        if (parse_url($url, PHP_URL_SCHEME)) {
            return $url; // Already absolute
        }

        $baseUrlParts = parse_url($this->baseUrl);
        $baseScheme = $baseUrlParts['scheme'] ?? 'https';
        $baseHost = $baseUrlParts['host'] ?? '';
        $basePath = $baseUrlParts['path'] ?? '/';

        if (str_starts_with($url, '//')) {
            return $baseScheme . ':' . $url;
        }

        if (str_starts_with($url, '/')) {
            return $baseScheme . '://' . $baseHost . $url;
        }

        // Relative URL
        $basePath = rtrim(dirname($basePath), '/');
        return $baseScheme . '://' . $baseHost . $basePath . '/' . $url;
    }

    private function isExternalLink(string $url, string $baseDomain): bool
    {
        $urlDomain = parse_url($url, PHP_URL_HOST);
        return $urlDomain && $urlDomain !== $baseDomain;
    }

    private function getDoctype(string $html): string
    {
        if (preg_match('/<!DOCTYPE[^>]*>/i', $html, $matches)) {
            return trim($matches[0]);
        }
        return '';
    }

    private function getLangAttribute(): string
    {
        $htmlNodes = $this->xpath->query('//html[@lang]/@lang');
        return $htmlNodes->length > 0 ? $htmlNodes->item(0)->value : '';
    }

    private function hasSchemaMarkup(): bool
    {
        $result = $this->xpath->query('//script[@type="application/ld+json"] | //*[@itemscope] | //*[@typeof]');
        return $result !== false && $result->length > 0;
    }

    private function hasOpenGraph(): bool
    {
        $result = $this->xpath->query('//meta[starts-with(@property, "og:")]');
        return $result !== false && $result->length > 0;
    }

    private function hasTwitterCards(): bool
    {
        $result = $this->xpath->query('//meta[starts-with(@name, "twitter:")]');
        return $result !== false && $result->length > 0;
    }

    private function isAmpPage(): bool
    {
        $result = $this->xpath->query('//html[@amp] | //html[@⚡]');
        return $result !== false && $result->length > 0;
    }

    private function requiresSSL(): bool
    {
        // Check for security-related meta tags or HTTPS-only content
        $httpsOnlyNodes = $this->xpath->query('//meta[@http-equiv="Content-Security-Policy" and contains(@content, "upgrade-insecure-requests")]');
        return ($httpsOnlyNodes !== false && $httpsOnlyNodes->length > 0) || str_contains($this->baseUrl, 'https://');
    }

    private function getExternalResources(): array
    {
        $resources = [
            'css' => [],
            'js' => [],
            'images' => [],
            'fonts' => []
        ];

        // External CSS
        $cssNodes = $this->xpath->query('//link[@rel="stylesheet" and @href]');
        foreach ($cssNodes as $css) {
            $href = $css->getAttribute('href');
            if ($this->isExternalLink($href, parse_url($this->baseUrl, PHP_URL_HOST))) {
                $resources['css'][] = $href;
            }
        }

        // External JS
        $jsNodes = $this->xpath->query('//script[@src]');
        foreach ($jsNodes as $js) {
            $src = $js->getAttribute('src');
            if ($this->isExternalLink($src, parse_url($this->baseUrl, PHP_URL_HOST))) {
                $resources['js'][] = $src;
            }
        }

        return $resources;
    }

    private function extractMicrodataItem(DOMNode $item): array
    {
        // Simplified microdata extraction
        return ['itemtype' => $item->getAttribute('itemtype')];
    }

    private function extractRdfaItem(DOMNode $item): array
    {
        // Simplified RDFa extraction
        return ['typeof' => $item->getAttribute('typeof')];
    }
}

/**
 * Custom exception for HTML parsing errors
 */
class HtmlParsingException extends \Exception
{
    //
}