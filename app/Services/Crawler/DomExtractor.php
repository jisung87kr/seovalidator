<?php

namespace App\Services\Crawler;

use DOMDocument;
use DOMXPath;
use DOMNode;
use DOMElement;
use Illuminate\Support\Facades\Log;

/**
 * DOM Element extraction utilities for specialized SEO analysis
 * Complements the HtmlParserService with advanced DOM manipulation and extraction
 */
class DomExtractor
{
    private DOMDocument $dom;
    private DOMXPath $xpath;
    private string $baseUrl;

    /**
     * Initialize DOM extractor with HTML content
     */
    public function initialize(string $html, string $url): void
    {
        $this->baseUrl = $url;
        $this->initializeDom($html);
    }

    /**
     * Extract tables with their structure and accessibility features
     */
    public function extractTables(): array
    {
        $tables = [];
        $tableNodes = $this->xpath->query('//table');

        foreach ($tableNodes as $table) {
            $tableData = [
                'has_caption' => $this->xpath->query('.//caption', $table)->length > 0,
                'has_thead' => $this->xpath->query('.//thead', $table)->length > 0,
                'has_tbody' => $this->xpath->query('.//tbody', $table)->length > 0,
                'has_tfoot' => $this->xpath->query('.//tfoot', $table)->length > 0,
                'headers_count' => $this->xpath->query('.//th', $table)->length,
                'rows_count' => $this->xpath->query('.//tr', $table)->length,
                'cells_count' => $this->xpath->query('.//td', $table)->length,
                'has_scope_attributes' => $this->xpath->query('.//th[@scope]', $table)->length > 0,
                'has_headers_attributes' => $this->xpath->query('.//*[@headers]', $table)->length > 0,
                'summary' => $table->getAttribute('summary'),
                'accessibility_score' => $this->calculateTableAccessibilityScore($table)
            ];

            $tables[] = $tableData;
        }

        return [
            'total_count' => count($tables),
            'with_accessibility_features' => count(array_filter($tables, fn($t) => $t['accessibility_score'] > 50)),
            'tables' => $tables
        ];
    }

    /**
     * Extract forms with their structure and accessibility
     */
    public function extractForms(): array
    {
        $forms = [];
        $formNodes = $this->xpath->query('//form');

        foreach ($formNodes as $form) {
            $inputs = $this->xpath->query('.//input | .//textarea | .//select', $form);
            $labels = $this->xpath->query('.//label', $form);
            $fieldsets = $this->xpath->query('.//fieldset', $form);

            $formData = [
                'action' => $form->getAttribute('action'),
                'method' => $form->getAttribute('method') ?: 'GET',
                'has_autocomplete' => $form->hasAttribute('autocomplete'),
                'inputs_count' => $inputs->length,
                'labels_count' => $labels->length,
                'fieldsets_count' => $fieldsets->length,
                'input_types' => $this->getFormInputTypes($inputs),
                'required_fields_count' => $this->xpath->query('.//*[@required]', $form)->length,
                'accessibility_features' => $this->getFormAccessibilityFeatures($form),
                'validation_attributes' => $this->getFormValidationAttributes($form)
            ];

            $forms[] = $formData;
        }

        return [
            'total_count' => count($forms),
            'forms' => $forms
        ];
    }

    /**
     * Extract multimedia elements (audio, video, iframe)
     */
    public function extractMultimedia(): array
    {
        return [
            'audio' => $this->extractAudioElements(),
            'video' => $this->extractVideoElements(),
            'iframes' => $this->extractIframes(),
            'embeds' => $this->extractEmbeds()
        ];
    }

    /**
     * Extract navigation elements and structure
     */
    public function extractNavigation(): array
    {
        return [
            'nav_elements' => $this->extractNavElements(),
            'breadcrumbs' => $this->extractBreadcrumbs(),
            'skip_links' => $this->extractSkipLinks(),
            'anchor_links' => $this->extractAnchorLinks()
        ];
    }

    /**
     * Extract accessibility features
     */
    public function extractAccessibilityFeatures(): array
    {
        return [
            'aria_labels' => $this->countAriaLabels(),
            'aria_describedby' => $this->countAriaDescribedby(),
            'landmarks' => $this->extractLandmarks(),
            'heading_structure' => $this->analyzeHeadingStructure(),
            'focus_management' => $this->analyzeFocusManagement(),
            'color_contrast_hints' => $this->getColorContrastHints(),
            'keyboard_navigation' => $this->analyzeKeyboardNavigation()
        ];
    }

    /**
     * Extract performance-related elements
     */
    public function extractPerformanceElements(): array
    {
        return [
            'lazy_loading' => $this->extractLazyLoadingElements(),
            'critical_resources' => $this->extractCriticalResources(),
            'preload_hints' => $this->extractPreloadHints(),
            'resource_hints' => $this->extractResourceHints(),
            'web_fonts' => $this->extractWebFonts(),
            'third_party_scripts' => $this->extractThirdPartyScripts()
        ];
    }

    /**
     * Extract security-related elements
     */
    public function extractSecurityElements(): array
    {
        return [
            'csp_headers' => $this->extractContentSecurityPolicy(),
            'integrity_attributes' => $this->extractIntegrityAttributes(),
            'external_links_security' => $this->analyzeExternalLinksSecurity(),
            'form_security' => $this->analyzeFormSecurity(),
            'iframe_security' => $this->analyzeIframeSecurity()
        ];
    }

    /**
     * Extract semantic HTML5 elements usage
     */
    public function extractSemanticElements(): array
    {
        $semanticElements = [
            'article', 'section', 'aside', 'header', 'footer', 'main',
            'nav', 'figure', 'figcaption', 'time', 'mark', 'details', 'summary'
        ];

        $usage = [];
        foreach ($semanticElements as $element) {
            $count = $this->xpath->query("//{$element}")->length;
            $usage[$element] = $count;
        }

        return [
            'semantic_elements_usage' => $usage,
            'semantic_score' => $this->calculateSemanticScore($usage),
            'total_semantic_elements' => array_sum($usage)
        ];
    }

    /**
     * Extract custom data attributes and microformats
     */
    public function extractCustomData(): array
    {
        return [
            'data_attributes' => $this->extractDataAttributes(),
            'microformats' => $this->extractMicroformats(),
            'custom_elements' => $this->extractCustomElements(),
            'web_components' => $this->extractWebComponents()
        ];
    }

    /**
     * Initialize DOM document with error handling
     */
    private function initializeDom(string $html): void
    {
        $this->dom = new DOMDocument();

        // Enable user error handling
        libxml_use_internal_errors(true);

        // Load HTML with proper encoding
        $success = $this->dom->loadHTML(
            '<?xml encoding="UTF-8">' . $html,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOCDATA
        );

        if (!$success) {
            $errors = libxml_get_errors();
            Log::warning('DOM initialization had errors', ['errors' => $errors]);
        }

        libxml_clear_errors();
        $this->xpath = new DOMXPath($this->dom);
    }

    /**
     * Calculate table accessibility score
     */
    private function calculateTableAccessibilityScore(DOMElement $table): float
    {
        $score = 0;
        $maxScore = 100;

        // Has caption
        if ($this->xpath->query('.//caption', $table)->length > 0) $score += 20;

        // Has proper header structure
        if ($this->xpath->query('.//thead', $table)->length > 0) $score += 15;

        // Has scope attributes
        if ($this->xpath->query('.//th[@scope]', $table)->length > 0) $score += 25;

        // Has headers attributes
        if ($this->xpath->query('.//*[@headers]', $table)->length > 0) $score += 20;

        // Has summary
        if (!empty($table->getAttribute('summary'))) $score += 20;

        return ($score / $maxScore) * 100;
    }

    /**
     * Get form input types
     */
    private function getFormInputTypes(\DOMNodeList $inputs): array
    {
        $types = [];
        foreach ($inputs as $input) {
            $type = $input->getAttribute('type') ?: 'text';
            $types[$type] = ($types[$type] ?? 0) + 1;
        }
        return $types;
    }

    /**
     * Get form accessibility features
     */
    private function getFormAccessibilityFeatures(DOMElement $form): array
    {
        return [
            'labels_properly_associated' => $this->checkLabelAssociation($form),
            'has_fieldsets_with_legends' => $this->xpath->query('.//fieldset/legend', $form)->length > 0,
            'required_fields_marked' => $this->xpath->query('.//*[@required]', $form)->length > 0,
            'error_messages_associated' => $this->xpath->query('.//*[@aria-describedby]', $form)->length > 0,
            'autocomplete_attributes' => $this->xpath->query('.//*[@autocomplete]', $form)->length
        ];
    }

    /**
     * Get form validation attributes
     */
    private function getFormValidationAttributes(DOMElement $form): array
    {
        return [
            'pattern_attributes' => $this->xpath->query('.//*[@pattern]', $form)->length,
            'min_max_attributes' => $this->xpath->query('.//*[@min or @max]', $form)->length,
            'minlength_maxlength' => $this->xpath->query('.//*[@minlength or @maxlength]', $form)->length,
            'step_attributes' => $this->xpath->query('.//*[@step]', $form)->length
        ];
    }

    /**
     * Extract audio elements
     */
    private function extractAudioElements(): array
    {
        $audioNodes = $this->xpath->query('//audio');
        $audios = [];

        foreach ($audioNodes as $audio) {
            $audios[] = [
                'src' => $audio->getAttribute('src'),
                'controls' => $audio->hasAttribute('controls'),
                'autoplay' => $audio->hasAttribute('autoplay'),
                'loop' => $audio->hasAttribute('loop'),
                'muted' => $audio->hasAttribute('muted'),
                'sources_count' => $this->xpath->query('.//source', $audio)->length,
                'has_transcript' => $this->checkForTranscript($audio)
            ];
        }

        return [
            'total_count' => count($audios),
            'with_controls' => count(array_filter($audios, fn($a) => $a['controls'])),
            'audios' => $audios
        ];
    }

    /**
     * Extract video elements
     */
    private function extractVideoElements(): array
    {
        $videoNodes = $this->xpath->query('//video');
        $videos = [];

        foreach ($videoNodes as $video) {
            $videos[] = [
                'src' => $video->getAttribute('src'),
                'poster' => $video->getAttribute('poster'),
                'controls' => $video->hasAttribute('controls'),
                'autoplay' => $video->hasAttribute('autoplay'),
                'loop' => $video->hasAttribute('loop'),
                'muted' => $video->hasAttribute('muted'),
                'width' => $video->getAttribute('width'),
                'height' => $video->getAttribute('height'),
                'sources_count' => $this->xpath->query('.//source', $video)->length,
                'tracks_count' => $this->xpath->query('.//track', $video)->length,
                'has_captions' => $this->xpath->query('.//track[@kind="captions"]', $video)->length > 0
            ];
        }

        return [
            'total_count' => count($videos),
            'with_controls' => count(array_filter($videos, fn($v) => $v['controls'])),
            'with_captions' => count(array_filter($videos, fn($v) => $v['has_captions'])),
            'videos' => $videos
        ];
    }

    /**
     * Extract iframe elements
     */
    private function extractIframes(): array
    {
        $iframeNodes = $this->xpath->query('//iframe');
        $iframes = [];

        foreach ($iframeNodes as $iframe) {
            $iframes[] = [
                'src' => $iframe->getAttribute('src'),
                'title' => $iframe->getAttribute('title'),
                'sandbox' => $iframe->getAttribute('sandbox'),
                'loading' => $iframe->getAttribute('loading'),
                'width' => $iframe->getAttribute('width'),
                'height' => $iframe->getAttribute('height'),
                'has_title' => !empty($iframe->getAttribute('title')),
                'is_sandboxed' => $iframe->hasAttribute('sandbox')
            ];
        }

        return [
            'total_count' => count($iframes),
            'with_title' => count(array_filter($iframes, fn($i) => $i['has_title'])),
            'sandboxed' => count(array_filter($iframes, fn($i) => $i['is_sandboxed'])),
            'iframes' => $iframes
        ];
    }

    /**
     * Extract embed elements
     */
    private function extractEmbeds(): array
    {
        $embedNodes = $this->xpath->query('//embed | //object');
        $embeds = [];

        foreach ($embedNodes as $embed) {
            $embeds[] = [
                'tag_name' => $embed->tagName,
                'src' => $embed->getAttribute('src') ?: $embed->getAttribute('data'),
                'type' => $embed->getAttribute('type'),
                'width' => $embed->getAttribute('width'),
                'height' => $embed->getAttribute('height')
            ];
        }

        return [
            'total_count' => count($embeds),
            'embeds' => $embeds
        ];
    }

    /**
     * Extract navigation elements
     */
    private function extractNavElements(): array
    {
        $navNodes = $this->xpath->query('//nav');
        $navs = [];

        foreach ($navNodes as $nav) {
            $navs[] = [
                'aria_label' => $nav->getAttribute('aria-label'),
                'aria_labelledby' => $nav->getAttribute('aria-labelledby'),
                'role' => $nav->getAttribute('role'),
                'links_count' => $this->xpath->query('.//a', $nav)->length,
                'lists_count' => $this->xpath->query('.//ul | .//ol', $nav)->length
            ];
        }

        return [
            'total_count' => count($navs),
            'properly_labeled' => count(array_filter($navs, fn($n) => !empty($n['aria_label']) || !empty($n['aria_labelledby']))),
            'navigations' => $navs
        ];
    }

    /**
     * Extract breadcrumb navigation
     */
    private function extractBreadcrumbs(): array
    {
        // Look for common breadcrumb patterns
        $breadcrumbSelectors = [
            '//*[@itemtype="http://schema.org/BreadcrumbList"]',
            '//*[contains(@class, "breadcrumb")]',
            '//nav[contains(@aria-label, "breadcrumb") or contains(@aria-label, "Breadcrumb")]'
        ];

        $breadcrumbs = [];
        foreach ($breadcrumbSelectors as $selector) {
            $nodes = $this->xpath->query($selector);
            foreach ($nodes as $node) {
                $breadcrumbs[] = [
                    'type' => $this->getBreadcrumbType($node),
                    'items_count' => $this->xpath->query('.//a | .//*[@itemprop="name"]', $node)->length,
                    'has_schema' => !empty($node->getAttribute('itemtype')),
                    'has_aria_label' => !empty($node->getAttribute('aria-label'))
                ];
            }
        }

        return [
            'total_count' => count($breadcrumbs),
            'with_schema' => count(array_filter($breadcrumbs, fn($b) => $b['has_schema'])),
            'breadcrumbs' => $breadcrumbs
        ];
    }

    /**
     * Extract skip links
     */
    private function extractSkipLinks(): array
    {
        $skipLinks = $this->xpath->query('//a[contains(@href, "#") and (contains(text(), "skip") or contains(text(), "Skip"))]');
        $links = [];

        foreach ($skipLinks as $link) {
            $links[] = [
                'text' => trim($link->textContent),
                'href' => $link->getAttribute('href'),
                'class' => $link->getAttribute('class')
            ];
        }

        return [
            'total_count' => count($links),
            'skip_links' => $links
        ];
    }

    /**
     * Extract anchor links (internal page navigation)
     */
    private function extractAnchorLinks(): array
    {
        $anchorLinks = $this->xpath->query('//a[starts-with(@href, "#")]');
        $links = [];

        foreach ($anchorLinks as $link) {
            $href = $link->getAttribute('href');
            $targetExists = !empty($href) && $href !== '#' ?
                $this->xpath->query("//*[@id='" . substr($href, 1) . "']")->length > 0 : false;

            $links[] = [
                'href' => $href,
                'text' => trim($link->textContent),
                'target_exists' => $targetExists
            ];
        }

        return [
            'total_count' => count($links),
            'with_valid_targets' => count(array_filter($links, fn($l) => $l['target_exists'])),
            'anchor_links' => $links
        ];
    }

    /**
     * Count ARIA labels
     */
    private function countAriaLabels(): array
    {
        return [
            'aria_label' => $this->xpath->query('//*[@aria-label]')->length,
            'aria_labelledby' => $this->xpath->query('//*[@aria-labelledby]')->length,
            'aria_describedby' => $this->xpath->query('//*[@aria-describedby]')->length
        ];
    }

    /**
     * Count ARIA describedby
     */
    private function countAriaDescribedby(): int
    {
        return $this->xpath->query('//*[@aria-describedby]')->length;
    }

    /**
     * Extract landmark roles
     */
    private function extractLandmarks(): array
    {
        $landmarks = [
            'main' => $this->xpath->query('//main | //*[@role="main"]')->length,
            'navigation' => $this->xpath->query('//nav | //*[@role="navigation"]')->length,
            'banner' => $this->xpath->query('//header | //*[@role="banner"]')->length,
            'contentinfo' => $this->xpath->query('//footer | //*[@role="contentinfo"]')->length,
            'complementary' => $this->xpath->query('//aside | //*[@role="complementary"]')->length,
            'search' => $this->xpath->query('//*[@role="search"]')->length,
            'form' => $this->xpath->query('//*[@role="form"]')->length
        ];

        return [
            'landmarks' => $landmarks,
            'total_landmarks' => array_sum($landmarks)
        ];
    }

    /**
     * Analyze heading structure for accessibility
     */
    private function analyzeHeadingStructure(): array
    {
        $headings = [];
        for ($i = 1; $i <= 6; $i++) {
            $headings["h{$i}"] = $this->xpath->query("//h{$i}")->length;
        }

        return [
            'heading_counts' => $headings,
            'has_h1' => $headings['h1'] > 0,
            'multiple_h1' => $headings['h1'] > 1,
            'proper_hierarchy' => $this->checkHeadingHierarchy($headings),
            'skipped_levels' => $this->findSkippedHeadingLevelsInDocument()
        ];
    }

    /**
     * Analyze focus management
     */
    private function analyzeFocusManagement(): array
    {
        return [
            'tabindex_positive' => $this->xpath->query('//*[@tabindex and @tabindex > 0]')->length,
            'tabindex_zero' => $this->xpath->query('//*[@tabindex="0"]')->length,
            'tabindex_negative' => $this->xpath->query('//*[@tabindex="-1"]')->length,
            'focusable_elements' => $this->countFocusableElements()
        ];
    }

    /**
     * Get color contrast hints from inline styles
     */
    private function getColorContrastHints(): array
    {
        $inlineStyles = $this->xpath->query('//*[@style]');
        $colorProperties = 0;
        $backgroundProperties = 0;

        foreach ($inlineStyles as $element) {
            $style = $element->getAttribute('style');
            if (preg_match('/color\s*:/i', $style)) $colorProperties++;
            if (preg_match('/background(-color)?\s*:/i', $style)) $backgroundProperties++;
        }

        return [
            'elements_with_inline_color' => $colorProperties,
            'elements_with_inline_background' => $backgroundProperties,
            'potential_contrast_issues' => max($colorProperties, $backgroundProperties)
        ];
    }

    /**
     * Analyze keyboard navigation support
     */
    private function analyzeKeyboardNavigation(): array
    {
        return [
            'skip_links_count' => $this->xpath->query('//a[contains(@href, "#") and (contains(@class, "skip") or contains(text(), "skip") or contains(text(), "Skip"))]')->length,
            'access_keys_count' => $this->xpath->query('//*[@accesskey]')->length,
            'custom_tabindex_count' => $this->xpath->query('//*[@tabindex]')->length
        ];
    }

    // Additional helper methods would continue here...
    // For brevity, I'll implement key methods and leave placeholders for others

    private function extractLazyLoadingElements(): array
    {
        return [
            'img_lazy' => $this->xpath->query('//img[@loading="lazy"]')->length,
            'iframe_lazy' => $this->xpath->query('//iframe[@loading="lazy"]')->length
        ];
    }

    private function extractCriticalResources(): array
    {
        return [
            'critical_css' => $this->xpath->query('//link[@rel="stylesheet" and @media="print" and @onload]')->length,
            'critical_js' => $this->xpath->query('//script[@async or @defer]')->length
        ];
    }

    private function extractPreloadHints(): array
    {
        return [
            'preload' => $this->xpath->query('//link[@rel="preload"]')->length,
            'prefetch' => $this->xpath->query('//link[@rel="prefetch"]')->length,
            'preconnect' => $this->xpath->query('//link[@rel="preconnect"]')->length,
            'dns_prefetch' => $this->xpath->query('//link[@rel="dns-prefetch"]')->length
        ];
    }

    private function extractResourceHints(): array
    {
        $hints = [];
        $hintTypes = ['preload', 'prefetch', 'preconnect', 'dns-prefetch', 'modulepreload'];

        foreach ($hintTypes as $type) {
            $nodes = $this->xpath->query("//link[@rel='{$type}']");
            $hints[$type] = [];

            foreach ($nodes as $node) {
                $hints[$type][] = [
                    'href' => $node->getAttribute('href'),
                    'as' => $node->getAttribute('as'),
                    'type' => $node->getAttribute('type'),
                    'crossorigin' => $node->getAttribute('crossorigin')
                ];
            }
        }

        return $hints;
    }

    private function extractWebFonts(): array
    {
        $webFonts = $this->xpath->query('//link[@rel="stylesheet" and contains(@href, "fonts")]');
        $fonts = [];

        foreach ($webFonts as $font) {
            $fonts[] = [
                'href' => $font->getAttribute('href'),
                'is_google_fonts' => strpos($font->getAttribute('href'), 'fonts.googleapis.com') !== false,
                'display' => $font->getAttribute('display')
            ];
        }

        return [
            'total_count' => count($fonts),
            'google_fonts_count' => count(array_filter($fonts, fn($f) => $f['is_google_fonts'])),
            'fonts' => $fonts
        ];
    }

    private function extractThirdPartyScripts(): array
    {
        $scripts = $this->xpath->query('//script[@src]');
        $thirdParty = [];
        $baseDomain = parse_url($this->baseUrl, PHP_URL_HOST);

        foreach ($scripts as $script) {
            $src = $script->getAttribute('src');
            $domain = parse_url($src, PHP_URL_HOST);

            if ($domain && $domain !== $baseDomain) {
                $thirdParty[] = [
                    'src' => $src,
                    'domain' => $domain,
                    'async' => $script->hasAttribute('async'),
                    'defer' => $script->hasAttribute('defer'),
                    'integrity' => $script->getAttribute('integrity'),
                    'crossorigin' => $script->getAttribute('crossorigin')
                ];
            }
        }

        $uniqueDomains = array_unique(array_column($thirdParty, 'domain'));

        return [
            'total_count' => count($thirdParty),
            'unique_domains' => count($uniqueDomains),
            'domains' => array_values($uniqueDomains),
            'with_integrity' => count(array_filter($thirdParty, fn($s) => !empty($s['integrity']))),
            'scripts' => $thirdParty
        ];
    }

    // Security analysis methods
    private function extractContentSecurityPolicy(): array
    {
        $cspMeta = $this->xpath->query('//meta[@http-equiv="Content-Security-Policy"]/@content');
        return [
            'has_csp_meta' => $cspMeta->length > 0,
            'csp_content' => $cspMeta->length > 0 ? $cspMeta->item(0)->value : null
        ];
    }

    private function extractIntegrityAttributes(): array
    {
        return [
            'script_integrity' => $this->xpath->query('//script[@integrity]')->length,
            'link_integrity' => $this->xpath->query('//link[@integrity]')->length
        ];
    }

    private function analyzeExternalLinksSecurity(): array
    {
        $externalLinks = $this->xpath->query('//a[@href and @target="_blank"]');
        $withoutNoopener = 0;
        $withoutNoreferrer = 0;

        foreach ($externalLinks as $link) {
            $rel = strtolower($link->getAttribute('rel'));
            if (!str_contains($rel, 'noopener')) $withoutNoopener++;
            if (!str_contains($rel, 'noreferrer')) $withoutNoreferrer++;
        }

        return [
            'external_blank_links' => $externalLinks->length,
            'without_noopener' => $withoutNoopener,
            'without_noreferrer' => $withoutNoreferrer
        ];
    }

    private function analyzeFormSecurity(): array
    {
        return [
            'forms_without_csrf' => $this->xpath->query('//form[not(.//input[@name="_token" or @name="csrf_token"])]')->length,
            'forms_with_autocomplete_off' => $this->xpath->query('//form[@autocomplete="off"]')->length,
            'password_fields' => $this->xpath->query('//input[@type="password"]')->length
        ];
    }

    private function analyzeIframeSecurity(): array
    {
        return [
            'iframes_without_sandbox' => $this->xpath->query('//iframe[not(@sandbox)]')->length,
            'iframes_with_javascript' => $this->xpath->query('//iframe[not(contains(@sandbox, "allow-scripts"))]')->length
        ];
    }

    private function calculateSemanticScore(array $usage): float
    {
        $semanticElements = ['article', 'section', 'aside', 'header', 'footer', 'main', 'nav'];
        $coreSemanticCount = 0;

        foreach ($semanticElements as $element) {
            if ($usage[$element] > 0) $coreSemanticCount++;
        }

        return ($coreSemanticCount / count($semanticElements)) * 100;
    }

    private function extractDataAttributes(): array
    {
        $dataAttrs = $this->xpath->query('//*[attribute::*[starts-with(name(), "data-")]]');
        return [
            'elements_with_data_attrs' => $dataAttrs->length,
            'total_data_attributes' => $this->countDataAttributes()
        ];
    }

    private function extractMicroformats(): array
    {
        return [
            'hcard' => $this->xpath->query('//*[contains(@class, "vcard") or contains(@class, "h-card")]')->length,
            'hcalendar' => $this->xpath->query('//*[contains(@class, "vevent") or contains(@class, "h-event")]')->length,
            'hreview' => $this->xpath->query('//*[contains(@class, "hreview") or contains(@class, "h-review")]')->length
        ];
    }

    private function extractCustomElements(): array
    {
        // Look for elements with hyphens (custom elements pattern)
        $customElements = [];
        $allElements = $this->xpath->query('//*[contains(name(), "-")]');

        foreach ($allElements as $element) {
            $tagName = $element->tagName;
            $customElements[$tagName] = ($customElements[$tagName] ?? 0) + 1;
        }

        return [
            'unique_custom_elements' => count($customElements),
            'custom_elements' => $customElements
        ];
    }

    private function extractWebComponents(): array
    {
        return [
            'template_elements' => $this->xpath->query('//template')->length,
            'slot_elements' => $this->xpath->query('//*[name()="slot"]')->length,
            'shadow_dom_usage' => $this->xpath->query('//*[@slot]')->length
        ];
    }

    // Helper methods
    private function checkLabelAssociation(DOMElement $form): int
    {
        $inputs = $this->xpath->query('.//input | .//textarea | .//select', $form);
        $properlyLabeled = 0;

        foreach ($inputs as $input) {
            $id = $input->getAttribute('id');
            $hasLabel = false;

            if ($id) {
                $labels = $this->xpath->query("//label[@for='{$id}']");
                if ($labels->length > 0) $hasLabel = true;
            }

            // Check if wrapped in label
            if (!$hasLabel) {
                $parentLabel = $this->xpath->query('./ancestor::label', $input);
                if ($parentLabel->length > 0) $hasLabel = true;
            }

            if ($hasLabel) $properlyLabeled++;
        }

        return $properlyLabeled;
    }

    private function checkForTranscript(DOMElement $audio): bool
    {
        // Look for transcript links or elements near the audio
        $parent = $audio->parentNode;
        if ($parent) {
            $transcript = $this->xpath->query('.//a[contains(text(), "transcript") or contains(text(), "Transcript")]', $parent);
            return $transcript->length > 0;
        }
        return false;
    }

    private function getBreadcrumbType(DOMElement $node): string
    {
        if ($node->getAttribute('itemtype') === 'http://schema.org/BreadcrumbList') {
            return 'schema';
        }
        if (str_contains(strtolower($node->getAttribute('class')), 'breadcrumb')) {
            return 'css_class';
        }
        if (str_contains(strtolower($node->getAttribute('aria-label')), 'breadcrumb')) {
            return 'aria_label';
        }
        return 'unknown';
    }

    private function checkHeadingHierarchy(array $headings): bool
    {
        // Simple check: if we have headings, they should start with h1
        $hasContent = array_sum($headings) > 0;
        return !$hasContent || $headings['h1'] > 0;
    }

    private function findSkippedHeadingLevels(array $headings): array
    {
        $skipped = [];
        $lastLevel = 0;

        for ($i = 1; $i <= 6; $i++) {
            if ($headings["h{$i}"] > 0) {
                if ($lastLevel > 0 && $i > $lastLevel + 1) {
                    for ($j = $lastLevel + 1; $j < $i; $j++) {
                        $skipped[] = $j;
                    }
                }
                $lastLevel = $i;
            }
        }

        return $skipped;
    }

    private function findSkippedHeadingLevelsInDocument(): array
    {
        $skipped = [];
        $lastLevel = 0;

        // Get all headings in document order
        $allHeadings = $this->xpath->query('//h1 | //h2 | //h3 | //h4 | //h5 | //h6');

        foreach ($allHeadings as $heading) {
            $currentLevel = (int) substr($heading->tagName, 1); // Extract number from h1, h2, etc.

            if ($lastLevel > 0 && $currentLevel > $lastLevel + 1) {
                for ($j = $lastLevel + 1; $j < $currentLevel; $j++) {
                    if (!in_array($j, $skipped)) {
                        $skipped[] = $j;
                    }
                }
            }
            $lastLevel = $currentLevel;
        }

        return $skipped;
    }

    private function countFocusableElements(): int
    {
        $focusableSelector = '//a[@href] | //input[not(@disabled)] | //select[not(@disabled)] | //textarea[not(@disabled)] | //button[not(@disabled)] | //*[@tabindex]';
        return $this->xpath->query($focusableSelector)->length;
    }

    private function countDataAttributes(): int
    {
        $count = 0;
        $elements = $this->xpath->query('//*');

        foreach ($elements as $element) {
            foreach ($element->attributes as $attr) {
                if (strpos($attr->name, 'data-') === 0) {
                    $count++;
                }
            }
        }

        return $count;
    }
}