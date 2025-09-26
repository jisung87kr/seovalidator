<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class CrawlUrl implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $tries = 3;
    public $backoff = 10;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $url,
        public ?int $userId = null,
        public array $options = []
    ) {
        $this->onQueue('seo_crawling');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting URL crawl', [
            'url' => $this->url,
            'user_id' => $this->userId,
            'options' => $this->options
        ]);

        try {
            // Simulate crawling with HTTP request
            $response = Http::timeout(30)
                ->withUserAgent('SEO Validator Bot/1.0')
                ->get($this->url);

            if ($response->successful()) {
                $html = $response->body();

                // Store crawl result temporarily (in real implementation, save to database)
                $crawlData = [
                    'url' => $this->url,
                    'html' => $html,
                    'status_code' => $response->status(),
                    'headers' => $response->headers(),
                    'crawled_at' => now(),
                    'size_bytes' => strlen($html)
                ];

                Log::info('URL crawl completed successfully', [
                    'url' => $this->url,
                    'status_code' => $response->status(),
                    'size_bytes' => strlen($html)
                ]);

                // Dispatch analysis job
                AnalyzeUrl::dispatch($this->url, $crawlData, $this->userId);

            } else {
                Log::warning('URL crawl failed with HTTP error', [
                    'url' => $this->url,
                    'status_code' => $response->status()
                ]);

                $this->fail(new \Exception("HTTP {$response->status()} error for URL: {$this->url}"));
            }

        } catch (\Exception $e) {
            Log::error('URL crawl failed with exception', [
                'url' => $this->url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CrawlUrl job failed permanently', [
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
        return ['crawling', 'seo', 'url:' . parse_url($this->url, PHP_URL_HOST)];
    }
}