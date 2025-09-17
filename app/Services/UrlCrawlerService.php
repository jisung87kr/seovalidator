<?php

namespace App\Services;

use App\Contracts\CrawlerInterface;
use App\DTOs\CrawlResult;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;
use Throwable;

class UrlCrawlerService implements CrawlerInterface
{
    private int $timeout;
    private string $userAgent;
    private array $userAgents;
    private int $maxRetries;
    private int $retryDelay;

    public function __construct()
    {
        $this->timeout = config('crawler.timeout', 30);
        $this->userAgents = config('crawler.user_agents', []);
        $this->userAgent = $this->getRandomUserAgent();
        $this->maxRetries = config('crawler.retry.max_attempts', 3);
        $this->retryDelay = config('crawler.retry.delay_seconds', 2);
    }

    public function crawl(string $url): CrawlResult
    {
        if (!$this->validateUrl($url)) {
            return CrawlResult::failure($url, 'Invalid URL format');
        }

        // Check cache first
        if (config('crawler.cache.enabled', true)) {
            $cached = $this->getCachedResult($url);
            if ($cached) {
                return $cached;
            }
        }

        // Attempt crawling with retry logic
        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            try {
                $result = $this->performCrawl($url);
                
                if ($result->isSuccessful()) {
                    $this->cacheResult($url, $result);
                    return $result;
                }

                if ($attempt < $this->maxRetries) {
                    Log::warning("Crawl attempt {$attempt} failed for {$url}, retrying...");
                    sleep($this->retryDelay);
                }
            } catch (Throwable $e) {
                Log::error("Crawl attempt {$attempt} error for {$url}: " . $e->getMessage());
                
                if ($attempt < $this->maxRetries) {
                    sleep($this->retryDelay);
                    continue;
                }
                
                return CrawlResult::failure($url, "Failed after {$this->maxRetries} attempts: " . $e->getMessage());
            }
        }

        return CrawlResult::failure($url, "Failed after {$this->maxRetries} attempts");
    }

    private function performCrawl(string $url): CrawlResult
    {
        $startTime = microtime(true);

        try {
            $browsershot = Browsershot::url($url)
                ->userAgent($this->userAgent)
                ->timeout($this->timeout)
                ->dismissDialogs()
                ->ignoreHttpsErrors()
                ->waitUntilNetworkIdle();

            // Apply Chrome arguments from config
            $chromeArgs = config('crawler.browsershot.args', []);
            foreach ($chromeArgs as $arg) {
                $browsershot->addChromiumArgument($arg);
            }

            $htmlContent = $browsershot->bodyHtml();
            $responseTime = microtime(true) - $startTime;

            if (empty($htmlContent)) {
                return CrawlResult::failure($url, 'Empty response received', null, $responseTime);
            }

            $extractedData = $this->extractDataFromHtml($htmlContent, $url);

            return CrawlResult::success(
                url: $url,
                htmlContent: $htmlContent,
                extractedData: $extractedData,
                responseTime: $responseTime,
                statusCode: 200 // Browsershot doesn't provide status code directly
            );

        } catch (Throwable $e) {
            $responseTime = microtime(true) - $startTime;
            return CrawlResult::failure($url, $e->getMessage(), null, $responseTime);
        }
    }

    private function extractDataFromHtml(string $html, string $baseUrl): array
    {
        $dom = new DOMDocument();
        
        // Suppress warnings for malformed HTML
        libxml_use_internal_errors(true);
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        return [
            'title' => $this->extractTitle($xpath),
            'meta_description' => $this->extractMetaDescription($xpath),
            'meta_tags' => $this->extractMetaTags($xpath),
            'images' => $this->extractImages($xpath, $baseUrl),
            'internal_links' => $this->extractInternalLinks($xpath, $baseUrl),
            'external_links' => $this->extractExternalLinks($xpath, $baseUrl),
            'headings' => $this->extractHeadings($xpath),
            'canonical_url' => $this->extractCanonicalUrl($xpath),
            'og_tags' => $this->extractOpenGraphTags($xpath),
            'twitter_tags' => $this->extractTwitterTags($xpath),
        ];
    }

    private function extractTitle(DOMXPath $xpath): ?string
    {
        $titleNodes = $xpath->query('//title');
        return $titleNodes->length > 0 ? trim($titleNodes->item(0)->textContent) : null;
    }

    private function extractMetaDescription(DOMXPath $xpath): ?string
    {
        $metaNodes = $xpath->query('//meta[@name="description"]/@content');
        return $metaNodes->length > 0 ? trim($metaNodes->item(0)->textContent) : null;
    }

    private function extractMetaTags(DOMXPath $xpath): array
    {
        $metaTags = [];
        $metaNodes = $xpath->query('//meta[@name or @property]');

        foreach ($metaNodes as $node) {
            $name = $node->getAttribute('name') ?: $node->getAttribute('property');
            $content = $node->getAttribute('content');
            
            if ($name && $content) {
                $metaTags[$name] = $content;
            }
        }

        return $metaTags;
    }

    private function extractImages(DOMXPath $xpath, string $baseUrl): array
    {
        $images = [];
        $imgNodes = $xpath->query('//img[@src]');

        foreach ($imgNodes as $img) {
            $src = $img->getAttribute('src');
            $alt = $img->getAttribute('alt');
            
            $images[] = [
                'src' => $this->resolveUrl($src, $baseUrl),
                'alt' => $alt,
                'title' => $img->getAttribute('title'),
            ];
        }

        return $images;
    }

    private function extractInternalLinks(DOMXPath $xpath, string $baseUrl): array
    {
        return $this->extractLinks($xpath, $baseUrl, true);
    }

    private function extractExternalLinks(DOMXPath $xpath, string $baseUrl): array
    {
        return $this->extractLinks($xpath, $baseUrl, false);
    }

    private function extractLinks(DOMXPath $xpath, string $baseUrl, bool $internal): array
    {
        $links = [];
        $linkNodes = $xpath->query('//a[@href]');
        $baseDomain = parse_url($baseUrl, PHP_URL_HOST);

        foreach ($linkNodes as $link) {
            $href = $link->getAttribute('href');
            $resolvedUrl = $this->resolveUrl($href, $baseUrl);
            $linkDomain = parse_url($resolvedUrl, PHP_URL_HOST);
            
            $isInternal = $linkDomain === $baseDomain || empty($linkDomain);
            
            if ($isInternal === $internal) {
                $links[] = [
                    'url' => $resolvedUrl,
                    'text' => trim($link->textContent),
                    'title' => $link->getAttribute('title'),
                ];
            }
        }

        return $links;
    }

    private function extractHeadings(DOMXPath $xpath): array
    {
        $headings = [];
        
        for ($level = 1; $level <= 6; $level++) {
            $headingNodes = $xpath->query("//h{$level}");
            
            foreach ($headingNodes as $heading) {
                $headings["h{$level}"][] = trim($heading->textContent);
            }
        }

        return $headings;
    }

    private function extractCanonicalUrl(DOMXPath $xpath): ?string
    {
        $canonicalNodes = $xpath->query('//link[@rel="canonical"]/@href');
        return $canonicalNodes->length > 0 ? $canonicalNodes->item(0)->textContent : null;
    }

    private function extractOpenGraphTags(DOMXPath $xpath): array
    {
        $ogTags = [];
        $ogNodes = $xpath->query('//meta[starts-with(@property, "og:")]');

        foreach ($ogNodes as $node) {
            $property = $node->getAttribute('property');
            $content = $node->getAttribute('content');
            
            if ($property && $content) {
                $ogTags[$property] = $content;
            }
        }

        return $ogTags;
    }

    private function extractTwitterTags(DOMXPath $xpath): array
    {
        $twitterTags = [];
        $twitterNodes = $xpath->query('//meta[starts-with(@name, "twitter:")]');

        foreach ($twitterNodes as $node) {
            $name = $node->getAttribute('name');
            $content = $node->getAttribute('content');
            
            if ($name && $content) {
                $twitterTags[$name] = $content;
            }
        }

        return $twitterTags;
    }

    private function resolveUrl(string $url, string $baseUrl): string
    {
        if (empty($url)) {
            return $url;
        }

        // Already absolute URL
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        // Protocol-relative URL
        if (str_starts_with($url, '//')) {
            $scheme = parse_url($baseUrl, PHP_URL_SCHEME);
            return $scheme . ':' . $url;
        }

        // Absolute path
        if (str_starts_with($url, '/')) {
            $parsed = parse_url($baseUrl);
            return $parsed['scheme'] . '://' . $parsed['host'] . 
                   (isset($parsed['port']) ? ':' . $parsed['port'] : '') . $url;
        }

        // Relative path
        return rtrim($baseUrl, '/') . '/' . ltrim($url, '/');
    }

    public function validateUrl(string $url): bool
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        return in_array($scheme, ['http', 'https']);
    }

    public function setUserAgent(string $userAgent): self
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    public function setTimeout(int $seconds): self
    {
        $this->timeout = $seconds;
        return $this;
    }

    private function getRandomUserAgent(): string
    {
        if (empty($this->userAgents)) {
            return 'Mozilla/5.0 (compatible; SEOValidator/1.0)';
        }

        return $this->userAgents[array_rand($this->userAgents)];
    }

    private function getCachedResult(string $url): ?CrawlResult
    {
        $cacheKey = config('crawler.cache.prefix') . md5($url);
        return Cache::get($cacheKey);
    }

    private function cacheResult(string $url, CrawlResult $result): void
    {
        $cacheKey = config('crawler.cache.prefix') . md5($url);
        $ttl = config('crawler.cache.ttl', 86400);
        
        Cache::put($cacheKey, $result, $ttl);
    }
}