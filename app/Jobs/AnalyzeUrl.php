<?php

namespace App\Jobs;

use App\Services\SeoAnalyzerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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
        public ?int $userId = null,
        public array $options = []
    ) {
        $this->onQueue('seo_analysis');
    }

    /**
     * Execute the job.
     */
    public function handle(SeoAnalyzerService $seoAnalyzer): void
    {
        Log::info('Starting comprehensive SEO analysis', [
            'url' => $this->url,
            'user_id' => $this->userId,
            'options' => $this->options
        ]);

        try {
            // Use the comprehensive SEO analyzer service
            $analysis = $seoAnalyzer->analyze($this->url, $this->options);

            Log::info('SEO analysis completed successfully', [
                'url' => $this->url,
                'overall_score' => $analysis['scores']['overall_score'] ?? 0,
                'grade' => $analysis['scores']['grade'] ?? 'N/A',
                'analysis_duration' => $analysis['analysis_duration_ms'] ?? 0
            ]);

            // Dispatch reporting job with comprehensive analysis
            GenerateSeoReport::dispatch($this->url, $analysis, $this->userId);

        } catch (\Exception $e) {
            Log::error('SEO analysis failed', [
                'url' => $this->url,
                'user_id' => $this->userId,
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