<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BatchAnalysisResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'batch_id' => $this->resource['batch_id'] ?? null,
            'submitted_at' => $this->resource['submitted_at'] ?? now()->toISOString(),
            'completed_at' => $this->resource['completed_at'] ?? null,
            'status' => $this->resource['status'] ?? 'processing',
            'summary' => [
                'total_urls' => $this->resource['summary']['total_urls'],
                'successful' => $this->resource['summary']['successful'],
                'failed' => $this->resource['summary']['failed'],
                'processing' => $this->when(
                    isset($this->resource['summary']['processing']),
                    $this->resource['summary']['processing']
                ),
                'success_rate' => $this->when(
                    $this->resource['summary']['total_urls'] > 0,
                    round(($this->resource['summary']['successful'] / $this->resource['summary']['total_urls']) * 100, 2)
                ),
            ],
            'results' => $this->when(
                isset($this->resource['results']) && $request->query('include_results', true),
                collect($this->resource['results'])->map(function ($analysis, $url) {
                    return [
                        'url' => $url,
                        'status' => 'completed',
                        'analysis' => new SeoAnalysisResource($analysis),
                    ];
                })->values()
            ),
            'errors' => $this->when(
                isset($this->resource['errors']) && !empty($this->resource['errors']),
                collect($this->resource['errors'])->map(function ($error, $url) {
                    return [
                        'url' => $url,
                        'status' => 'failed',
                        'error' => $error,
                    ];
                })->values()
            ),
            'processing_time_ms' => $this->when(
                isset($this->resource['processing_time_ms']),
                $this->resource['processing_time_ms']
            ),
            'estimated_completion' => $this->when(
                $this->resource['status'] === 'processing' && isset($this->resource['estimated_completion']),
                $this->resource['estimated_completion']
            ),
            'webhook_url' => $this->when(
                isset($this->resource['webhook_url']),
                $this->resource['webhook_url']
            ),
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     */
    public function with(Request $request): array
    {
        return [
            'links' => [
                'self' => $this->when(
                    isset($this->resource['batch_id']),
                    route('api.v1.seo.batch.show', ['batch_id' => $this->resource['batch_id']])
                ),
                'status' => $this->when(
                    isset($this->resource['batch_id']),
                    route('api.v1.seo.status', ['jobId' => $this->resource['batch_id']])
                ),
            ],
        ];
    }
}