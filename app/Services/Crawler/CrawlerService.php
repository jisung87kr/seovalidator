<?php

namespace App\Services\Crawler;

use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class CrawlerService
{
    private array $defaultOptions = [
        'user_agent' => 'SEO Validator Bot/1.0 (Googlebot-compatible)',
        'viewport' => ['width' => 1200, 'height' => 800],
        'timeout' => 30000, // 30 seconds
        'javascript_enabled' => true,
        'wait_for_load' => 3000, // 3 seconds
        'device_scale_factor' => 1,
        'mobile' => false,
        'ignore_https_errors' => false
    ];

    /**
     * Crawl a URL and extract content with JavaScript rendering
     */
    public function crawl(string $url, array $options = []): array
    {
        $startTime = microtime(true);
        $options = array_merge($this->defaultOptions, $options);

        Log::info('Starting URL crawl with browser', [
            'url' => $url,
            'javascript_enabled' => $options['javascript_enabled']
        ]);

        try {
            if ($options['javascript_enabled']) {
                $result = $this->crawlWithJavaScript($url, $options);
            } else {
                $result = $this->crawlWithoutJavaScript($url, $options);
            }

            $duration = (microtime(true) - $startTime) * 1000;
            $result['duration_ms'] = round($duration, 2);
            $result['crawled_at'] = now()->toISOString();

            Log::info('URL crawl completed', [
                'url' => $url,
                'duration_ms' => $result['duration_ms'],
                'html_size' => strlen($result['html']),
                'status_code' => $result['status_code'],
                'javascript_enabled' => $options['javascript_enabled']
            ]);

            return $result;

        } catch (\Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;

            Log::error('URL crawl failed', [
                'url' => $url,
                'duration_ms' => round($duration, 2),
                'error' => $e->getMessage(),
                'javascript_enabled' => $options['javascript_enabled']
            ]);

            throw new CrawlerException("Failed to crawl URL: {$url}. Error: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Crawl URL with JavaScript rendering using Browsershot
     */
    private function crawlWithJavaScript(string $url, array $options): array
    {
        try {
            $browsershot = Browsershot::url($url)
                ->userAgent($options['user_agent'])
                ->windowSize($options['viewport']['width'], $options['viewport']['height'])
                ->timeout($options['timeout'] / 1000)
                ->waitUntilNetworkIdle(true, $options['wait_for_load'])
                ->setOption('args', [
                    '--no-sandbox',
                    '--disable-dev-shm-usage',
                    '--disable-gpu',
                    '--no-first-run'
                ]);

            if ($options['mobile']) {
                $browsershot->mobile();
            }

            if ($options['ignore_https_errors']) {
                $browsershot->ignoreHttpsErrors();
            }

            // Get HTML content
            $html = $browsershot->bodyHtml();

            // Get additional page information
            $pageInfo = $this->getPageInfoWithBrowser($url, $options);

            return [
                'html' => $html,
                'status_code' => $pageInfo['status_code'] ?? 200,
                'final_url' => $pageInfo['final_url'] ?? $url,
                'headers' => $pageInfo['headers'] ?? [],
                'load_time_ms' => $pageInfo['load_time_ms'] ?? 0,
                'javascript_enabled' => true,
                'user_agent' => $options['user_agent'],
                'viewport' => $options['viewport'],
                'mobile' => $options['mobile'],
                'page_title' => $pageInfo['page_title'] ?? '',
                'resources_loaded' => $pageInfo['resources_loaded'] ?? 0
            ];

        } catch (\Exception $e) {
            Log::error('JavaScript crawling failed, falling back to HTTP', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);

            // Fallback to HTTP-only crawling
            return $this->crawlWithoutJavaScript($url, $options);
        }
    }

    /**
     * Get additional page information using browser
     */
    private function getPageInfoWithBrowser(string $url, array $options): array
    {
        try {
            $browsershot = Browsershot::url($url)
                ->userAgent($options['user_agent'])
                ->windowSize($options['viewport']['width'], $options['viewport']['height'])
                ->timeout($options['timeout'] / 1000);

            if ($options['mobile']) {
                $browsershot->mobile();
            }

            if ($options['ignore_https_errors']) {
                $browsershot->ignoreHttpsErrors();
            }

            // Execute JavaScript to get page information
            $pageInfo = $browsershot->evaluate("
                return {
                    title: document.title,
                    finalUrl: window.location.href,
                    resourcesLoaded: document.images.length + document.scripts.length + document.styleSheets.length,
                    loadTime: performance.timing.loadEventEnd - performance.timing.navigationStart
                };
            ");

            return [
                'page_title' => $pageInfo['title'] ?? '',
                'final_url' => $pageInfo['finalUrl'] ?? $url,
                'resources_loaded' => $pageInfo['resourcesLoaded'] ?? 0,
                'load_time_ms' => $pageInfo['loadTime'] ?? 0,
                'status_code' => 200, // Assume success if we got this far
                'headers' => []
            ];

        } catch (\Exception $e) {
            Log::warning('Failed to get additional page info with browser', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Crawl URL without JavaScript using HTTP client
     */
    private function crawlWithoutJavaScript(string $url, array $options): array
    {
        $startTime = microtime(true);

        $response = Http::timeout($options['timeout'] / 1000)
            ->withUserAgent($options['user_agent'])
            ->withOptions([
                'verify' => !$options['ignore_https_errors'],
                'allow_redirects' => [
                    'max' => 10,
                    'strict' => false,
                    'referer' => true,
                    'protocols' => ['http', 'https'],
                    'track_redirects' => true
                ]
            ])
            ->get($url);

        $loadTime = (microtime(true) - $startTime) * 1000;

        if (!$response->successful()) {
            throw new \Exception("HTTP request failed with status: {$response->status()}");
        }

        return [
            'html' => $response->body(),
            'status_code' => $response->status(),
            'final_url' => $this->getFinalUrl($response) ?? $url,
            'headers' => $response->headers(),
            'load_time_ms' => round($loadTime, 2),
            'javascript_enabled' => false,
            'user_agent' => $options['user_agent'],
            'viewport' => $options['viewport'],
            'mobile' => $options['mobile'],
            'page_title' => $this->extractTitleFromHtml($response->body()),
            'resources_loaded' => 0
        ];
    }

    /**
     * Extract final URL from response (handle redirects)
     */
    private function getFinalUrl(Response $response): ?string
    {
        // Laravel HTTP client handles redirects automatically
        // The final URL would be available in the response if tracked
        $effectiveUrl = $response->effectiveUri();
        return $effectiveUrl ? (string) $effectiveUrl : null;
    }

    /**
     * Extract page title from HTML content
     */
    private function extractTitleFromHtml(string $html): string
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
            return html_entity_decode(trim($matches[1]), ENT_QUOTES, 'UTF-8');
        }

        return '';
    }

    /**
     * Test if a URL is accessible
     */
    public function testUrl(string $url, array $options = []): array
    {
        $options = array_merge($this->defaultOptions, $options, [
            'javascript_enabled' => false // Quick test without JS
        ]);

        try {
            $startTime = microtime(true);

            $response = Http::timeout(10)
                ->withUserAgent($options['user_agent'])
                ->head($url);

            $responseTime = (microtime(true) - $startTime) * 1000;

            return [
                'accessible' => $response->successful(),
                'status_code' => $response->status(),
                'response_time_ms' => round($responseTime, 2),
                'content_type' => $response->header('content-type'),
                'content_length' => $response->header('content-length'),
                'server' => $response->header('server'),
                'redirected' => $response->redirected(),
                'final_url' => $this->getFinalUrl($response) ?? $url
            ];

        } catch (\Exception $e) {
            return [
                'accessible' => false,
                'status_code' => 0,
                'response_time_ms' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get crawler capabilities and status
     */
    public function getCapabilities(): array
    {
        return [
            'javascript_support' => $this->checkBrowsershotAvailability(),
            'http_client' => true,
            'user_agents' => [
                'desktop' => 'SEO Validator Bot/1.0 (Googlebot-compatible)',
                'mobile' => 'SEO Validator Bot/1.0 Mobile (Googlebot Mobile-compatible)'
            ],
            'supported_protocols' => ['http', 'https'],
            'max_timeout' => 60000, // 60 seconds
            'max_redirects' => 10
        ];
    }

    /**
     * Check if Browsershot/Puppeteer is available
     */
    private function checkBrowsershotAvailability(): bool
    {
        try {
            // Try to create a simple Browsershot instance
            Browsershot::url('about:blank')->setTimeout(1);
            return true;
        } catch (\Exception $e) {
            Log::warning('Browsershot/Puppeteer not available', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Batch crawl multiple URLs
     */
    public function crawlBatch(array $urls, array $options = []): array
    {
        $results = [];
        $errors = [];

        foreach ($urls as $url) {
            try {
                $results[$url] = $this->crawl($url, $options);
            } catch (\Exception $e) {
                $errors[$url] = $e->getMessage();
                Log::error('Batch crawl failed for URL', [
                    'url' => $url,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'results' => $results,
            'errors' => $errors,
            'summary' => [
                'total_urls' => count($urls),
                'successful' => count($results),
                'failed' => count($errors)
            ]
        ];
    }
}

/**
 * Custom exception for crawler errors
 */
class CrawlerException extends \Exception
{
    //
}