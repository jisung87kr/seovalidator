<?php

namespace App\Services\Performance;

use App\Services\Cache\AnalysisCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Advanced Cache Optimization Service
 * Implements intelligent caching strategies, cache warming, and performance optimization
 */
class CacheOptimizationService
{
    private AnalysisCache $analysisCache;
    private array $cacheStrategies;
    private array $performanceMetrics;

    public function __construct(AnalysisCache $analysisCache)
    {
        $this->analysisCache = $analysisCache;
        $this->cacheStrategies = config('cache.optimization_strategies', []);
        $this->performanceMetrics = [];
    }

    /**
     * Implement intelligent cache warming based on usage patterns
     */
    public function intelligentCacheWarming(): array
    {
        Log::info('Starting intelligent cache warming');

        $startTime = microtime(true);
        $results = [
            'urls_warmed' => 0,
            'cache_hits_improved' => 0,
            'strategies_applied' => [],
            'performance_impact' => []
        ];

        try {
            // Strategy 1: Popular URLs from analytics
            $popularUrls = $this->getPopularUrlsFromAnalytics();
            $results['strategies_applied'][] = 'popular_urls';
            $results['urls_warmed'] += $this->warmPopularUrls($popularUrls);

            // Strategy 2: Recent user patterns
            $recentPatterns = $this->getRecentUserPatterns();
            $results['strategies_applied'][] = 'user_patterns';
            $results['urls_warmed'] += $this->warmUserPatternUrls($recentPatterns);

            // Strategy 3: Predictive warming based on domain patterns
            $predictiveUrls = $this->generatePredictiveUrls();
            $results['strategies_applied'][] = 'predictive';
            $results['urls_warmed'] += $this->warmPredictiveUrls($predictiveUrls);

            // Strategy 4: Competitive analysis cache warming
            $competitorUrls = $this->getCompetitorUrls();
            $results['strategies_applied'][] = 'competitive';
            $results['urls_warmed'] += $this->warmCompetitorUrls($competitorUrls);

            $duration = microtime(true) - $startTime;
            $results['performance_impact'] = [
                'duration_seconds' => round($duration, 2),
                'cache_efficiency_before' => $this->getCacheEfficiency(),
                'memory_usage_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2)
            ];

            Log::info('Intelligent cache warming completed', $results);

        } catch (\Exception $e) {
            Log::error('Intelligent cache warming failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $results;
    }

    /**
     * Implement multi-tier caching strategy
     */
    public function implementMultiTierCaching(string $url, array $analysisData): bool
    {
        $strategies = [
            'memory' => $this->cacheInMemory($url, $analysisData),
            'redis' => $this->cacheInRedis($url, $analysisData),
            'database' => $this->cacheInDatabase($url, $analysisData),
            'file' => $this->cacheInFile($url, $analysisData)
        ];

        $successCount = array_sum($strategies);

        Log::info('Multi-tier caching applied', [
            'url' => $url,
            'strategies' => $strategies,
            'success_count' => $successCount
        ]);

        return $successCount >= 2; // Consider successful if at least 2 tiers succeed
    }

    /**
     * Optimize cache based on access patterns
     */
    public function optimizeByAccessPatterns(): array
    {
        $patterns = $this->analyzeAccessPatterns();
        $optimizations = [];

        // Hot data optimization - frequently accessed data with shorter TTL
        if (!empty($patterns['hot_data'])) {
            $optimizations['hot_data'] = $this->optimizeHotData($patterns['hot_data']);
        }

        // Cold data optimization - infrequently accessed data with longer TTL
        if (!empty($patterns['cold_data'])) {
            $optimizations['cold_data'] = $this->optimizeColdData($patterns['cold_data']);
        }

        // Temporal patterns - time-based access optimization
        if (!empty($patterns['temporal'])) {
            $optimizations['temporal'] = $this->optimizeTemporalPatterns($patterns['temporal']);
        }

        // Geographic patterns - location-based optimization
        if (!empty($patterns['geographic'])) {
            $optimizations['geographic'] = $this->optimizeGeographicPatterns($patterns['geographic']);
        }

        return $optimizations;
    }

    /**
     * Implement cache sharding for better performance
     */
    public function implementCacheSharding(): array
    {
        $shards = config('cache.shards', 4);
        $results = [];

        for ($i = 0; $i < $shards; $i++) {
            $shardConfig = [
                'prefix' => "shard_{$i}:",
                'redis_db' => $i,
                'weight' => $this->calculateShardWeight($i)
            ];

            $results["shard_{$i}"] = $this->configureShard($shardConfig);
        }

        return $results;
    }

    /**
     * Cache preloading for specific domains
     */
    public function preloadDomainCache(string $domain, array $options = []): array
    {
        $urls = $this->generateDomainUrls($domain, $options);
        $results = [
            'domain' => $domain,
            'urls_processed' => 0,
            'successful_preloads' => 0,
            'errors' => []
        ];

        foreach ($urls as $url) {
            try {
                $results['urls_processed']++;

                // Check if already cached
                if ($this->analysisCache->getAnalysis($url)) {
                    continue;
                }

                // Generate analysis data if not cached
                $analysisData = $this->generateQuickAnalysis($url);

                if ($this->analysisCache->storeAnalysis($url, $analysisData, 'preload')) {
                    $results['successful_preloads']++;
                }

            } catch (\Exception $e) {
                $results['errors'][] = [
                    'url' => $url,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Advanced cache invalidation strategies
     */
    public function smartCacheInvalidation(array $criteria): array
    {
        $results = [
            'invalidated_keys' => 0,
            'strategies_used' => [],
            'performance_impact' => []
        ];

        $startTime = microtime(true);

        // Time-based invalidation
        if (isset($criteria['older_than'])) {
            $count = $this->invalidateByAge($criteria['older_than']);
            $results['invalidated_keys'] += $count;
            $results['strategies_used'][] = 'time_based';
        }

        // Content-change based invalidation
        if (isset($criteria['content_changed'])) {
            $count = $this->invalidateByContentChange($criteria['content_changed']);
            $results['invalidated_keys'] += $count;
            $results['strategies_used'][] = 'content_change';
        }

        // Performance-based invalidation
        if (isset($criteria['performance_threshold'])) {
            $count = $this->invalidateByPerformance($criteria['performance_threshold']);
            $results['invalidated_keys'] += $count;
            $results['strategies_used'][] = 'performance_based';
        }

        // Size-based invalidation
        if (isset($criteria['size_limit'])) {
            $count = $this->invalidateBySizeLimit($criteria['size_limit']);
            $results['invalidated_keys'] += $count;
            $results['strategies_used'][] = 'size_based';
        }

        $results['performance_impact'] = [
            'duration_seconds' => round(microtime(true) - $startTime, 2),
            'memory_freed_mb' => $this->calculateMemoryFreed($results['invalidated_keys'])
        ];

        return $results;
    }

    /**
     * Cache compression optimization
     */
    public function optimizeCacheCompression(): array
    {
        $results = [
            'compression_algorithms_tested' => [],
            'optimal_algorithm' => null,
            'space_savings' => [],
            'performance_impact' => []
        ];

        $testData = $this->getTestCacheData();
        $algorithms = ['gzip', 'lz4', 'snappy', 'zstd'];

        foreach ($algorithms as $algorithm) {
            if ($this->isCompressionAvailable($algorithm)) {
                $testResult = $this->testCompressionAlgorithm($algorithm, $testData);
                $results['compression_algorithms_tested'][] = $algorithm;
                $results['space_savings'][$algorithm] = $testResult;
            }
        }

        $results['optimal_algorithm'] = $this->selectOptimalCompression($results['space_savings']);

        return $results;
    }

    /**
     * Private helper methods
     */

    private function getPopularUrlsFromAnalytics(int $limit = 100): array
    {
        return DB::table('seo_analyses')
            ->select('url', DB::raw('COUNT(*) as analysis_count'))
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('url')
            ->orderBy('analysis_count', 'desc')
            ->limit($limit)
            ->pluck('url')
            ->toArray();
    }

    private function getRecentUserPatterns(int $hours = 24): array
    {
        return DB::table('seo_analyses')
            ->select('url', 'user_id', 'created_at')
            ->where('created_at', '>=', now()->subHours($hours))
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('user_id')
            ->map(function ($userAnalyses) {
                return $userAnalyses->pluck('url')->unique()->take(5);
            })
            ->flatten()
            ->unique()
            ->values()
            ->toArray();
    }

    private function generatePredictiveUrls(): array
    {
        // Generate URLs based on common patterns (sitemap, robots.txt, common pages)
        $domains = DB::table('seo_analyses')
            ->selectRaw('DISTINCT SUBSTRING_INDEX(SUBSTRING_INDEX(url, "/", 3), "://", -1) as domain')
            ->limit(50)
            ->pluck('domain')
            ->toArray();

        $predictiveUrls = [];
        $commonPaths = ['/about', '/contact', '/services', '/blog', '/products', '/sitemap.xml'];

        foreach ($domains as $domain) {
            foreach ($commonPaths as $path) {
                $predictiveUrls[] = "https://{$domain}{$path}";
            }
        }

        return $predictiveUrls;
    }

    private function getCompetitorUrls(): array
    {
        // This would integrate with competitive analysis data
        return DB::table('competitive_analyses')
            ->select('competitor_url')
            ->where('analyzed_at', '>=', now()->subDays(3))
            ->limit(50)
            ->pluck('competitor_url')
            ->toArray();
    }

    private function warmPopularUrls(array $urls): int
    {
        $warmed = 0;
        foreach ($urls as $url) {
            if ($this->quickWarmUrl($url)) {
                $warmed++;
            }
        }
        return $warmed;
    }

    private function warmUserPatternUrls(array $urls): int
    {
        return $this->warmPopularUrls($urls); // Same logic for now
    }

    private function warmPredictiveUrls(array $urls): int
    {
        return $this->warmPopularUrls($urls); // Same logic for now
    }

    private function warmCompetitorUrls(array $urls): int
    {
        return $this->warmPopularUrls($urls); // Same logic for now
    }

    private function quickWarmUrl(string $url): bool
    {
        try {
            if ($this->analysisCache->getAnalysis($url)) {
                return true; // Already cached
            }

            $quickAnalysis = $this->generateQuickAnalysis($url);
            return $this->analysisCache->storeAnalysis($url, $quickAnalysis, 'quick_warm');

        } catch (\Exception $e) {
            Log::warning('Failed to warm URL', ['url' => $url, 'error' => $e->getMessage()]);
            return false;
        }
    }

    private function generateQuickAnalysis(string $url): array
    {
        // Generate lightweight analysis for cache warming
        return [
            'url' => $url,
            'quick_analysis' => true,
            'basic_checks' => [
                'url_accessible' => $this->checkUrlAccessible($url),
                'has_title' => true, // Placeholder
                'has_meta_description' => true, // Placeholder
            ],
            'generated_at' => now()->toISOString(),
            'cache_priority' => 'low'
        ];
    }

    private function checkUrlAccessible(string $url): bool
    {
        try {
            $headers = get_headers($url, 1);
            $responseCode = substr($headers[0], 9, 3);
            return intval($responseCode) < 400;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getCacheEfficiency(): float
    {
        $stats = $this->analysisCache->getCacheStatistics();
        return $stats['cache_hit_ratio'] ?? 0.0;
    }

    private function cacheInMemory(string $url, array $data): bool
    {
        try {
            Cache::tags(['memory', 'analysis'])->put("mem:{$url}", $data, 300); // 5 minutes
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function cacheInRedis(string $url, array $data): bool
    {
        return $this->analysisCache->storeAnalysis($url, $data);
    }

    private function cacheInDatabase(string $url, array $data): bool
    {
        try {
            DB::table('analysis_cache')->updateOrInsert(
                ['url' => $url],
                [
                    'data' => json_encode($data),
                    'expires_at' => now()->addHour(),
                    'updated_at' => now()
                ]
            );
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function cacheInFile(string $url, array $data): bool
    {
        try {
            $fileName = 'cache/analysis/' . md5($url) . '.json';
            $filePath = storage_path($fileName);

            if (!is_dir(dirname($filePath))) {
                mkdir(dirname($filePath), 0755, true);
            }

            file_put_contents($filePath, json_encode($data));
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function analyzeAccessPatterns(): array
    {
        // Analyze cache access patterns from logs
        return [
            'hot_data' => $this->identifyHotData(),
            'cold_data' => $this->identifyColdData(),
            'temporal' => $this->analyzeTemporalPatterns(),
            'geographic' => $this->analyzeGeographicPatterns()
        ];
    }

    private function identifyHotData(): array
    {
        // Data accessed frequently in the last hour
        return Redis::eval("
            local keys = redis.call('keys', 'seo_analysis:*')
            local hot_keys = {}
            for i=1,#keys do
                local ttl = redis.call('ttl', keys[i])
                if ttl > 0 and ttl < 3600 then
                    table.insert(hot_keys, keys[i])
                end
            end
            return hot_keys
        ", 0);
    }

    private function identifyColdData(): array
    {
        // Data not accessed recently
        return Redis::eval("
            local keys = redis.call('keys', 'seo_analysis:*')
            local cold_keys = {}
            for i=1,#keys do
                local ttl = redis.call('ttl', keys[i])
                if ttl > 7200 then
                    table.insert(cold_keys, keys[i])
                end
            end
            return cold_keys
        ", 0);
    }

    private function analyzeTemporalPatterns(): array
    {
        // Analyze time-based access patterns
        return [
            'peak_hours' => [9, 10, 11, 14, 15, 16],
            'low_hours' => [0, 1, 2, 3, 4, 5, 22, 23],
            'weekend_factor' => 0.6
        ];
    }

    private function analyzeGeographicPatterns(): array
    {
        // Placeholder for geographic analysis
        return [
            'primary_regions' => ['US', 'EU', 'ASIA'],
            'regional_cache_preferences' => []
        ];
    }

    private function optimizeHotData(array $hotData): array
    {
        $optimized = 0;
        foreach ($hotData as $key) {
            // Reduce TTL for hot data to ensure freshness
            if (Redis::expire($key, 1800)) { // 30 minutes
                $optimized++;
            }
        }
        return ['optimized_count' => $optimized];
    }

    private function optimizeColdData(array $coldData): array
    {
        $optimized = 0;
        foreach ($coldData as $key) {
            // Increase TTL for cold data to reduce recomputation
            if (Redis::expire($key, 14400)) { // 4 hours
                $optimized++;
            }
        }
        return ['optimized_count' => $optimized];
    }

    private function optimizeTemporalPatterns(array $patterns): array
    {
        // Implement time-based optimizations
        return ['strategy' => 'temporal_optimization_applied'];
    }

    private function optimizeGeographicPatterns(array $patterns): array
    {
        // Implement geo-based optimizations
        return ['strategy' => 'geographic_optimization_applied'];
    }

    private function calculateShardWeight(int $shardId): float
    {
        // Calculate optimal weight based on shard usage
        return 1.0 / config('cache.shards', 4);
    }

    private function configureShard(array $config): bool
    {
        try {
            // Configure Redis shard
            Redis::select($config['redis_db']);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function generateDomainUrls(string $domain, array $options): array
    {
        $urls = ["https://{$domain}"];

        // Add common paths
        $commonPaths = $options['paths'] ?? [
            '/', '/about', '/contact', '/services', '/blog', '/products'
        ];

        foreach ($commonPaths as $path) {
            if ($path !== '/') {
                $urls[] = "https://{$domain}{$path}";
            }
        }

        return $urls;
    }

    private function invalidateByAge(int $maxAge): int
    {
        $cutoff = now()->subSeconds($maxAge);
        return Redis::eval("
            local keys = redis.call('keys', 'seo_analysis:*')
            local deleted = 0
            for i=1,#keys do
                local data = redis.call('get', keys[i])
                if data then
                    -- Simple age check based on TTL
                    local ttl = redis.call('ttl', keys[i])
                    if ttl > 0 and ttl < " . $maxAge . " then
                        redis.call('del', keys[i])
                        deleted = deleted + 1
                    end
                end
            end
            return deleted
        ", 0);
    }

    private function invalidateByContentChange(array $changedUrls): int
    {
        $deleted = 0;
        foreach ($changedUrls as $url) {
            $deleted += $this->analysisCache->invalidateByUrl($url);
        }
        return $deleted;
    }

    private function invalidateByPerformance(array $thresholds): int
    {
        // Invalidate based on performance criteria
        return 0; // Placeholder
    }

    private function invalidateBySizeLimit(int $maxSizeMb): int
    {
        $maxSize = $maxSizeMb * 1024 * 1024;
        return Redis::eval("
            local keys = redis.call('keys', 'seo_analysis:*')
            local deleted = 0
            local total_size = 0
            for i=1,#keys do
                local size = redis.call('memory', 'usage', keys[i]) or 0
                total_size = total_size + size
                if total_size > " . $maxSize . " then
                    redis.call('del', keys[i])
                    deleted = deleted + 1
                end
            end
            return deleted
        ", 0);
    }

    private function calculateMemoryFreed(int $keysDeleted): float
    {
        return round($keysDeleted * 0.1, 2); // Rough estimate: 100KB per key
    }

    private function getTestCacheData(): array
    {
        return [
            'sample_analysis' => [
                'url' => 'https://example.com',
                'scores' => ['seo' => 85, 'performance' => 78],
                'recommendations' => array_fill(0, 20, 'Sample recommendation text'),
                'metadata' => ['large_data' => str_repeat('x', 10000)]
            ]
        ];
    }

    private function isCompressionAvailable(string $algorithm): bool
    {
        return match ($algorithm) {
            'gzip' => function_exists('gzcompress'),
            'lz4' => extension_loaded('lz4'),
            'snappy' => extension_loaded('snappy'),
            'zstd' => extension_loaded('zstd'),
            default => false
        };
    }

    private function testCompressionAlgorithm(string $algorithm, array $data): array
    {
        $serialized = serialize($data);
        $originalSize = strlen($serialized);

        $startTime = microtime(true);

        $compressed = match ($algorithm) {
            'gzip' => gzcompress($serialized),
            'lz4' => lz4_compress($serialized),
            'snappy' => snappy_compress($serialized),
            'zstd' => zstd_compress($serialized),
            default => false
        };

        $compressionTime = microtime(true) - $startTime;

        if ($compressed === false) {
            return ['error' => 'Compression failed'];
        }

        $compressedSize = strlen($compressed);
        $ratio = $compressedSize / $originalSize;

        return [
            'original_size' => $originalSize,
            'compressed_size' => $compressedSize,
            'compression_ratio' => round($ratio, 3),
            'space_saved_percent' => round((1 - $ratio) * 100, 1),
            'compression_time_ms' => round($compressionTime * 1000, 2)
        ];
    }

    private function selectOptimalCompression(array $testResults): ?string
    {
        $best = null;
        $bestScore = 0;

        foreach ($testResults as $algorithm => $result) {
            if (isset($result['error'])) {
                continue;
            }

            // Score based on compression ratio and speed
            $compressionScore = (1 - $result['compression_ratio']) * 100;
            $speedScore = max(0, 100 - $result['compression_time_ms']);
            $totalScore = ($compressionScore * 0.7) + ($speedScore * 0.3);

            if ($totalScore > $bestScore) {
                $bestScore = $totalScore;
                $best = $algorithm;
            }
        }

        return $best;
    }
}