<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SeoAnalysisResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->when(isset($this->resource['id']), $this->resource['id'] ?? null),
            'url' => $this->resource['url'],
            'analyzed_at' => $this->resource['analyzed_at'],
            'analysis_duration_ms' => $this->resource['analysis_duration_ms'],
            'status' => [
                'code' => $this->resource['status']['code'],
                'success' => $this->resource['status']['success'],
                'message' => $this->when(
                    isset($this->resource['status']['message']),
                    $this->resource['status']['message']
                ),
            ],
            'crawl_data' => $this->when(
                $request->query('include_crawl_data', false),
                [
                    'html_size' => $this->resource['crawl_data']['html_size'],
                    'load_time_ms' => $this->resource['crawl_data']['load_time_ms'],
                    'javascript_enabled' => $this->resource['crawl_data']['javascript_enabled'],
                    'final_url' => $this->resource['crawl_data']['final_url'],
                ]
            ),
            'seo_elements' => [
                'meta' => [
                    'title' => $this->resource['seo_elements']['meta']['title'] ?? null,
                    'title_length' => $this->when(
                        isset($this->resource['seo_elements']['meta']['title']),
                        strlen($this->resource['seo_elements']['meta']['title'] ?? '')
                    ),
                    'description' => $this->resource['seo_elements']['meta']['description'] ?? null,
                    'description_length' => $this->when(
                        isset($this->resource['seo_elements']['meta']['description']),
                        strlen($this->resource['seo_elements']['meta']['description'] ?? '')
                    ),
                    'keywords' => $this->resource['seo_elements']['meta']['keywords'] ?? null,
                    'robots' => $this->resource['seo_elements']['meta']['robots'] ?? null,
                    'canonical' => $this->resource['seo_elements']['meta']['canonical'] ?? null,
                    'og_tags' => $this->resource['seo_elements']['meta']['og_tags'] ?? [],
                    'twitter_tags' => $this->resource['seo_elements']['meta']['twitter_tags'] ?? [],
                ],
                'headings' => [
                    'h1' => $this->resource['seo_elements']['headings']['h1'] ?? [],
                    'h2' => $this->resource['seo_elements']['headings']['h2'] ?? [],
                    'h3' => $this->resource['seo_elements']['headings']['h3'] ?? [],
                    'h4' => $this->resource['seo_elements']['headings']['h4'] ?? [],
                    'h5' => $this->resource['seo_elements']['headings']['h5'] ?? [],
                    'h6' => $this->resource['seo_elements']['headings']['h6'] ?? [],
                    'structure_valid' => $this->when(
                        isset($this->resource['seo_elements']['headings']['structure_valid']),
                        $this->resource['seo_elements']['headings']['structure_valid']
                    ),
                ],
                'images' => [
                    'total_count' => $this->resource['seo_elements']['images']['total_count'] ?? 0,
                    'with_alt_count' => $this->resource['seo_elements']['images']['with_alt_count'] ?? 0,
                    'without_alt_count' => $this->resource['seo_elements']['images']['without_alt_count'] ?? 0,
                    'details' => $this->when(
                        $request->query('include_image_details', false),
                        $this->resource['seo_elements']['images']['details'] ?? []
                    ),
                ],
                'links' => [
                    'internal_count' => $this->resource['seo_elements']['links']['internal_count'] ?? 0,
                    'external_count' => $this->resource['seo_elements']['links']['external_count'] ?? 0,
                    'broken_count' => $this->resource['seo_elements']['links']['broken_count'] ?? 0,
                    'details' => $this->when(
                        $request->query('include_link_details', false),
                        $this->resource['seo_elements']['links']['details'] ?? []
                    ),
                ],
                'content' => [
                    'word_count' => $this->resource['seo_elements']['content']['word_count'] ?? 0,
                    'reading_time_minutes' => $this->resource['seo_elements']['content']['reading_time_minutes'] ?? 0,
                    'language' => $this->resource['seo_elements']['content']['language'] ?? null,
                ],
            ],
            'scores' => [
                'overall_score' => $this->resource['scores']['overall_score'],
                'meta_score' => $this->resource['scores']['meta_score'] ?? null,
                'content_score' => $this->resource['scores']['content_score'] ?? null,
                'technical_score' => $this->resource['scores']['technical_score'] ?? null,
                'performance_score' => $this->resource['scores']['performance_score'] ?? null,
                'accessibility_score' => $this->resource['scores']['accessibility_score'] ?? null,
            ],
            'page_analysis' => $this->when(
                isset($this->resource['page_analysis']) && $request->query('include_page_analysis', true),
                $this->resource['page_analysis']
            ),
            'recommendations' => $this->when(
                $request->query('include_recommendations', true),
                collect($this->resource['recommendations'] ?? [])->map(function ($recommendation) {
                    return [
                        'type' => $recommendation['type'],
                        'category' => $recommendation['category'],
                        'message' => $recommendation['message'],
                        'impact' => $recommendation['impact'],
                        'fix' => $recommendation['fix'],
                        'priority' => $this->mapImpactToPriority($recommendation['impact']),
                    ];
                })->groupBy('category')
            ),
            'metadata' => [
                'analysis_version' => $this->resource['metadata']['analysis_version'],
                'api_version' => 'v1',
                'processing_time_ms' => $this->when(
                    isset($this->resource['metadata']['processing_time_ms']),
                    $this->resource['metadata']['processing_time_ms']
                ),
            ],
        ];
    }

    /**
     * Map impact level to priority number for sorting
     */
    private function mapImpactToPriority(string $impact): int
    {
        return match ($impact) {
            'high' => 1,
            'medium' => 2,
            'low' => 3,
            default => 4,
        };
    }

    /**
     * Get additional data that should be returned with the resource array.
     */
    public function with(Request $request): array
    {
        return [
            'links' => [
                'self' => $this->when(
                    isset($this->resource['id']),
                    route('api.v1.seo.analysis.show', ['id' => $this->resource['id'] ?? null])
                ),
            ],
        ];
    }
}