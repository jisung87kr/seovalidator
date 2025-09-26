<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Services\SeoAnalyzerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SeoAnalysisTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_requires_authentication_for_seo_analysis()
    {
        $response = $this->postJson('/api/v1/seo/analyze', [
            'url' => 'https://example.com'
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_validates_url_parameter_for_analysis()
    {
        Sanctum::actingAs($this->user);

        // Missing URL
        $response = $this->postJson('/api/v1/seo/analyze', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);

        // Invalid URL
        $response = $this->postJson('/api/v1/seo/analyze', [
            'url' => 'not-a-valid-url'
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);

        // URL too long
        $response = $this->postJson('/api/v1/seo/analyze', [
            'url' => 'https://example.com/' . str_repeat('a', 2100)
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    /** @test */
    public function it_validates_options_parameter()
    {
        Sanctum::actingAs($this->user);

        // Invalid timeout value
        $response = $this->postJson('/api/v1/seo/analyze', [
            'url' => 'https://example.com',
            'options' => [
                'timeout' => 3 // Too low
            ]
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['options.timeout']);

        // Invalid timeout value (too high)
        $response = $this->postJson('/api/v1/seo/analyze', [
            'url' => 'https://example.com',
            'options' => [
                'timeout' => 100 // Too high
            ]
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['options.timeout']);
    }

    /** @test */
    public function it_can_analyze_a_single_url_successfully()
    {
        Sanctum::actingAs($this->user);

        // Mock the SEO analyzer service
        $mockAnalysis = [
            'url' => 'https://example.com',
            'analyzed_at' => now()->toISOString(),
            'analysis_duration_ms' => 1500,
            'status' => [
                'code' => 200,
                'success' => true
            ],
            'seo_elements' => [
                'meta' => [
                    'title' => 'Example Domain',
                    'description' => 'This domain is for use in illustrative examples.',
                    'keywords' => null,
                    'robots' => null,
                    'canonical' => 'https://example.com',
                    'og_tags' => [],
                    'twitter_tags' => []
                ],
                'headings' => [
                    'h1' => ['Example Domain'],
                    'h2' => [],
                    'h3' => [],
                    'h4' => [],
                    'h5' => [],
                    'h6' => [],
                    'structure_valid' => true
                ],
                'images' => [
                    'total_count' => 0,
                    'with_alt_count' => 0,
                    'without_alt_count' => 0
                ],
                'links' => [
                    'internal_count' => 1,
                    'external_count' => 1,
                    'broken_count' => 0
                ],
                'content' => [
                    'word_count' => 50,
                    'reading_time_minutes' => 0.2,
                    'language' => 'en'
                ]
            ],
            'scores' => [
                'overall_score' => 75.5,
                'meta_score' => 80.0,
                'content_score' => 70.0,
                'technical_score' => 75.0,
                'performance_score' => 80.0,
                'accessibility_score' => 70.0
            ],
            'recommendations' => [
                [
                    'type' => 'warning',
                    'category' => 'content',
                    'message' => 'Content is too short',
                    'impact' => 'medium',
                    'fix' => 'Add more valuable content (aim for 300+ words)'
                ]
            ],
            'metadata' => [
                'analysis_version' => '1.0.0',
                'options' => []
            ]
        ];

        $this->mock(SeoAnalyzerService::class, function ($mock) use ($mockAnalysis) {
            $mock->shouldReceive('analyze')
                ->once()
                ->with('https://example.com', [])
                ->andReturn($mockAnalysis);
        });

        $response = $this->postJson('/api/v1/seo/analyze', [
            'url' => 'https://example.com'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'url',
                    'analyzed_at',
                    'analysis_duration_ms',
                    'status' => ['code', 'success'],
                    'seo_elements' => [
                        'meta' => [
                            'title',
                            'title_length',
                            'description',
                            'description_length'
                        ],
                        'headings',
                        'images',
                        'links',
                        'content'
                    ],
                    'scores' => [
                        'overall_score',
                        'meta_score',
                        'content_score',
                        'technical_score',
                        'performance_score',
                        'accessibility_score'
                    ],
                    'recommendations',
                    'metadata'
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'SEO analysis completed successfully',
                'data' => [
                    'url' => 'https://example.com',
                    'scores' => [
                        'overall_score' => 75.5
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_analyze_batch_urls_successfully()
    {
        Sanctum::actingAs($this->user);

        $urls = ['https://example.com', 'https://example.org'];

        $mockBatchResult = [
            'results' => [
                'https://example.com' => [
                    'url' => 'https://example.com',
                    'analyzed_at' => now()->toISOString(),
                    'scores' => ['overall_score' => 75.5],
                    'status' => ['code' => 200, 'success' => true],
                    'seo_elements' => [],
                    'recommendations' => [],
                    'metadata' => ['analysis_version' => '1.0.0']
                ]
            ],
            'errors' => [
                'https://example.org' => 'Connection timeout'
            ],
            'summary' => [
                'total_urls' => 2,
                'successful' => 1,
                'failed' => 1
            ]
        ];

        $this->mock(SeoAnalyzerService::class, function ($mock) use ($urls, $mockBatchResult) {
            $mock->shouldReceive('analyzeBatch')
                ->once()
                ->with($urls, [])
                ->andReturn($mockBatchResult);
        });

        $response = $this->postJson('/api/v1/seo/analyze/batch', [
            'urls' => $urls
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'batch_id',
                    'submitted_at',
                    'completed_at',
                    'status',
                    'summary' => [
                        'total_urls',
                        'successful',
                        'failed',
                        'success_rate'
                    ],
                    'results',
                    'errors'
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Batch analysis completed successfully'
            ]);
    }

    /** @test */
    public function it_validates_batch_analysis_parameters()
    {
        Sanctum::actingAs($this->user);

        // Missing URLs
        $response = $this->postJson('/api/v1/seo/analyze/batch', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['urls']);

        // Empty URLs array
        $response = $this->postJson('/api/v1/seo/analyze/batch', [
            'urls' => []
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['urls']);

        // Too many URLs
        $response = $this->postJson('/api/v1/seo/analyze/batch', [
            'urls' => array_fill(0, 11, 'https://example.com')
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['urls']);

        // Invalid URL in array
        $response = $this->postJson('/api/v1/seo/analyze/batch', [
            'urls' => ['https://example.com', 'not-a-url']
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['urls.1']);
    }

    /** @test */
    public function it_can_get_analysis_history()
    {
        Sanctum::actingAs($this->user);

        // Mock cached history data
        $historyData = [
            [
                'id' => 'analysis-1',
                'url' => 'https://example.com',
                'analyzed_at' => now()->subHour()->toISOString(),
                'overall_score' => 75.5,
                'status' => ['code' => 200, 'success' => true]
            ],
            [
                'id' => 'analysis-2',
                'url' => 'https://example.org',
                'analyzed_at' => now()->subHours(2)->toISOString(),
                'overall_score' => 82.0,
                'status' => ['code' => 200, 'success' => true]
            ]
        ];

        Cache::shouldReceive('get')
            ->with("user_analysis_history:{$this->user->id}", [])
            ->andReturn($historyData);

        $response = $this->getJson('/api/v1/seo/history');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'items',
                    'pagination' => [
                        'current_page',
                        'last_page',
                        'per_page',
                        'total',
                        'from',
                        'to'
                    ]
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Analysis history retrieved successfully'
            ]);
    }

    /** @test */
    public function it_can_get_specific_analysis_by_id()
    {
        Sanctum::actingAs($this->user);

        $analysisId = 'test-analysis-id';
        $analysisData = [
            'id' => $analysisId,
            'url' => 'https://example.com',
            'analyzed_at' => now()->toISOString(),
            'scores' => ['overall_score' => 75.5],
            'status' => ['code' => 200, 'success' => true],
            'seo_elements' => [],
            'recommendations' => [],
            'metadata' => ['analysis_version' => '1.0.0']
        ];

        Cache::shouldReceive('get')
            ->with("analysis:{$this->user->id}:{$analysisId}")
            ->andReturn($analysisData);

        $response = $this->getJson("/api/v1/seo/history/{$analysisId}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Analysis retrieved successfully',
                'data' => [
                    'id' => $analysisId,
                    'url' => 'https://example.com'
                ]
            ]);
    }

    /** @test */
    public function it_returns_404_for_non_existent_analysis()
    {
        Sanctum::actingAs($this->user);

        $analysisId = 'non-existent-id';

        Cache::shouldReceive('get')
            ->with("analysis:{$this->user->id}:{$analysisId}")
            ->andReturn(null);

        $response = $this->getJson("/api/v1/seo/history/{$analysisId}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Analysis not found or has expired'
            ]);
    }

    /** @test */
    public function it_handles_analysis_service_exceptions()
    {
        Sanctum::actingAs($this->user);

        $this->mock(SeoAnalyzerService::class, function ($mock) {
            $mock->shouldReceive('analyze')
                ->once()
                ->andThrow(new \Exception('Service unavailable'));
        });

        $response = $this->postJson('/api/v1/seo/analyze', [
            'url' => 'https://example.com'
        ]);

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Analysis failed: Service unavailable'
            ]);
    }

    /** @test */
    public function it_can_analyze_with_custom_options()
    {
        Sanctum::actingAs($this->user);

        $options = [
            'force_refresh' => true,
            'include_quality_analysis' => true,
            'javascript_enabled' => true,
            'timeout' => 30
        ];

        $this->mock(SeoAnalyzerService::class, function ($mock) use ($options) {
            $mock->shouldReceive('analyze')
                ->once()
                ->with('https://example.com', $options)
                ->andReturn([
                    'url' => 'https://example.com',
                    'analyzed_at' => now()->toISOString(),
                    'scores' => ['overall_score' => 75.5],
                    'status' => ['code' => 200, 'success' => true],
                    'seo_elements' => [],
                    'recommendations' => [],
                    'metadata' => ['analysis_version' => '1.0.0', 'options' => $options]
                ]);
        });

        $response = $this->postJson('/api/v1/seo/analyze', [
            'url' => 'https://example.com',
            'options' => $options
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'SEO analysis completed successfully'
            ]);
    }

    /** @test */
    public function it_returns_async_response_for_batch_analysis_with_async_flag()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/seo/analyze/batch', [
            'urls' => ['https://example.com'],
            'async' => true
        ]);

        $response->assertStatus(202)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'batch_id',
                    'submitted_at',
                    'status',
                    'summary',
                    'estimated_completion'
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Batch analysis started. Check status using the provided batch ID.',
                'data' => [
                    'status' => 'processing'
                ]
            ]);
    }
}