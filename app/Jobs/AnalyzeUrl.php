<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use DOMDocument;
use DOMXPath;

class AnalyzeUrl implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 2;
    public $backoff = 15;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $url,
        public array $crawlData,
        public ?int $userId = null
    ) {
        $this->onQueue('seo_analysis');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting SEO analysis', [
            'url' => $this->url,
            'user_id' => $this->userId
        ]);

        try {
            $html = $this->crawlData['html'];

            // Parse HTML
            $dom = new DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML($html);
            $xpath = new DOMXPath($dom);
            libxml_clear_errors();

            // Perform SEO analysis
            $analysis = [
                'url' => $this->url,
                'analyzed_at' => now(),
                'meta' => $this->analyzeMetaTags($xpath),
                'headings' => $this->analyzeHeadings($xpath),
                'images' => $this->analyzeImages($xpath),
                'links' => $this->analyzeLinks($xpath),
                'content' => $this->analyzeContent($xpath, $html),
                'technical' => $this->analyzeTechnical($this->crawlData),
                'performance' => $this->analyzePerformance($html, $this->crawlData)
            ];

            Log::info('SEO analysis completed', [
                'url' => $this->url,
                'title_length' => strlen($analysis['meta']['title'] ?? ''),
                'description_length' => strlen($analysis['meta']['description'] ?? ''),
                'h1_count' => count($analysis['headings']['h1'] ?? []),
                'images_without_alt' => $analysis['images']['without_alt_count'] ?? 0
            ]);

            // Dispatch reporting job
            GenerateSeoReport::dispatch($this->url, $analysis, $this->userId);

        } catch (\Exception $e) {
            Log::error('SEO analysis failed', [
                'url' => $this->url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Analyze meta tags
     */
    private function analyzeMetaTags(DOMXPath $xpath): array
    {
        $title = $xpath->query('//title')->item(0)?->textContent ?? '';
        $description = $xpath->query('//meta[@name="description"]/@content')->item(0)?->value ?? '';
        $keywords = $xpath->query('//meta[@name="keywords"]/@content')->item(0)?->value ?? '';

        $ogTitle = $xpath->query('//meta[@property="og:title"]/@content')->item(0)?->value ?? '';
        $ogDescription = $xpath->query('//meta[@property="og:description"]/@content')->item(0)?->value ?? '';
        $ogImage = $xpath->query('//meta[@property="og:image"]/@content')->item(0)?->value ?? '';

        return [
            'title' => trim($title),
            'title_length' => strlen(trim($title)),
            'description' => trim($description),
            'description_length' => strlen(trim($description)),
            'keywords' => trim($keywords),
            'og_title' => trim($ogTitle),
            'og_description' => trim($ogDescription),
            'og_image' => trim($ogImage),
            'canonical' => $xpath->query('//link[@rel="canonical"]/@href')->item(0)?->value ?? ''
        ];
    }

    /**
     * Analyze heading structure
     */
    private function analyzeHeadings(DOMXPath $xpath): array
    {
        $headings = [];

        for ($level = 1; $level <= 6; $level++) {
            $headingNodes = $xpath->query("//h{$level}");
            $headings["h{$level}"] = [];

            foreach ($headingNodes as $heading) {
                $headings["h{$level}"][] = trim($heading->textContent);
            }
        }

        return $headings;
    }

    /**
     * Analyze images
     */
    private function analyzeImages(DOMXPath $xpath): array
    {
        $images = $xpath->query('//img');
        $totalImages = $images->length;
        $withoutAlt = 0;
        $withoutTitle = 0;
        $imageList = [];

        foreach ($images as $img) {
            $src = $img->getAttribute('src');
            $alt = $img->getAttribute('alt');
            $title = $img->getAttribute('title');

            if (empty($alt)) $withoutAlt++;
            if (empty($title)) $withoutTitle++;

            $imageList[] = [
                'src' => $src,
                'alt' => $alt,
                'title' => $title,
                'has_alt' => !empty($alt),
                'has_title' => !empty($title)
            ];
        }

        return [
            'total_count' => $totalImages,
            'without_alt_count' => $withoutAlt,
            'without_title_count' => $withoutTitle,
            'images' => $imageList
        ];
    }

    /**
     * Analyze links
     */
    private function analyzeLinks(DOMXPath $xpath): array
    {
        $links = $xpath->query('//a[@href]');
        $totalLinks = $links->length;
        $internalLinks = 0;
        $externalLinks = 0;
        $noFollowLinks = 0;

        $parsedUrl = parse_url($this->url);
        $currentDomain = $parsedUrl['host'] ?? '';

        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            $rel = $link->getAttribute('rel');

            if (str_contains($rel, 'nofollow')) {
                $noFollowLinks++;
            }

            if (str_starts_with($href, 'http') || str_starts_with($href, '//')) {
                $linkDomain = parse_url($href, PHP_URL_HOST);
                if ($linkDomain === $currentDomain) {
                    $internalLinks++;
                } else {
                    $externalLinks++;
                }
            } else {
                $internalLinks++;
            }
        }

        return [
            'total_count' => $totalLinks,
            'internal_count' => $internalLinks,
            'external_count' => $externalLinks,
            'nofollow_count' => $noFollowLinks
        ];
    }

    /**
     * Analyze content
     */
    private function analyzeContent(DOMXPath $xpath, string $html): array
    {
        $text = strip_tags($html);
        $wordCount = str_word_count($text);

        return [
            'word_count' => $wordCount,
            'character_count' => strlen($text),
            'html_size' => strlen($html),
            'text_to_html_ratio' => strlen($html) > 0 ? round((strlen($text) / strlen($html)) * 100, 2) : 0
        ];
    }

    /**
     * Analyze technical aspects
     */
    private function analyzeTechnical(array $crawlData): array
    {
        $headers = $crawlData['headers'] ?? [];

        return [
            'status_code' => $crawlData['status_code'] ?? 0,
            'content_type' => $headers['content-type'][0] ?? '',
            'server' => $headers['server'][0] ?? '',
            'cache_control' => $headers['cache-control'][0] ?? '',
            'last_modified' => $headers['last-modified'][0] ?? '',
            'etag' => $headers['etag'][0] ?? ''
        ];
    }

    /**
     * Analyze performance metrics
     */
    private function analyzePerformance(string $html, array $crawlData): array
    {
        return [
            'html_size_bytes' => strlen($html),
            'html_size_kb' => round(strlen($html) / 1024, 2),
            'load_time_estimate' => $this->estimateLoadTime(strlen($html)),
            'compression_ratio' => $this->calculateCompressionRatio($html)
        ];
    }

    /**
     * Estimate load time based on content size
     */
    private function estimateLoadTime(int $sizeBytes): array
    {
        // Rough estimates for different connection speeds (in seconds)
        return [
            '56k_modem' => round($sizeBytes / (56 * 1024 / 8), 2),
            'dsl_1mbps' => round($sizeBytes / (1024 * 1024 / 8), 2),
            'cable_5mbps' => round($sizeBytes / (5 * 1024 * 1024 / 8), 2),
            'fiber_50mbps' => round($sizeBytes / (50 * 1024 * 1024 / 8), 2)
        ];
    }

    /**
     * Calculate potential compression ratio
     */
    private function calculateCompressionRatio(string $html): float
    {
        $compressed = gzcompress($html, 9);
        return $compressed ? round((1 - strlen($compressed) / strlen($html)) * 100, 2) : 0;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('AnalyzeUrl job failed permanently', [
            'url' => $this->url,
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['analysis', 'seo', 'url:' . parse_url($this->url, PHP_URL_HOST)];
    }
}