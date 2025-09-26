<?php

namespace Tests\Unit\Services\Cache;

use Tests\TestCase;
use App\Services\Cache\AnalysisCache;
use Illuminate\Support\Facades\Redis;
use Mockery;

class AnalysisCacheTest extends TestCase
{
    private AnalysisCache $analysisCache;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock Redis facade
        Redis::shouldReceive('ping')
            ->andReturn('PONG')
            ->byDefault();

        $this->analysisCache = new AnalysisCache();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_store_analysis_successfully()
    {
        // Arrange
        $url = 'https://example.com';
        $analysisData = [
            'overall_score' => 85,
            'grade' => 'B',
            'category_scores' => [
                'title' => ['score' => 90]
            ]
        ];
        $type = 'full_analysis';
        $context = ['industry' => 'e-commerce'];

        Redis::shouldReceive('setex')
            ->once()
            ->andReturn(true);

        // Act
        $result = $this->analysisCache->storeAnalysis($url, $analysisData, $type, $context);

        // Assert
        $this->assertTrue($result);
    }

    public function test_get_analysis_cache_hit()
    {
        // Arrange
        $url = 'https://example.com';
        $cachedData = [
            'data' => [
                'overall_score' => 85,
                'grade' => 'B'
            ],
            'metadata' => [
                'type' => 'full_analysis',
                'cached_at' => now()->toISOString(),
                'version' => '1.0.0'
            ]
        ];

        Redis::shouldReceive('get')
            ->once()
            ->andReturn(serialize($cachedData));

        // Act
        $result = $this->analysisCache->getAnalysis($url);

        // Assert
        $this->assertEquals($cachedData['data'], $result);
    }

    public function test_get_analysis_cache_miss()
    {
        // Arrange
        $url = 'https://example.com';

        Redis::shouldReceive('get')
            ->once()
            ->andReturn(null);

        // Act
        $result = $this->analysisCache->getAnalysis($url);

        // Assert
        $this->assertNull($result);
    }

    public function test_store_batch_analysis()
    {
        // Arrange
        $batchId = 'batch_123';
        $results = [
            'url1' => ['score' => 85],
            'url2' => ['score' => 72]
        ];

        Redis::shouldReceive('setex')
            ->once()
            ->andReturn(true);

        // Act
        $result = $this->analysisCache->storeBatchAnalysis($batchId, $results);

        // Assert
        $this->assertTrue($result);
    }

    public function test_get_batch_analysis()
    {
        // Arrange
        $batchId = 'batch_123';
        $batchData = [
            'batch_id' => $batchId,
            'results' => [
                'url1' => ['score' => 85],
                'url2' => ['score' => 72]
            ],
            'total_count' => 2
        ];

        Redis::shouldReceive('get')
            ->once()
            ->andReturn(serialize($batchData));

        // Act
        $result = $this->analysisCache->getBatchAnalysis($batchId);

        // Assert
        $this->assertEquals($batchData, $result);
    }

    public function test_store_user_analysis_with_preferences()
    {
        // Arrange
        $userId = 123;
        $url = 'https://example.com';
        $analysisData = ['score' => 85];
        $userPreferences = ['cache_duration' => 'long'];

        Redis::shouldReceive('setex')
            ->once()
            ->withArgs([Mockery::any(), 7200, Mockery::any()]) // Should use 'long' duration
            ->andReturn(true);

        // Act
        $result = $this->analysisCache->storeUserAnalysis($userId, $url, $analysisData, $userPreferences);

        // Assert
        $this->assertTrue($result);
    }

    public function test_invalidate_by_url()
    {
        // Arrange
        $url = 'https://example.com';
        $keys = ['key1', 'key2', 'key3'];

        Redis::shouldReceive('keys')
            ->once()
            ->andReturn($keys);

        Redis::shouldReceive('del')
            ->once()
            ->with($keys)
            ->andReturn(3);

        // Act
        $result = $this->analysisCache->invalidateByUrl($url);

        // Assert
        $this->assertEquals(3, $result);
    }

    public function test_invalidate_by_domain()
    {
        // Arrange
        $domain = 'example.com';
        $keys = ['domain_key1', 'domain_key2'];

        Redis::shouldReceive('keys')
            ->once()
            ->andReturn($keys);

        Redis::shouldReceive('del')
            ->once()
            ->with($keys)
            ->andReturn(2);

        // Act
        $result = $this->analysisCache->invalidateByDomain($domain);

        // Assert
        $this->assertEquals(2, $result);
    }

    public function test_get_cache_statistics()
    {
        // Arrange
        $redisInfo = [
            'used_memory' => 1024000,
            'used_memory_human' => '1.0M',
            'connected_clients' => 5,
            'keyspace_hits' => 100,
            'keyspace_misses' => 20
        ];
        $keys = ['key1', 'key2', 'key3'];

        Redis::shouldReceive('info')
            ->once()
            ->andReturn($redisInfo);

        Redis::shouldReceive('keys')
            ->once()
            ->andReturn($keys);

        Redis::shouldReceive('memory')
            ->times(3)
            ->andReturn(1024);

        // Act
        $result = $this->analysisCache->getCacheStatistics();

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_keys', $result);
        $this->assertArrayHasKey('total_size_bytes', $result);
        $this->assertArrayHasKey('redis_info', $result);
        $this->assertArrayHasKey('cache_hit_ratio', $result);

        $this->assertEquals(3, $result['total_keys']);
        $this->assertEquals(83.33, $result['cache_hit_ratio']); // 100/(100+20) * 100
    }

    public function test_warmup_cache_with_provider()
    {
        // Arrange
        $urls = ['https://example1.com', 'https://example2.com'];
        $analysisProvider = function($url) {
            return ['score' => 85, 'url' => $url];
        };

        // Mock getAnalysis to return null (cache miss)
        Redis::shouldReceive('get')
            ->twice()
            ->andReturn(null);

        // Mock storeAnalysis
        Redis::shouldReceive('setex')
            ->twice()
            ->andReturn(true);

        // Act
        $result = $this->analysisCache->warmupCache($urls, $analysisProvider);

        // Assert
        $this->assertEquals(2, $result['success']);
        $this->assertEquals(0, $result['failed']);
        $this->assertEquals(0, $result['skipped']);
    }

    public function test_warmup_cache_skips_existing_entries()
    {
        // Arrange
        $urls = ['https://example1.com', 'https://example2.com'];
        $existingData = [
            'data' => ['score' => 80],
            'metadata' => ['cached_at' => now()->toISOString()]
        ];

        // Mock getAnalysis to return existing data (cache hit)
        Redis::shouldReceive('get')
            ->twice()
            ->andReturn(serialize($existingData));

        // Act
        $result = $this->analysisCache->warmupCache($urls);

        // Assert
        $this->assertEquals(0, $result['success']);
        $this->assertEquals(0, $result['failed']);
        $this->assertEquals(2, $result['skipped']);
    }

    public function test_cleanup_expired_entries()
    {
        // Arrange
        $keys = ['expired_key1', 'valid_key1', 'expired_key2'];

        Redis::shouldReceive('keys')
            ->once()
            ->andReturn($keys);

        Redis::shouldReceive('ttl')
            ->with('expired_key1')
            ->andReturn(-2); // Expired

        Redis::shouldReceive('ttl')
            ->with('valid_key1')
            ->andReturn(3600); // Still valid

        Redis::shouldReceive('ttl')
            ->with('expired_key2')
            ->andReturn(-1); // No expiry set

        Redis::shouldReceive('del')
            ->with('expired_key1')
            ->andReturn(1);

        Redis::shouldReceive('del')
            ->with('expired_key2')
            ->andReturn(1);

        // Act
        $result = $this->analysisCache->cleanupExpiredEntries();

        // Assert
        $this->assertEquals(2, $result); // Should clean 2 expired entries
    }

    public function test_compression_for_large_data()
    {
        // Arrange
        $url = 'https://example.com';
        $largeAnalysisData = array_fill(0, 1000, 'Large data content that should be compressed when stored in cache');

        Redis::shouldReceive('setex')
            ->once()
            ->withArgs(function($key, $ttl, $data) {
                return str_starts_with($data, 'gz:'); // Should be compressed
            })
            ->andReturn(true);

        // Act
        $result = $this->analysisCache->storeAnalysis($url, $largeAnalysisData);

        // Assert
        $this->assertTrue($result);
    }

    public function test_cache_key_generation_consistency()
    {
        // Arrange
        $url1 = 'https://example.com';
        $url2 = 'https://example.com';
        $context = ['industry' => 'tech'];

        // Mock Redis calls to capture the keys
        $capturedKeys = [];
        Redis::shouldReceive('setex')
            ->twice()
            ->withArgs(function($key, $ttl, $data) use (&$capturedKeys) {
                $capturedKeys[] = $key;
                return true;
            })
            ->andReturn(true);

        // Act
        $this->analysisCache->storeAnalysis($url1, ['score' => 85], 'full_analysis', $context);
        $this->analysisCache->storeAnalysis($url2, ['score' => 85], 'full_analysis', $context);

        // Assert
        $this->assertEquals($capturedKeys[0], $capturedKeys[1]); // Same URL and context should generate same key
    }

    public function test_ttl_adjustment_based_on_content_type()
    {
        // Arrange
        $url = 'https://news-site.com/breaking-news';
        $analysisData = ['score' => 90];
        $context = ['content_type' => 'news'];

        Redis::shouldReceive('setex')
            ->once()
            ->withArgs([Mockery::any(), 900, Mockery::any()]) // News content should have short TTL (15 minutes)
            ->andReturn(true);

        // Act
        $result = $this->analysisCache->storeAnalysis($url, $analysisData, 'full_analysis', $context);

        // Assert
        $this->assertTrue($result);
    }

    public function test_cache_version_compatibility_check()
    {
        // Arrange
        $url = 'https://example.com';
        $outdatedData = [
            'data' => ['score' => 85],
            'metadata' => [
                'cached_at' => now()->subHours(1)->toISOString(),
                'version' => '0.9.0' // Older version
            ]
        ];

        Redis::shouldReceive('get')
            ->once()
            ->andReturn(serialize($outdatedData));

        Redis::shouldReceive('del')
            ->once()
            ->andReturn(1); // Cache should be invalidated

        // Act
        $result = $this->analysisCache->getAnalysis($url);

        // Assert
        $this->assertNull($result); // Should return null due to version incompatibility
    }
}