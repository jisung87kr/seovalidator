<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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
     *
     * Note: This job is now deprecated in favor of direct SeoAnalyzerService usage.
     * The SeoAnalyzerService handles both crawling and analysis in a single operation.
     */
    public function handle(): void
    {
        Log::info('CrawlUrl job executed - redirecting to AnalyzeUrl', [
            'url' => $this->url,
            'user_id' => $this->userId,
            'options' => $this->options
        ]);

        // Directly dispatch the analysis job which will handle crawling internally
        AnalyzeUrl::dispatch($this->url, $this->userId, $this->options);
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