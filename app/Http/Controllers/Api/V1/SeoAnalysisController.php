<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\AnalyzeUrlRequest;
use App\Http\Requests\Api\V1\BatchAnalyzeRequest;
use App\Http\Resources\Api\V1\SeoAnalysisResource;
use App\Http\Resources\Api\V1\BatchAnalysisResource;
use App\Services\SeoAnalyzerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SeoAnalysisController extends ApiController
{
    public function __construct(
        private SeoAnalyzerService $seoAnalyzerService
    ) {}

    /**
     * Analyze a single URL for SEO metrics
     *
     * @group SEO Analysis
     */
    public function analyze(AnalyzeUrlRequest $request): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $url = $request->input('url');
            $options = $request->input('options', []);
            $webhookUrl = $request->input('webhook_url');

            // Perform SEO analysis
            $analysis = $this->seoAnalyzerService->analyze($url, $options);

            // Add processing time to metadata
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);
            $analysis['metadata']['processing_time_ms'] = $processingTime;

            // Generate unique analysis ID for tracking
            $analysisId = Str::uuid()->toString();
            $analysis['id'] = $analysisId;

            // Cache the analysis result for history tracking
            $this->cacheAnalysisForHistory($request->user()->id, $analysisId, $analysis);

            // Send webhook notification if requested
            if ($webhookUrl) {
                $this->sendWebhookNotification($webhookUrl, 'analysis.completed', $analysis);
            }

            Log::info('SEO analysis completed via API', [
                'user_id' => $request->user()->id,
                'url' => $url,
                'analysis_id' => $analysisId,
                'processing_time_ms' => $processingTime,
                'overall_score' => $analysis['scores']['overall_score']
            ]);

            return $this->success(
                new SeoAnalysisResource($analysis),
                'SEO analysis completed successfully'
            );

        } catch (\Exception $e) {
            Log::error('SEO analysis failed via API', [
                'user_id' => $request->user()->id,
                'url' => $request->input('url'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Send webhook notification for failure if requested
            if ($request->input('webhook_url')) {
                $this->sendWebhookNotification(
                    $request->input('webhook_url'),
                    'analysis.failed',
                    [
                        'url' => $request->input('url'),
                        'error' => $e->getMessage(),
                        'failed_at' => now()->toISOString()
                    ]
                );
            }

            return $this->serverError('Analysis failed: ' . $e->getMessage());
        }
    }

    /**
     * Analyze multiple URLs in batch
     *
     * @group SEO Analysis
     */
    public function analyzeBatch(BatchAnalyzeRequest $request): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $urls = $request->input('urls');
            $options = $request->input('options', []);
            $webhookUrl = $request->input('webhook_url');
            $async = $request->input('async', false);

            $batchId = Str::uuid()->toString();

            // For async processing, queue the batch job
            if ($async) {
                // TODO: Implement queue-based batch processing when queue system is ready
                // For now, process synchronously but return job status structure
                return $this->processBatchAsync($batchId, $urls, $options, $webhookUrl, $request->user()->id);
            }

            // Process batch synchronously
            $batchResult = $this->seoAnalyzerService->analyzeBatch($urls, $options);

            // Add batch metadata
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);
            $batchResult['batch_id'] = $batchId;
            $batchResult['submitted_at'] = now()->toISOString();
            $batchResult['completed_at'] = now()->toISOString();
            $batchResult['status'] = 'completed';
            $batchResult['processing_time_ms'] = $processingTime;

            if ($webhookUrl) {
                $batchResult['webhook_url'] = $webhookUrl;
            }

            // Cache individual analyses for history
            foreach ($batchResult['results'] as $url => $analysis) {
                $analysisId = Str::uuid()->toString();
                $analysis['id'] = $analysisId;
                $this->cacheAnalysisForHistory($request->user()->id, $analysisId, $analysis);
            }

            // Cache batch result
            Cache::put("batch_analysis:{$batchId}", $batchResult, now()->addHours(24));

            // Send webhook notification if requested
            if ($webhookUrl) {
                $this->sendWebhookNotification($webhookUrl, 'batch.completed', $batchResult);
            }

            Log::info('Batch SEO analysis completed via API', [
                'user_id' => $request->user()->id,
                'batch_id' => $batchId,
                'total_urls' => count($urls),
                'successful' => $batchResult['summary']['successful'],
                'failed' => $batchResult['summary']['failed'],
                'processing_time_ms' => $processingTime
            ]);

            return $this->success(
                new BatchAnalysisResource($batchResult),
                'Batch analysis completed successfully'
            );

        } catch (\Exception $e) {
            Log::error('Batch SEO analysis failed via API', [
                'user_id' => $request->user()->id,
                'urls' => $request->input('urls'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Send webhook notification for failure if requested
            if ($request->input('webhook_url')) {
                $this->sendWebhookNotification(
                    $request->input('webhook_url'),
                    'batch.failed',
                    [
                        'urls' => $request->input('urls'),
                        'error' => $e->getMessage(),
                        'failed_at' => now()->toISOString()
                    ]
                );
            }

            return $this->serverError('Batch analysis failed: ' . $e->getMessage());
        }
    }

    /**
     * Get analysis history for the authenticated user
     *
     * @group SEO Analysis
     */
    public function history(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $page = $request->query('page', 1);
        $perPage = min($request->query('per_page', 20), 100); // Max 100 per page
        $url = $request->query('url'); // Optional URL filter

        try {
            // Get cached analysis history
            $historyKey = "user_analysis_history:{$userId}";
            $history = Cache::get($historyKey, []);

            // Filter by URL if provided
            if ($url) {
                $history = array_filter($history, function ($analysis) use ($url) {
                    return isset($analysis['url']) && $analysis['url'] === $url;
                });
            }

            // Sort by analyzed_at (most recent first)
            usort($history, function ($a, $b) {
                return strtotime($b['analyzed_at']) - strtotime($a['analyzed_at']);
            });

            // Paginate manually
            $total = count($history);
            $offset = ($page - 1) * $perPage;
            $items = array_slice($history, $offset, $perPage);

            // Create pagination metadata
            $pagination = [
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
                'per_page' => $perPage,
                'total' => $total,
                'from' => $offset + 1,
                'to' => min($offset + $perPage, $total),
            ];

            return $this->success([
                'items' => collect($items)->map(fn($analysis) => new SeoAnalysisResource($analysis)),
                'pagination' => $pagination
            ], 'Analysis history retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to retrieve analysis history', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return $this->serverError('Failed to retrieve analysis history');
        }
    }

    /**
     * Get a specific analysis by ID
     *
     * @group SEO Analysis
     */
    public function getAnalysis(Request $request, string $id): JsonResponse
    {
        $userId = $request->user()->id;

        try {
            // Get analysis from cache
            $analysisKey = "analysis:{$userId}:{$id}";
            $analysis = Cache::get($analysisKey);

            if (!$analysis) {
                return $this->notFound('Analysis not found or has expired');
            }

            return $this->success(
                new SeoAnalysisResource($analysis),
                'Analysis retrieved successfully'
            );

        } catch (\Exception $e) {
            Log::error('Failed to retrieve analysis', [
                'user_id' => $userId,
                'analysis_id' => $id,
                'error' => $e->getMessage()
            ]);

            return $this->serverError('Failed to retrieve analysis');
        }
    }

    /**
     * Get status of an async job (batch analysis or long-running single analysis)
     *
     * @group SEO Analysis
     */
    public function getStatus(Request $request, string $jobId): JsonResponse
    {
        try {
            // Check for batch analysis
            $batchResult = Cache::get("batch_analysis:{$jobId}");
            if ($batchResult) {
                return $this->success(
                    new BatchAnalysisResource($batchResult),
                    'Batch analysis status retrieved'
                );
            }

            // Check for single analysis
            $analysisResult = Cache::get("job_analysis:{$jobId}");
            if ($analysisResult) {
                return $this->success(
                    new SeoAnalysisResource($analysisResult),
                    'Analysis status retrieved'
                );
            }

            return $this->notFound('Job not found or has expired');

        } catch (\Exception $e) {
            Log::error('Failed to retrieve job status', [
                'job_id' => $jobId,
                'error' => $e->getMessage()
            ]);

            return $this->serverError('Failed to retrieve job status');
        }
    }

    /**
     * Get batch analysis status by batch ID
     *
     * @group SEO Analysis
     */
    public function getBatchStatus(Request $request, string $batchId): JsonResponse
    {
        try {
            $batchResult = Cache::get("batch_analysis:{$batchId}");

            if (!$batchResult) {
                return $this->notFound('Batch analysis not found or has expired');
            }

            return $this->success(
                new BatchAnalysisResource($batchResult),
                'Batch analysis status retrieved'
            );

        } catch (\Exception $e) {
            Log::error('Failed to retrieve batch status', [
                'batch_id' => $batchId,
                'error' => $e->getMessage()
            ]);

            return $this->serverError('Failed to retrieve batch status');
        }
    }

    /**
     * Process batch analysis asynchronously (placeholder for queue implementation)
     */
    private function processBatchAsync(string $batchId, array $urls, array $options, ?string $webhookUrl, int $userId): JsonResponse
    {
        // For now, return a processing status
        // In the future, this would dispatch a job to the queue
        $batchResult = [
            'batch_id' => $batchId,
            'submitted_at' => now()->toISOString(),
            'status' => 'processing',
            'summary' => [
                'total_urls' => count($urls),
                'successful' => 0,
                'failed' => 0,
                'processing' => count($urls)
            ],
            'estimated_completion' => now()->addMinutes(count($urls) * 2)->toISOString(),
            'webhook_url' => $webhookUrl
        ];

        // Cache the initial status
        Cache::put("batch_analysis:{$batchId}", $batchResult, now()->addHours(24));

        return $this->success(
            new BatchAnalysisResource($batchResult),
            'Batch analysis started. Check status using the provided batch ID.',
            202 // Accepted
        );
    }

    /**
     * Cache analysis result for history tracking
     */
    private function cacheAnalysisForHistory(int $userId, string $analysisId, array $analysis): void
    {
        // Cache individual analysis (expires in 7 days)
        Cache::put("analysis:{$userId}:{$analysisId}", $analysis, now()->addDays(7));

        // Add to user's history list
        $historyKey = "user_analysis_history:{$userId}";
        $history = Cache::get($historyKey, []);

        // Keep only essential data for history list
        $historyItem = [
            'id' => $analysisId,
            'url' => $analysis['url'],
            'analyzed_at' => $analysis['analyzed_at'],
            'overall_score' => $analysis['scores']['overall_score'],
            'status' => $analysis['status']
        ];

        array_unshift($history, $historyItem);

        // Keep only the latest 100 analyses in history
        $history = array_slice($history, 0, 100);

        Cache::put($historyKey, $history, now()->addDays(30));
    }

    /**
     * Send webhook notification (placeholder implementation)
     */
    private function sendWebhookNotification(string $url, string $event, array $data): void
    {
        // TODO: Implement actual webhook delivery
        // This would typically use a queue job for reliable delivery
        Log::info('Webhook notification would be sent', [
            'url' => $url,
            'event' => $event,
            'data_keys' => array_keys($data)
        ]);
    }
}