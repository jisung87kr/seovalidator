<?php

namespace App\Services\Parser;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Log;

/**
 * Performance optimizations for large HTML document processing
 * Provides memory-efficient parsing and selective extraction for improved performance
 */
class HtmlPerformanceOptimizer
{
    private const MEMORY_THRESHOLD = 128 * 1024 * 1024; // 128MB
    private const LARGE_HTML_THRESHOLD = 1024 * 1024; // 1MB
    private const DOM_ELEMENT_THRESHOLD = 5000; // elements
    private const PROCESSING_TIMEOUT = 30; // seconds

    private bool $useStreamingParser = false;
    private array $performanceMetrics = [];

    /**
     * Determine if HTML content requires performance optimization
     */
    public function shouldOptimize(string $html): array
    {
        $htmlSize = strlen($html);
        $memoryUsage = memory_get_usage(true);

        $optimization = [
            'html_size' => $htmlSize,
            'memory_usage' => $memoryUsage,
            'should_optimize' => false,
            'optimization_reasons' => [],
            'recommended_strategy' => 'standard'
        ];

        // Check HTML size
        if ($htmlSize > self::LARGE_HTML_THRESHOLD) {
            $optimization['should_optimize'] = true;
            $optimization['optimization_reasons'][] = 'Large HTML size (' . $this->formatBytes($htmlSize) . ')';
        }

        // Check memory usage
        if ($memoryUsage > self::MEMORY_THRESHOLD) {
            $optimization['should_optimize'] = true;
            $optimization['optimization_reasons'][] = 'High memory usage (' . $this->formatBytes($memoryUsage) . ')';
        }

        // Estimate DOM complexity
        $estimatedElements = $this->estimateDomElementCount($html);
        if ($estimatedElements > self::DOM_ELEMENT_THRESHOLD) {
            $optimization['should_optimize'] = true;
            $optimization['optimization_reasons'][] = 'Complex DOM structure (~' . $estimatedElements . ' elements)';
        }

        // Recommend strategy
        if ($optimization['should_optimize']) {
            if ($htmlSize > 5 * 1024 * 1024 || $estimatedElements > 10000) {
                $optimization['recommended_strategy'] = 'streaming';
            } else {
                $optimization['recommended_strategy'] = 'chunked';
            }
        }

        return $optimization;
    }

    /**
     * Process HTML with performance optimizations
     */
    public function optimizedParse(string $html, string $url, array $extractionTargets = []): array
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        Log::debug('Starting optimized HTML parsing', [
            'url' => $url,
            'html_size' => strlen($html),
            'memory_usage' => $startMemory,
            'extraction_targets' => $extractionTargets
        ]);

        try {
            set_time_limit(self::PROCESSING_TIMEOUT + 10);

            $optimizationInfo = $this->shouldOptimize($html);

            if ($optimizationInfo['should_optimize']) {
                $result = $this->parseWithOptimization($html, $url, $optimizationInfo['recommended_strategy'], $extractionTargets);
            } else {
                $result = $this->parseStandard($html, $url, $extractionTargets);
            }

            $this->recordPerformanceMetrics($startTime, $startMemory, $optimizationInfo);

            $result['performance_metrics'] = $this->performanceMetrics;

            return $result;

        } catch (\Exception $e) {
            Log::error('Optimized HTML parsing failed', [
                'url' => $url,
                'error' => $e->getMessage(),
                'memory_usage' => memory_get_usage(true)
            ]);
            throw $e;
        }
    }

    /**
     * Parse HTML with optimization strategy
     */
    private function parseWithOptimization(string $html, string $url, string $strategy, array $targets): array
    {
        switch ($strategy) {
            case 'streaming':
                return $this->parseWithStreamingStrategy($html, $url, $targets);
            case 'chunked':
                return $this->parseWithChunkedStrategy($html, $url, $targets);
            default:
                return $this->parseStandard($html, $url, $targets);
        }
    }

    /**
     * Standard parsing for smaller documents
     */
    private function parseStandard(string $html, string $url, array $targets): array
    {
        $dom = new DOMDocument();

        // Memory optimization settings
        libxml_use_internal_errors(true);

        $success = $dom->loadHTML(
            '<?xml encoding="UTF-8">' . $html,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_COMPACT | LIBXML_PARSEHUGE
        );

        if (!$success) {
            throw new \Exception('Failed to parse HTML with standard strategy');
        }

        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        return $this->extractTargetedData($xpath, $url, $targets);
    }

    /**
     * Streaming-based parsing for very large documents
     */
    private function parseWithStreamingStrategy(string $html, string $url, array $targets): array
    {
        // For very large documents, use streaming approach
        $results = [];

        // Pre-extract critical data using regex before DOM parsing
        $results = array_merge($results, $this->regexPreExtraction($html, $url));

        // Parse document in chunks
        $chunks = $this->chunkHtml($html);

        foreach ($chunks as $chunkIndex => $chunk) {
            try {
                $chunkResults = $this->parseHtmlChunk($chunk, $url, $targets, $chunkIndex);
                $results = $this->mergeChunkResults($results, $chunkResults);

                // Force garbage collection after each chunk
                if ($chunkIndex % 5 === 0) {
                    gc_collect_cycles();
                }

            } catch (\Exception $e) {
                Log::warning('Failed to parse HTML chunk', [
                    'chunk_index' => $chunkIndex,
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }

        return $results;
    }

    /**
     * Chunked parsing strategy for moderately large documents
     */
    private function parseWithChunkedStrategy(string $html, string $url, array $targets): array
    {
        // Parse full document but process elements in batches
        $dom = new DOMDocument();

        libxml_use_internal_errors(true);

        // Use memory-optimized loading options
        $success = $dom->loadHTML(
            '<?xml encoding="UTF-8">' . $html,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_COMPACT | LIBXML_PARSEHUGE
        );

        if (!$success) {
            throw new \Exception('Failed to parse HTML with chunked strategy');
        }

        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        // Process extraction in batches to manage memory
        return $this->batchedExtraction($xpath, $url, $targets);
    }

    /**
     * Pre-extract data using regex before DOM parsing (for performance)
     */
    private function regexPreExtraction(string $html, string $url): array
    {
        $results = [];

        // Extract title
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
            $results['title'] = trim(strip_tags($matches[1]));
        }

        // Extract meta description
        if (preg_match('/<meta\s+name=["\']description["\']\s+content=["\']([^"\']*)["\'][^>]*>/i', $html, $matches)) {
            $results['description'] = trim($matches[1]);
        }

        // Extract canonical
        if (preg_match('/<link\s+rel=["\']canonical["\']\s+href=["\']([^"\']*)["\'][^>]*>/i', $html, $matches)) {
            $results['canonical'] = trim($matches[1]);
        }

        // Count headings using regex (faster than DOM)
        $results['heading_counts'] = [];
        for ($i = 1; $i <= 6; $i++) {
            $count = preg_match_all("/<h{$i}[^>]*>.*?<\/h{$i}>/is", $html);
            $results['heading_counts']["h{$i}"] = $count;
        }

        // Count images
        $results['image_count'] = preg_match_all('/<img[^>]*>/i', $html);

        // Count links
        $results['link_count'] = preg_match_all('/<a\s[^>]*href[^>]*>/i', $html);

        return $results;
    }

    /**
     * Chunk HTML content for streaming processing
     */
    private function chunkHtml(string $html, int $chunkSize = 512000): array // 512KB chunks
    {
        $chunks = [];
        $htmlLength = strlen($html);

        for ($i = 0; $i < $htmlLength; $i += $chunkSize) {
            $chunk = substr($html, $i, $chunkSize);

            // Ensure we don't break in the middle of a tag
            if ($i + $chunkSize < $htmlLength) {
                $lastTag = strrpos($chunk, '>');
                if ($lastTag !== false) {
                    $chunk = substr($chunk, 0, $lastTag + 1);
                    $i = $i + $lastTag + 1 - $chunkSize; // Adjust next position
                }
            }

            $chunks[] = $chunk;
        }

        return $chunks;
    }

    /**
     * Parse individual HTML chunk
     */
    private function parseHtmlChunk(string $chunk, string $url, array $targets, int $chunkIndex): array
    {
        // Add basic HTML structure to make chunk parseable
        $wrappedChunk = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' .
                       $chunk .
                       '</body></html>';

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);

        $success = $dom->loadHTML(
            '<?xml encoding="UTF-8">' . $wrappedChunk,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_COMPACT
        );

        if (!$success) {
            return [];
        }

        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        return $this->extractTargetedData($xpath, $url, $targets, $chunkIndex);
    }

    /**
     * Merge results from multiple chunks
     */
    private function mergeChunkResults(array $existing, array $newResults): array
    {
        foreach ($newResults as $key => $value) {
            if (is_array($value) && isset($existing[$key]) && is_array($existing[$key])) {
                if (array_key_exists(0, $value)) {
                    // Numeric array - merge
                    $existing[$key] = array_merge($existing[$key], $value);
                } else {
                    // Associative array - merge recursively
                    $existing[$key] = array_merge_recursive($existing[$key], $value);
                }
            } elseif (is_numeric($value) && isset($existing[$key]) && is_numeric($existing[$key])) {
                // Sum numeric values
                $existing[$key] += $value;
            } else {
                $existing[$key] = $value;
            }
        }

        return $existing;
    }

    /**
     * Batched extraction for chunked strategy
     */
    private function batchedExtraction(DOMXPath $xpath, string $url, array $targets, int $batchSize = 500): array
    {
        $results = [];

        // Extract different element types in batches
        $elementTypes = [
            'images' => '//img',
            'links' => '//a[@href]',
            'headings' => '//h1 | //h2 | //h3 | //h4 | //h5 | //h6',
            'scripts' => '//script',
            'styles' => '//style | //link[@rel="stylesheet"]',
            'meta' => '//meta'
        ];

        foreach ($elementTypes as $type => $selector) {
            if (empty($targets) || in_array($type, $targets)) {
                $results[$type] = $this->extractElementsBatched($xpath, $selector, $batchSize);

                // Force garbage collection after processing each element type
                gc_collect_cycles();
            }
        }

        return $results;
    }

    /**
     * Extract elements in batches to manage memory
     */
    private function extractElementsBatched(DOMXPath $xpath, string $selector, int $batchSize): array
    {
        $allElements = $xpath->query($selector);
        $results = [];
        $processed = 0;

        for ($i = 0; $i < $allElements->length; $i += $batchSize) {
            $batch = [];
            $endIndex = min($i + $batchSize, $allElements->length);

            for ($j = $i; $j < $endIndex; $j++) {
                $element = $allElements->item($j);
                $batch[] = $this->extractElementData($element);
                $processed++;

                // Check memory usage periodically
                if ($processed % 100 === 0 && memory_get_usage(true) > self::MEMORY_THRESHOLD) {
                    Log::warning('High memory usage during batch processing', [
                        'memory_usage' => memory_get_usage(true),
                        'processed' => $processed,
                        'total' => $allElements->length
                    ]);
                    gc_collect_cycles();
                }
            }

            $results = array_merge($results, $batch);

            // Force garbage collection between batches
            unset($batch);
            gc_collect_cycles();
        }

        return $results;
    }

    /**
     * Extract basic data from DOM element
     */
    private function extractElementData(\DOMElement $element): array
    {
        $data = [
            'tag_name' => $element->tagName
        ];

        // Extract key attributes based on element type
        switch (strtolower($element->tagName)) {
            case 'img':
                $data['src'] = $element->getAttribute('src');
                $data['alt'] = $element->getAttribute('alt');
                $data['title'] = $element->getAttribute('title');
                break;
            case 'a':
                $data['href'] = $element->getAttribute('href');
                $data['text'] = trim($element->textContent);
                break;
            case 'meta':
                $data['name'] = $element->getAttribute('name');
                $data['property'] = $element->getAttribute('property');
                $data['content'] = $element->getAttribute('content');
                break;
            case 'script':
                $data['src'] = $element->getAttribute('src');
                $data['type'] = $element->getAttribute('type');
                break;
            default:
                $data['text'] = trim($element->textContent);
                break;
        }

        return $data;
    }

    /**
     * Extract targeted data from XPath
     */
    private function extractTargetedData(DOMXPath $xpath, string $url, array $targets, int $chunkIndex = 0): array
    {
        $results = [];

        if (empty($targets) || in_array('meta', $targets)) {
            $results['meta'] = $this->extractMetaDataOptimized($xpath);
        }

        if (empty($targets) || in_array('headings', $targets)) {
            $results['headings'] = $this->extractHeadingsOptimized($xpath);
        }

        if (empty($targets) || in_array('images', $targets)) {
            $results['images'] = $this->extractImagesOptimized($xpath);
        }

        if (empty($targets) || in_array('links', $targets)) {
            $results['links'] = $this->extractLinksOptimized($xpath, $url);
        }

        if (empty($targets) || in_array('scripts', $targets)) {
            $results['scripts'] = $this->extractScriptsOptimized($xpath);
        }

        return $results;
    }

    /**
     * Optimized meta data extraction
     */
    private function extractMetaDataOptimized(DOMXPath $xpath): array
    {
        $meta = [];

        // Extract title
        $titleNode = $xpath->query('//title')->item(0);
        $meta['title'] = $titleNode ? trim($titleNode->textContent) : '';

        // Extract common meta tags in one query
        $metaNodes = $xpath->query('//meta[@name or @property]');
        foreach ($metaNodes as $node) {
            $name = $node->getAttribute('name') ?: $node->getAttribute('property');
            $content = $node->getAttribute('content');

            if ($name && $content) {
                $meta[$name] = $content;
            }
        }

        return $meta;
    }

    /**
     * Optimized headings extraction
     */
    private function extractHeadingsOptimized(DOMXPath $xpath): array
    {
        $headings = [];

        // Extract all headings in one query
        $headingNodes = $xpath->query('//h1 | //h2 | //h3 | //h4 | //h5 | //h6');

        foreach ($headingNodes as $node) {
            $level = $node->tagName;
            if (!isset($headings[$level])) {
                $headings[$level] = [];
            }

            $text = trim($node->textContent);
            if (!empty($text)) {
                $headings[$level][] = [
                    'text' => $text,
                    'length' => strlen($text)
                ];
            }
        }

        return $headings;
    }

    /**
     * Optimized images extraction
     */
    private function extractImagesOptimized(DOMXPath $xpath): array
    {
        $images = [];
        $imageNodes = $xpath->query('//img[@src]');

        foreach ($imageNodes as $node) {
            $images[] = [
                'src' => $node->getAttribute('src'),
                'alt' => $node->getAttribute('alt'),
                'has_alt' => !empty($node->getAttribute('alt'))
            ];
        }

        return [
            'total_count' => count($images),
            'without_alt_count' => count(array_filter($images, fn($img) => !$img['has_alt'])),
            'images' => $images
        ];
    }

    /**
     * Optimized links extraction
     */
    private function extractLinksOptimized(DOMXPath $xpath, string $baseUrl): array
    {
        $links = [];
        $linkNodes = $xpath->query('//a[@href]');
        $baseDomain = parse_url($baseUrl, PHP_URL_HOST);

        foreach ($linkNodes as $node) {
            $href = $node->getAttribute('href');
            $isExternal = $this->isExternalLink($href, $baseDomain);

            $links[] = [
                'href' => $href,
                'text' => trim($node->textContent),
                'is_external' => $isExternal
            ];
        }

        return [
            'total_count' => count($links),
            'external_count' => count(array_filter($links, fn($link) => $link['is_external'])),
            'links' => $links
        ];
    }

    /**
     * Optimized scripts extraction
     */
    private function extractScriptsOptimized(DOMXPath $xpath): array
    {
        $scripts = [];
        $scriptNodes = $xpath->query('//script');

        foreach ($scriptNodes as $node) {
            $scripts[] = [
                'src' => $node->getAttribute('src'),
                'type' => $node->getAttribute('type'),
                'async' => $node->hasAttribute('async'),
                'defer' => $node->hasAttribute('defer'),
                'inline' => empty($node->getAttribute('src'))
            ];
        }

        return [
            'total_count' => count($scripts),
            'external_count' => count(array_filter($scripts, fn($s) => !empty($s['src']))),
            'async_count' => count(array_filter($scripts, fn($s) => $s['async'])),
            'scripts' => $scripts
        ];
    }

    /**
     * Record performance metrics
     */
    private function recordPerformanceMetrics(float $startTime, int $startMemory, array $optimizationInfo): void
    {
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);

        $this->performanceMetrics = [
            'processing_time_ms' => round(($endTime - $startTime) * 1000, 2),
            'memory_used_mb' => round(($endMemory - $startMemory) / 1024 / 1024, 2),
            'peak_memory_mb' => round($peakMemory / 1024 / 1024, 2),
            'optimization_applied' => $optimizationInfo['should_optimize'],
            'optimization_strategy' => $optimizationInfo['should_optimize'] ? $optimizationInfo['recommended_strategy'] : 'standard',
            'optimization_reasons' => $optimizationInfo['optimization_reasons'] ?? []
        ];
    }

    /**
     * Utility methods
     */
    private function estimateDomElementCount(string $html): int
    {
        // Rough estimation based on opening tags
        return preg_match_all('/<[a-zA-Z][^>]*>/', $html);
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }

    private function isExternalLink(string $url, string $baseDomain): bool
    {
        $linkDomain = parse_url($url, PHP_URL_HOST);
        return $linkDomain && $linkDomain !== $baseDomain;
    }

    /**
     * Get performance metrics from last parsing operation
     */
    public function getPerformanceMetrics(): array
    {
        return $this->performanceMetrics;
    }

    /**
     * Reset performance metrics
     */
    public function resetMetrics(): void
    {
        $this->performanceMetrics = [];
    }
}