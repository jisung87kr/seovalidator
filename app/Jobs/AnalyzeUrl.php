<?php

namespace App\Jobs;

use App\Services\SeoAnalyzerService;
use App\Models\SeoAnalysis;
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

            // Log the actual structure for debugging
            Log::info('SEO analysis data structure', [
                'url' => $this->url,
                'keys' => array_keys($analysis),
                'seo_elements_keys' => isset($analysis['seo_elements']) ? array_keys($analysis['seo_elements']) : [],
                'overall_score' => $analysis['scores']['overall_score'] ?? 0,
                'grade' => $analysis['scores']['grade'] ?? 'N/A',
                'analysis_duration' => $analysis['analysis_duration_ms'] ?? 0
            ]);

            // Save analysis results to database
            $this->saveAnalysisToDatabase($analysis);

            // Dispatch reporting job with comprehensive analysis
            GenerateSeoReport::dispatch($this->url, $analysis, $this->userId);

        } catch (\Exception $e) {
            Log::error('SEO analysis failed', [
                'url' => $this->url,
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Update status to failed in database
            $this->updateAnalysisStatus('failed', $e->getMessage());

            throw $e;
        }
    }

    /**
     * Save analysis results to database
     */
    private function saveAnalysisToDatabase(array $analysis): void
    {
        try {
            // Find the existing analysis record by URL and user
            $seoAnalysis = SeoAnalysis::where('url', $this->url)
                ->where('user_id', $this->userId)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$seoAnalysis) {
                // Create new record if not found
                $seoAnalysis = SeoAnalysis::create([
                    'user_id' => $this->userId,
                    'url' => $this->url,
                    'status' => 'completed'
                ]);
            }

            // Update with analysis results
            $seoAnalysis->update([
                'status' => 'completed',
                'overall_score' => $analysis['scores']['overall_score'] ?? 0,
                'technical_score' => $analysis['scores']['category_scores']['technical']['score'] ?? 0,
                'content_score' => $analysis['scores']['category_scores']['content']['score'] ?? 0,
                'performance_score' => $analysis['scores']['category_scores']['technical']['metrics']['performance_score'] ?? 0,
                'accessibility_score' => $analysis['scores']['category_scores']['images']['score'] ?? 0,
                'title' => $analysis['seo_elements']['meta']['title'] ?? null,
                'analysis_data' => json_encode($analysis),
                'analyzed_at' => now()
            ]);

            Log::info('Analysis results saved to database', [
                'analysis_id' => $seoAnalysis->id,
                'url' => $this->url,
                'overall_score' => $seoAnalysis->overall_score
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to save analysis to database', [
                'url' => $this->url,
                'user_id' => $this->userId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update analysis status in database
     */
    private function updateAnalysisStatus(string $status, ?string $errorMessage = null): void
    {
        try {
            $seoAnalysis = SeoAnalysis::where('url', $this->url)
                ->where('user_id', $this->userId)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($seoAnalysis) {
                $updateData = ['status' => $status];
                if ($errorMessage) {
                    $updateData['error_message'] = $errorMessage;
                }
                $seoAnalysis->update($updateData);
            }
        } catch (\Exception $e) {
            Log::error('Failed to update analysis status', [
                'url' => $this->url,
                'user_id' => $this->userId,
                'status' => $status,
                'error' => $e->getMessage()
            ]);
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

        // Update status to failed in database
        $this->updateAnalysisStatus('failed', $exception->getMessage());
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['analysis', 'seo', 'url:' . parse_url($this->url, PHP_URL_HOST)];
    }
}
