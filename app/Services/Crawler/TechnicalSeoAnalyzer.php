<?php

namespace App\Services\Crawler;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Log;

/**
 * Technical SEO factors analysis service
 * Provides specialized analysis for technical SEO aspects not covered by basic HTML parsing
 */
class TechnicalSeoAnalyzer
{
    private DOMDocument $dom;
    private DOMXPath $xpath;
    private string $baseUrl;
    private string $html;

    /**
     * Initialize analyzer with HTML content and URL
     */
    public function initialize(string $html, string $url): void
    {
        $this->html = $html;
        $this->baseUrl = $url;
        $this->initializeDom($html);
    }

    /**
     * Perform comprehensive technical SEO analysis
     */
    public function analyze(): array
    {
        Log::debug('Starting technical SEO analysis', [
            'url' => $this->baseUrl,
            'html_size' => strlen($this->html)
        ]);

        return [
            'page_speed' => $this->analyzePageSpeedFactors(),
            'mobile_optimization' => $this->analyzeMobileOptimization(),
            'crawlability' => $this->analyzeCrawlability(),
            'indexability' => $this->analyzeIndexability(),
            'structured_data_validation' => $this->analyzeStructuredDataValidation(),
            'html_validation' => $this->analyzeHtmlValidation(),
            'security_factors' => $this->analyzeSecurityFactors(),
            'international_seo' => $this->analyzeInternationalSeo(),
            'local_seo_factors' => $this->analyzeLocalSeoFactors(),
            'technical_performance' => $this->analyzeTechnicalPerformance(),
            'content_optimization' => $this->analyzeContentOptimization(),
            'url_structure' => $this->analyzeUrlStructure(),
            'server_factors' => $this->analyzeServerFactors()
        ];
    }

    /**
     * Analyze page speed related factors
     */
    private function analyzePageSpeedFactors(): array
    {
        return [
            'critical_rendering_path' => $this->analyzeCriticalRenderingPath(),
            'resource_optimization' => $this->analyzeResourceOptimization(),
            'caching_strategy' => $this->analyzeCachingStrategy(),
            'compression' => $this->analyzeCompression(),
            'lazy_loading' => $this->analyzeLazyLoading(),
            'third_party_impact' => $this->analyzeThirdPartyImpact(),
            'javascript_optimization' => $this->analyzeJavaScriptOptimization(),
            'css_optimization' => $this->analyzeCssOptimization(),
            'image_optimization' => $this->analyzeImageOptimization()
        ];
    }

    /**
     * Analyze mobile optimization factors
     */
    private function analyzeMobileOptimization(): array
    {
        return [
            'viewport_configuration' => $this->analyzeViewportConfiguration(),
            'responsive_design' => $this->analyzeResponsiveDesign(),
            'mobile_usability' => $this->analyzeMobileUsability(),
            'touch_optimization' => $this->analyzeTouchOptimization(),
            'mobile_performance' => $this->analyzeMobilePerformance(),
            'amp_implementation' => $this->analyzeAmpImplementation()
        ];
    }

    /**
     * Analyze crawlability factors
     */
    private function analyzeCrawlability(): array
    {
        return [
            'robots_meta' => $this->analyzeRobotsMeta(),
            'navigation_structure' => $this->analyzeNavigationStructure(),
            'internal_linking' => $this->analyzeInternalLinking(),
            'url_parameters' => $this->analyzeUrlParameters(),
            'pagination' => $this->analyzePagination(),
            'sitemap_references' => $this->analyzeSitemapReferences(),
            'crawl_budget' => $this->analyzeCrawlBudget()
        ];
    }

    /**
     * Analyze indexability factors
     */
    private function analyzeIndexability(): array
    {
        return [
            'meta_robots' => $this->analyzeMetaRobots(),
            'canonical_tags' => $this->analyzeCanonicalTags(),
            'duplicate_content' => $this->analyzeDuplicateContent(),
            'content_quality' => $this->analyzeContentQuality(),
            'thin_content' => $this->analyzeThinContent(),
            'noindex_patterns' => $this->analyzeNoindexPatterns()
        ];
    }

    /**
     * Analyze structured data validation
     */
    private function analyzeStructuredDataValidation(): array
    {
        return [
            'json_ld_validation' => $this->validateJsonLd(),
            'microdata_validation' => $this->validateMicrodata(),
            'rdfa_validation' => $this->validateRdfa(),
            'schema_completeness' => $this->analyzeSchemaCompleteness(),
            'rich_snippets_potential' => $this->analyzeRichSnippetsPotential()
        ];
    }

    /**
     * Analyze HTML validation issues
     */
    private function analyzeHtmlValidation(): array
    {
        return [
            'doctype_validation' => $this->validateDoctype(),
            'html_structure' => $this->validateHtmlStructure(),
            'semantic_markup' => $this->validateSemanticMarkup(),
            'accessibility_compliance' => $this->analyzeAccessibilityCompliance(),
            'w3c_validation_hints' => $this->getW3cValidationHints()
        ];
    }

    /**
     * Analyze security factors affecting SEO
     */
    private function analyzeSecurityFactors(): array
    {
        return [
            'https_implementation' => $this->analyzeHttpsImplementation(),
            'security_headers' => $this->analyzeSecurityHeaders(),
            'mixed_content' => $this->analyzeMixedContent(),
            'external_resource_security' => $this->analyzeExternalResourceSecurity(),
            'content_security_policy' => $this->analyzeContentSecurityPolicy()
        ];
    }

    /**
     * Analyze international SEO factors
     */
    private function analyzeInternationalSeo(): array
    {
        return [
            'hreflang_implementation' => $this->analyzeHreflangImplementation(),
            'language_detection' => $this->analyzeLanguageDetection(),
            'geo_targeting' => $this->analyzeGeoTargeting(),
            'currency_localization' => $this->analyzeCurrencyLocalization(),
            'cultural_adaptation' => $this->analyzeCulturalAdaptation()
        ];
    }

    /**
     * Analyze local SEO factors
     */
    private function analyzeLocalSeoFactors(): array
    {
        return [
            'local_business_schema' => $this->analyzeLocalBusinessSchema(),
            'contact_information' => $this->analyzeContactInformation(),
            'location_signals' => $this->analyzeLocationSignals(),
            'opening_hours' => $this->analyzeOpeningHours(),
            'local_citations' => $this->analyzeLocalCitations()
        ];
    }

    /**
     * Analyze technical performance factors
     */
    private function analyzeTechnicalPerformance(): array
    {
        return [
            'dom_complexity' => $this->analyzeDomComplexity(),
            'render_blocking' => $this->analyzeRenderBlocking(),
            'javascript_errors' => $this->analyzeJavaScriptErrors(),
            'css_efficiency' => $this->analyzeCssEfficiency(),
            'memory_usage_hints' => $this->analyzeMemoryUsageHints()
        ];
    }

    /**
     * Analyze content optimization factors
     */
    private function analyzeContentOptimization(): array
    {
        return [
            'heading_optimization' => $this->analyzeHeadingOptimization(),
            'keyword_placement' => $this->analyzeKeywordPlacement(),
            'content_freshness' => $this->analyzeContentFreshness(),
            'readability' => $this->analyzeReadability(),
            'content_depth' => $this->analyzeContentDepth(),
            'semantic_relevance' => $this->analyzeSemanticRelevance()
        ];
    }

    /**
     * Analyze URL structure factors
     */
    private function analyzeUrlStructure(): array
    {
        $urlParts = parse_url($this->baseUrl);

        return [
            'url_length' => strlen($this->baseUrl),
            'path_depth' => substr_count($urlParts['path'] ?? '/', '/'),
            'parameters_count' => isset($urlParts['query']) ? substr_count($urlParts['query'], '&') + 1 : 0,
            'has_fragment' => isset($urlParts['fragment']),
            'url_readability' => $this->analyzeUrlReadability($this->baseUrl),
            'keyword_presence' => $this->analyzeKeywordPresenceInUrl($this->baseUrl),
            'url_case_consistency' => $this->analyzeUrlCaseConsistency($this->baseUrl)
        ];
    }

    /**
     * Analyze server-related factors
     */
    private function analyzeServerFactors(): array
    {
        return [
            'response_time_hints' => $this->analyzeResponseTimeHints(),
            'server_push_hints' => $this->analyzeServerPushHints(),
            'cdn_usage' => $this->analyzeCdnUsage(),
            'compression_hints' => $this->analyzeCompressionHints(),
            'caching_headers' => $this->analyzeCachingHeaders()
        ];
    }

    // Implementation of specific analysis methods

    private function analyzeCriticalRenderingPath(): array
    {
        return [
            'render_blocking_css' => $this->xpath->query('//link[@rel="stylesheet" and not(@media="print") and not(@onload)]')->length,
            'render_blocking_js' => $this->xpath->query('//script[not(@async) and not(@defer) and position()<//body]')->length,
            'critical_css_inlined' => $this->xpath->query('//style')->length > 0,
            'above_fold_optimization' => $this->hasAboveFoldOptimization(),
            'font_display_strategy' => $this->analyzesFontDisplayStrategy()
        ];
    }

    private function analyzeResourceOptimization(): array
    {
        $externalResources = [
            'css' => $this->xpath->query('//link[@rel="stylesheet"]')->length,
            'js' => $this->xpath->query('//script[@src]')->length,
            'images' => $this->xpath->query('//img[@src]')->length
        ];

        return [
            'total_resources' => array_sum($externalResources),
            'resource_breakdown' => $externalResources,
            'minification_hints' => $this->getMinificationHints(),
            'concatenation_opportunities' => $this->getConcatenationOpportunities(),
            'resource_preloading' => $this->xpath->query('//link[@rel="preload"]')->length
        ];
    }

    private function analyzeLazyLoading(): array
    {
        $totalImages = $this->xpath->query('//img')->length;
        $lazyImages = $this->xpath->query('//img[@loading="lazy"]')->length;
        $totalIframes = $this->xpath->query('//iframe')->length;
        $lazyIframes = $this->xpath->query('//iframe[@loading="lazy"]')->length;

        return [
            'images_with_lazy_loading' => $lazyImages,
            'images_total' => $totalImages,
            'lazy_loading_percentage_images' => $totalImages > 0 ? round(($lazyImages / $totalImages) * 100, 2) : 0,
            'iframes_with_lazy_loading' => $lazyIframes,
            'iframes_total' => $totalIframes,
            'lazy_loading_percentage_iframes' => $totalIframes > 0 ? round(($lazyIframes / $totalIframes) * 100, 2) : 0,
            'intersection_observer_usage' => $this->hasIntersectionObserver()
        ];
    }

    private function analyzeThirdPartyImpact(): array
    {
        $scripts = $this->xpath->query('//script[@src]');
        $thirdPartyScripts = [];
        $baseDomain = parse_url($this->baseUrl, PHP_URL_HOST);

        foreach ($scripts as $script) {
            $src = $script->getAttribute('src');
            $domain = parse_url($src, PHP_URL_HOST);

            if ($domain && $domain !== $baseDomain) {
                $thirdPartyScripts[] = $domain;
            }
        }

        $uniqueDomains = array_unique($thirdPartyScripts);

        return [
            'third_party_scripts_count' => count($thirdPartyScripts),
            'unique_third_party_domains' => count($uniqueDomains),
            'domains' => array_values($uniqueDomains),
            'async_third_party' => $this->xpath->query('//script[@src and @async]')->length,
            'defer_third_party' => $this->xpath->query('//script[@src and @defer]')->length,
            'third_party_performance_impact' => $this->calculateThirdPartyImpact($uniqueDomains)
        ];
    }

    private function analyzeViewportConfiguration(): array
    {
        $viewport = $this->xpath->query('//meta[@name="viewport"]/@content');
        $viewportContent = $viewport->length > 0 ? $viewport->item(0)->value : '';

        return [
            'has_viewport_meta' => !empty($viewportContent),
            'viewport_content' => $viewportContent,
            'has_width_device_width' => str_contains($viewportContent, 'width=device-width'),
            'has_initial_scale' => str_contains($viewportContent, 'initial-scale'),
            'user_scalable' => !str_contains($viewportContent, 'user-scalable=no'),
            'viewport_optimization_score' => $this->calculateViewportScore($viewportContent)
        ];
    }

    private function analyzeResponsiveDesign(): array
    {
        return [
            'media_queries_count' => $this->countMediaQueries(),
            'responsive_images' => $this->analyzeResponsiveImages(),
            'flexible_layout_hints' => $this->getFlexibleLayoutHints(),
            'breakpoint_strategy' => $this->analyzeBreakpointStrategy(),
            'mobile_first_design' => $this->hasMobileFirstDesign()
        ];
    }

    private function analyzeMobileUsability(): array
    {
        return [
            'touch_target_optimization' => $this->analyzeTouchTargets(),
            'text_legibility' => $this->analyzeTextLegibility(),
            'mobile_navigation' => $this->analyzeMobileNavigation(),
            'tap_delay_optimization' => $this->hasTapDelayOptimization(),
            'mobile_friendly_forms' => $this->analyzeMobileFriendlyForms()
        ];
    }

    private function analyzeRobotsMeta(): array
    {
        $robotsMeta = $this->xpath->query('//meta[@name="robots"]/@content');
        $robotsContent = $robotsMeta->length > 0 ? strtolower($robotsMeta->item(0)->value) : '';

        return [
            'has_robots_meta' => !empty($robotsContent),
            'robots_content' => $robotsContent,
            'allows_indexing' => !str_contains($robotsContent, 'noindex'),
            'allows_following' => !str_contains($robotsContent, 'nofollow'),
            'allows_archiving' => !str_contains($robotsContent, 'noarchive'),
            'allows_snippet' => !str_contains($robotsContent, 'nosnippet'),
            'max_snippet' => $this->extractMaxSnippet($robotsContent),
            'max_image_preview' => $this->extractMaxImagePreview($robotsContent),
            'max_video_preview' => $this->extractMaxVideoPreview($robotsContent)
        ];
    }

    private function analyzeCanonicalTags(): array
    {
        $canonical = $this->xpath->query('//link[@rel="canonical"]/@href');
        $canonicalUrl = $canonical->length > 0 ? $canonical->item(0)->value : '';

        return [
            'has_canonical' => !empty($canonicalUrl),
            'canonical_url' => $canonicalUrl,
            'is_self_referencing' => $canonicalUrl === $this->baseUrl,
            'canonical_validation' => $this->validateCanonicalUrl($canonicalUrl),
            'multiple_canonicals' => $canonical->length > 1
        ];
    }

    private function validateJsonLd(): array
    {
        $jsonLdScripts = $this->xpath->query('//script[@type="application/ld+json"]');
        $validSchemas = 0;
        $invalidSchemas = 0;
        $schemas = [];
        $errors = [];

        foreach ($jsonLdScripts as $script) {
            try {
                $json = json_decode($script->textContent, true, 512, JSON_THROW_ON_ERROR);
                if (isset($json['@type'])) {
                    $schemas[] = is_array($json['@type']) ? $json['@type'] : [$json['@type']];
                    $validSchemas++;
                } else {
                    $invalidSchemas++;
                    $errors[] = 'Missing @type property';
                }
            } catch (\Exception $e) {
                $invalidSchemas++;
                $errors[] = 'Invalid JSON: ' . $e->getMessage();
            }
        }

        return [
            'total_json_ld' => $jsonLdScripts->length,
            'valid_schemas' => $validSchemas,
            'invalid_schemas' => $invalidSchemas,
            'schema_types' => array_unique(array_merge(...$schemas)),
            'validation_errors' => $errors
        ];
    }

    private function analyzeHttpsImplementation(): array
    {
        $isHttps = str_starts_with($this->baseUrl, 'https://');
        $mixedContentRisks = $this->findMixedContentRisks();

        return [
            'is_https' => $isHttps,
            'mixed_content_risks' => $mixedContentRisks,
            'http_links_count' => $this->xpath->query('//a[starts-with(@href, "http://")]')->length,
            'insecure_resources_count' => $this->countInsecureResources(),
            'ssl_optimization' => $this->analyzesSslOptimization()
        ];
    }

    private function analyzeHreflangImplementation(): array
    {
        $hreflangLinks = $this->xpath->query('//link[@rel="alternate" and @hreflang]');
        $languages = [];
        $regions = [];

        foreach ($hreflangLinks as $link) {
            $hreflang = $link->getAttribute('hreflang');
            $href = $link->getAttribute('href');

            if ($hreflang === 'x-default') {
                $hasXDefault = true;
            } else {
                if (str_contains($hreflang, '-')) {
                    [$lang, $region] = explode('-', $hreflang, 2);
                    $languages[] = $lang;
                    $regions[] = $region;
                } else {
                    $languages[] = $hreflang;
                }
            }
        }

        return [
            'has_hreflang' => $hreflangLinks->length > 0,
            'hreflang_count' => $hreflangLinks->length,
            'has_x_default' => isset($hasXDefault),
            'unique_languages' => array_unique($languages),
            'unique_regions' => array_unique($regions),
            'bidirectional_validation' => $this->validateBidirectionalHreflang(),
            'self_referencing_hreflang' => $this->hasSelfReferencingHreflang()
        ];
    }

    private function analyzeDomComplexity(): array
    {
        $allElements = $this->xpath->query('//*');
        $maxDepth = $this->calculateMaxDomDepth();
        $avgDepth = $this->calculateAvgDomDepth();

        return [
            'total_elements' => $allElements->length,
            'max_depth' => $maxDepth,
            'average_depth' => $avgDepth,
            'complexity_score' => $this->calculateDomComplexityScore($allElements->length, $maxDepth),
            'large_dom_warning' => $allElements->length > 1500,
            'deep_nesting_warning' => $maxDepth > 32
        ];
    }

    private function analyzeHeadingOptimization(): array
    {
        $headings = [];
        $hierarchy = [];

        for ($i = 1; $i <= 6; $i++) {
            $count = $this->xpath->query("//h{$i}")->length;
            $headings["h{$i}"] = $count;
            if ($count > 0) {
                $hierarchy[] = $i;
            }
        }

        return [
            'heading_distribution' => $headings,
            'hierarchy_levels_used' => $hierarchy,
            'has_h1' => $headings['h1'] > 0,
            'multiple_h1' => $headings['h1'] > 1,
            'hierarchy_issues' => $this->findHierarchyIssues($hierarchy),
            'heading_density' => $this->calculateHeadingDensity($headings),
            'empty_headings' => $this->countEmptyHeadings()
        ];
    }

    // Helper methods for complex calculations

    private function initializeDom(string $html): void
    {
        $this->dom = new DOMDocument();
        libxml_use_internal_errors(true);

        $success = $this->dom->loadHTML(
            '<?xml encoding="UTF-8">' . $html,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        if (!$success) {
            Log::warning('DOM initialization failed for technical SEO analysis');
        }

        libxml_clear_errors();
        $this->xpath = new DOMXPath($this->dom);
    }

    private function hasAboveFoldOptimization(): bool
    {
        // Look for critical CSS inlining or other above-fold optimization techniques
        $criticalCss = $this->xpath->query('//style[contains(text(), "critical") or contains(text(), "above-fold")]');
        $preloadCss = $this->xpath->query('//link[@rel="preload" and @as="style"]');

        return $criticalCss->length > 0 || $preloadCss->length > 0;
    }

    private function analyzesFontDisplayStrategy(): array
    {
        $fontFaces = [];
        $styles = $this->xpath->query('//style');

        foreach ($styles as $style) {
            if (preg_match_all('/@font-face[^}]*font-display:\s*([^;}]+)/i', $style->textContent, $matches)) {
                foreach ($matches[1] as $display) {
                    $fontFaces[] = trim($display);
                }
            }
        }

        return [
            'font_display_count' => count($fontFaces),
            'font_display_values' => array_unique($fontFaces),
            'has_font_display_swap' => in_array('swap', $fontFaces)
        ];
    }

    private function hasIntersectionObserver(): bool
    {
        $scripts = $this->xpath->query('//script');
        foreach ($scripts as $script) {
            if (str_contains($script->textContent, 'IntersectionObserver')) {
                return true;
            }
        }
        return false;
    }

    private function calculateThirdPartyImpact(array $domains): string
    {
        $highImpactDomains = [
            'googletagmanager.com', 'google-analytics.com', 'facebook.com',
            'doubleclick.net', 'googlesyndication.com'
        ];

        $highImpactCount = 0;
        foreach ($domains as $domain) {
            foreach ($highImpactDomains as $highImpact) {
                if (str_contains($domain, $highImpact)) {
                    $highImpactCount++;
                    break;
                }
            }
        }

        if ($highImpactCount > 3) return 'high';
        if ($highImpactCount > 1) return 'medium';
        return 'low';
    }

    private function calculateViewportScore(string $viewportContent): float
    {
        $score = 0;
        if (str_contains($viewportContent, 'width=device-width')) $score += 40;
        if (str_contains($viewportContent, 'initial-scale=1')) $score += 30;
        if (!str_contains($viewportContent, 'user-scalable=no')) $score += 20;
        if (!str_contains($viewportContent, 'maximum-scale')) $score += 10;

        return $score;
    }

    private function countMediaQueries(): int
    {
        $count = 0;
        $styles = $this->xpath->query('//style');

        foreach ($styles as $style) {
            $count += preg_match_all('/@media[^{]*\{/', $style->textContent);
        }

        return $count;
    }

    private function analyzeResponsiveImages(): array
    {
        $totalImages = $this->xpath->query('//img')->length;
        $responsiveImages = $this->xpath->query('//img[@srcset or @sizes]')->length;
        $pictureElements = $this->xpath->query('//picture')->length;

        return [
            'total_images' => $totalImages,
            'responsive_images' => $responsiveImages,
            'picture_elements' => $pictureElements,
            'responsive_percentage' => $totalImages > 0 ? round(($responsiveImages / $totalImages) * 100, 2) : 0
        ];
    }

    private function extractMaxSnippet(string $robotsContent): ?int
    {
        if (preg_match('/max-snippet:(\d+)/i', $robotsContent, $matches)) {
            return (int)$matches[1];
        }
        return null;
    }

    private function extractMaxImagePreview(string $robotsContent): ?string
    {
        if (preg_match('/max-image-preview:([^,\s]+)/i', $robotsContent, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private function extractMaxVideoPreview(string $robotsContent): ?int
    {
        if (preg_match('/max-video-preview:(\d+)/i', $robotsContent, $matches)) {
            return (int)$matches[1];
        }
        return null;
    }

    private function validateCanonicalUrl(string $canonicalUrl): array
    {
        $issues = [];

        if (empty($canonicalUrl)) {
            $issues[] = 'No canonical URL specified';
        } else {
            if (!filter_var($canonicalUrl, FILTER_VALIDATE_URL)) {
                $issues[] = 'Invalid URL format';
            }

            if (parse_url($canonicalUrl, PHP_URL_SCHEME) === 'http' &&
                parse_url($this->baseUrl, PHP_URL_SCHEME) === 'https') {
                $issues[] = 'Canonical points to HTTP while current page is HTTPS';
            }
        }

        return [
            'is_valid' => empty($issues),
            'issues' => $issues
        ];
    }

    private function findMixedContentRisks(): array
    {
        $risks = [];

        if (str_starts_with($this->baseUrl, 'https://')) {
            $httpImages = $this->xpath->query('//img[starts-with(@src, "http://")]')->length;
            $httpScripts = $this->xpath->query('//script[starts-with(@src, "http://")]')->length;
            $httpStylesheets = $this->xpath->query('//link[@rel="stylesheet" and starts-with(@href, "http://")]')->length;

            if ($httpImages > 0) $risks[] = "HTTP images: {$httpImages}";
            if ($httpScripts > 0) $risks[] = "HTTP scripts: {$httpScripts}";
            if ($httpStylesheets > 0) $risks[] = "HTTP stylesheets: {$httpStylesheets}";
        }

        return $risks;
    }

    private function countInsecureResources(): int
    {
        if (!str_starts_with($this->baseUrl, 'https://')) {
            return 0;
        }

        return $this->xpath->query('//img[starts-with(@src, "http://")] | //script[starts-with(@src, "http://")] | //link[starts-with(@href, "http://")]')->length;
    }

    private function calculateMaxDomDepth(): int
    {
        $maxDepth = 0;
        $elements = $this->xpath->query('//*');

        foreach ($elements as $element) {
            $depth = 0;
            $current = $element;

            while ($current->parentNode && $current->parentNode->nodeType === XML_ELEMENT_NODE) {
                $depth++;
                $current = $current->parentNode;
            }

            $maxDepth = max($maxDepth, $depth);
        }

        return $maxDepth;
    }

    private function calculateAvgDomDepth(): float
    {
        $totalDepth = 0;
        $count = 0;
        $elements = $this->xpath->query('//*');

        foreach ($elements as $element) {
            $depth = 0;
            $current = $element;

            while ($current->parentNode && $current->parentNode->nodeType === XML_ELEMENT_NODE) {
                $depth++;
                $current = $current->parentNode;
            }

            $totalDepth += $depth;
            $count++;
        }

        return $count > 0 ? round($totalDepth / $count, 2) : 0;
    }

    private function calculateDomComplexityScore(int $elementCount, int $maxDepth): string
    {
        $score = 0;

        if ($elementCount > 1500) $score += 3;
        elseif ($elementCount > 800) $score += 2;
        elseif ($elementCount > 400) $score += 1;

        if ($maxDepth > 32) $score += 3;
        elseif ($maxDepth > 20) $score += 2;
        elseif ($maxDepth > 12) $score += 1;

        if ($score >= 5) return 'very_high';
        if ($score >= 3) return 'high';
        if ($score >= 2) return 'medium';
        return 'low';
    }

    private function findHierarchyIssues(array $hierarchy): array
    {
        $issues = [];

        if (empty($hierarchy)) {
            $issues[] = 'No headings found';
            return $issues;
        }

        if ($hierarchy[0] !== 1) {
            $issues[] = 'Page does not start with H1';
        }

        for ($i = 1; $i < count($hierarchy); $i++) {
            if ($hierarchy[$i] - $hierarchy[$i-1] > 1) {
                $issues[] = "Skipped heading level from H{$hierarchy[$i-1]} to H{$hierarchy[$i]}";
            }
        }

        return $issues;
    }

    private function calculateHeadingDensity(array $headings): float
    {
        $totalHeadings = array_sum($headings);
        $textLength = strlen(strip_tags($this->html));

        return $textLength > 0 ? round(($totalHeadings / $textLength) * 1000, 2) : 0; // per 1000 chars
    }

    private function countEmptyHeadings(): int
    {
        $emptyHeadings = 0;
        for ($i = 1; $i <= 6; $i++) {
            $headings = $this->xpath->query("//h{$i}");
            foreach ($headings as $heading) {
                if (trim($heading->textContent) === '') {
                    $emptyHeadings++;
                }
            }
        }
        return $emptyHeadings;
    }

    // Placeholder methods for remaining functionality
    private function analyzeCachingStrategy(): array { return ['status' => 'analysis_pending']; }
    private function analyzeCompression(): array { return ['status' => 'analysis_pending']; }
    private function analyzeJavaScriptOptimization(): array { return ['status' => 'analysis_pending']; }
    private function analyzeCssOptimization(): array { return ['status' => 'analysis_pending']; }
    private function analyzeImageOptimization(): array { return ['status' => 'analysis_pending']; }
    private function analyzeTouchOptimization(): array { return ['status' => 'analysis_pending']; }
    private function analyzeMobilePerformance(): array { return ['status' => 'analysis_pending']; }
    private function analyzeAmpImplementation(): array { return ['status' => 'analysis_pending']; }
    private function analyzeNavigationStructure(): array { return ['status' => 'analysis_pending']; }
    private function analyzeInternalLinking(): array { return ['status' => 'analysis_pending']; }
    private function analyzeUrlParameters(): array { return ['status' => 'analysis_pending']; }
    private function analyzePagination(): array { return ['status' => 'analysis_pending']; }
    private function analyzeSitemapReferences(): array { return ['status' => 'analysis_pending']; }
    private function analyzeCrawlBudget(): array { return ['status' => 'analysis_pending']; }
    private function analyzeMetaRobots(): array { return ['status' => 'analysis_pending']; }
    private function analyzeDuplicateContent(): array { return ['status' => 'analysis_pending']; }
    private function analyzeContentQuality(): array { return ['status' => 'analysis_pending']; }
    private function analyzeThinContent(): array { return ['status' => 'analysis_pending']; }
    private function analyzeNoindexPatterns(): array { return ['status' => 'analysis_pending']; }
    private function validateMicrodata(): array { return ['status' => 'analysis_pending']; }
    private function validateRdfa(): array { return ['status' => 'analysis_pending']; }
    private function analyzeSchemaCompleteness(): array { return ['status' => 'analysis_pending']; }
    private function analyzeRichSnippetsPotential(): array { return ['status' => 'analysis_pending']; }
    private function validateDoctype(): array { return ['status' => 'analysis_pending']; }
    private function validateHtmlStructure(): array { return ['status' => 'analysis_pending']; }
    private function validateSemanticMarkup(): array { return ['status' => 'analysis_pending']; }
    private function analyzeAccessibilityCompliance(): array { return ['status' => 'analysis_pending']; }
    private function getW3cValidationHints(): array { return ['status' => 'analysis_pending']; }
    private function analyzeSecurityHeaders(): array { return ['status' => 'analysis_pending']; }
    private function analyzeMixedContent(): array { return ['status' => 'analysis_pending']; }
    private function analyzeExternalResourceSecurity(): array { return ['status' => 'analysis_pending']; }
    private function analyzeContentSecurityPolicy(): array { return ['status' => 'analysis_pending']; }
    private function analyzeLanguageDetection(): array { return ['status' => 'analysis_pending']; }
    private function analyzeGeoTargeting(): array { return ['status' => 'analysis_pending']; }
    private function analyzeCurrencyLocalization(): array { return ['status' => 'analysis_pending']; }
    private function analyzeCulturalAdaptation(): array { return ['status' => 'analysis_pending']; }
    private function analyzeLocalBusinessSchema(): array { return ['status' => 'analysis_pending']; }
    private function analyzeContactInformation(): array { return ['status' => 'analysis_pending']; }
    private function analyzeLocationSignals(): array { return ['status' => 'analysis_pending']; }
    private function analyzeOpeningHours(): array { return ['status' => 'analysis_pending']; }
    private function analyzeLocalCitations(): array { return ['status' => 'analysis_pending']; }
    private function analyzeRenderBlocking(): array { return ['status' => 'analysis_pending']; }
    private function analyzeJavaScriptErrors(): array { return ['status' => 'analysis_pending']; }
    private function analyzeCssEfficiency(): array { return ['status' => 'analysis_pending']; }
    private function analyzeMemoryUsageHints(): array { return ['status' => 'analysis_pending']; }
    private function analyzeKeywordPlacement(): array { return ['status' => 'analysis_pending']; }
    private function analyzeContentFreshness(): array { return ['status' => 'analysis_pending']; }
    private function analyzeReadability(): array { return ['status' => 'analysis_pending']; }
    private function analyzeContentDepth(): array { return ['status' => 'analysis_pending']; }
    private function analyzeSemanticRelevance(): array { return ['status' => 'analysis_pending']; }
    private function analyzeUrlReadability(string $url): array { return ['status' => 'analysis_pending']; }
    private function analyzeKeywordPresenceInUrl(string $url): array { return ['status' => 'analysis_pending']; }
    private function analyzeUrlCaseConsistency(string $url): array { return ['status' => 'analysis_pending']; }
    private function analyzeResponseTimeHints(): array { return ['status' => 'analysis_pending']; }
    private function analyzeServerPushHints(): array { return ['status' => 'analysis_pending']; }
    private function analyzeCdnUsage(): array { return ['status' => 'analysis_pending']; }
    private function analyzeCompressionHints(): array { return ['status' => 'analysis_pending']; }
    private function analyzeCachingHeaders(): array { return ['status' => 'analysis_pending']; }
    private function getMinificationHints(): array { return ['status' => 'analysis_pending']; }
    private function getConcatenationOpportunities(): array { return ['status' => 'analysis_pending']; }
    private function getFlexibleLayoutHints(): array { return ['status' => 'analysis_pending']; }
    private function analyzeBreakpointStrategy(): array { return ['status' => 'analysis_pending']; }
    private function hasMobileFirstDesign(): bool { return false; }
    private function analyzeTouchTargets(): array { return ['status' => 'analysis_pending']; }
    private function analyzeTextLegibility(): array { return ['status' => 'analysis_pending']; }
    private function analyzeMobileNavigation(): array { return ['status' => 'analysis_pending']; }
    private function hasTapDelayOptimization(): bool { return false; }
    private function analyzeMobileFriendlyForms(): array { return ['status' => 'analysis_pending']; }
    private function analyzesSslOptimization(): array { return ['status' => 'analysis_pending']; }
    private function validateBidirectionalHreflang(): bool { return false; }
    private function hasSelfReferencingHreflang(): bool { return false; }
}