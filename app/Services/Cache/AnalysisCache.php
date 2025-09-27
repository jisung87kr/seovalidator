<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Exception;

/**
 * Redis Caching Layer for SEO Analysis Results
 *
 * Provides high-performance caching for SEO analysis data with
 * smart cache invalidation, compression, and analytics.
 */
class AnalysisCache
{
    private string $keyPrefix = 'seo_analysis:';
    private int $defaultTtl = 3600; // 1 hour in seconds
    private bool $compressionEnabled = true;
    private int $maxCacheSize = 1048576; // 1MB max cache entry size

    /**
     * Cache TTL configurations by content type
     */
    private array $ttlConfigs = [
        'full_analysis' => 3600,      // 1 hour - complete SEO analysis
        'score_only' => 1800,         // 30 minutes - score calculations only
        'meta_data' => 7200,          // 2 hours - meta information
        'technical_audit' => 14400,   // 4 hours - technical SEO data
        'performance_metrics' => 900, // 15 minutes - performance data (changes frequently)
        'crawl_data' => 21600,        // 6 hours - crawled page data
        'recommendations' => 1800,    // 30 minutes - SEO recommendations
        'competitive_data' => 86400   // 24 hours - competitor analysis
    ];

    /**
     * Cache key patterns for different analysis types
     */
    private array $keyPatterns = [
        'url_analysis' => 'url:{hash}',
        'domain_analysis' => 'domain:{domain}',
        'keyword_analysis' => 'keywords:{hash}',
        'batch_analysis' => 'batch:{batch_id}',
        'user_analysis' => 'user:{user_id}:url:{hash}',
        'competitor_analysis' => 'competitor:{domain}:{competitor}'
    ];

    public function __construct()
    {
        // Verify cache connection only if using Redis
        if (config('cache.default') === 'redis') {
            $this->verifyConnection();
        }
    }

    /**
     * Store SEO analysis results in cache
     */
    public function storeAnalysis(string $url, array $analysisData, string $type = 'full_analysis', array $context = []): bool
    {
        try {
            $cacheKey = $this->generateCacheKey('url_analysis', $url, $context);
            $ttl = $this->getTtl($type, $context);

            // Add metadata to analysis data
            $cacheData = $this->prepareCacheData($analysisData, $type, $url, $context);

            // Compress if data is large
            $serializedData = $this->serializeData($cacheData);

            // Store in Redis with appropriate TTL
            $success = Cache::put($cacheKey, $serializedData, $ttl);

            if ($success) {
                $this->trackCacheOperation('store', $cacheKey, strlen($serializedData));
                Log::debug('Analysis cached successfully', [
                    'url' => $url,
                    'type' => $type,
                    'cache_key' => $cacheKey,
                    'ttl' => $ttl,
                    'size_bytes' => strlen($serializedData)
                ]);
            }

            return $success;

        } catch (Exception $e) {
            Log::error('Failed to store analysis in cache', [
                'url' => $url,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Retrieve SEO analysis results from cache
     */
    public function getAnalysis(string $url, array $context = []): ?array
    {
        try {
            $cacheKey = $this->generateCacheKey('url_analysis', $url, $context);
            $cachedData = Cache::get($cacheKey);

            if ($cachedData === null) {
                $this->trackCacheOperation('miss', $cacheKey);
                return null;
            }

            $unserializedData = $this->unserializeData($cachedData);

            // Check if cached data is still valid
            if (!$this->isCacheValid($unserializedData)) {
                $this->invalidateKey($cacheKey);
                return null;
            }

            $this->trackCacheOperation('hit', $cacheKey, strlen($cachedData));

            Log::debug('Analysis retrieved from cache', [
                'url' => $url,
                'cache_key' => $cacheKey,
                'cached_at' => $unserializedData['metadata']['cached_at'] ?? 'unknown'
            ]);

            return $unserializedData['data'];

        } catch (Exception $e) {
            Log::error('Failed to retrieve analysis from cache', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Store batch analysis results
     */
    public function storeBatchAnalysis(string $batchId, array $results): bool
    {
        try {
            $cacheKey = $this->generateCacheKey('batch_analysis', $batchId);
            $ttl = $this->getTtl('full_analysis');

            $cacheData = [
                'batch_id' => $batchId,
                'results' => $results,
                'total_count' => count($results),
                'created_at' => now()->toISOString(),
                'expires_at' => now()->addSeconds($ttl)->toISOString()
            ];

            $serializedData = $this->serializeData($cacheData);
            $success = Cache::put($cacheKey, $serializedData, $ttl);

            if ($success) {
                $this->trackCacheOperation('batch_store', $cacheKey, strlen($serializedData));
            }

            return $success;

        } catch (Exception $e) {
            Log::error('Failed to store batch analysis in cache', [
                'batch_id' => $batchId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Retrieve batch analysis results
     */
    public function getBatchAnalysis(string $batchId): ?array
    {
        try {
            $cacheKey = $this->generateCacheKey('batch_analysis', $batchId);
            $cachedData = Cache::get($cacheKey);

            if ($cachedData === null) {
                return null;
            }

            return $this->unserializeData($cachedData);

        } catch (Exception $e) {
            Log::error('Failed to retrieve batch analysis from cache', [
                'batch_id' => $batchId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Cache user-specific analysis with personalized TTL
     */
    public function storeUserAnalysis(int $userId, string $url, array $analysisData, array $userPreferences = []): bool
    {
        try {
            $context = ['user_id' => $userId];
            $cacheKey = $this->generateCacheKey('user_analysis', $url, $context);

            // Adjust TTL based on user preferences
            $ttl = $this->getUserSpecificTtl($userPreferences);

            $cacheData = $this->prepareCacheData($analysisData, 'user_analysis', $url, $context);
            $cacheData['user_preferences'] = $userPreferences;

            $serializedData = $this->serializeData($cacheData);
            return Cache::put($cacheKey, $serializedData, $ttl);

        } catch (Exception $e) {
            Log::error('Failed to store user analysis in cache', [
                'user_id' => $userId,
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Invalidate cache entries by URL pattern
     */
    public function invalidateByUrl(string $url): int
    {
        try {
            $pattern = $this->keyPrefix . "*" . $this->hashUrl($url) . "*";
            // File cache doesn't support pattern matching, return empty array
            if (config('cache.default') !== 'redis') {
                return true;
            }
            $keys = \Illuminate\Support\Facades\Redis::keys($pattern);

            if (empty($keys)) {
                return 0;
            }

            $deleted = \Illuminate\Support\Facades\Redis::del($keys);

            Log::info('Cache invalidated for URL', [
                'url' => $url,
                'keys_deleted' => $deleted,
                'pattern' => $pattern
            ]);

            return $deleted;

        } catch (Exception $e) {
            Log::error('Failed to invalidate cache by URL', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Invalidate cache entries by domain
     */
    public function invalidateByDomain(string $domain): int
    {
        try {
            $pattern = $this->keyPrefix . "*{$domain}*";
            // File cache doesn't support pattern matching, return empty array
            if (config('cache.default') !== 'redis') {
                return true;
            }
            $keys = \Illuminate\Support\Facades\Redis::keys($pattern);

            if (empty($keys)) {
                return 0;
            }

            $deleted = \Illuminate\Support\Facades\Redis::del($keys);

            Log::info('Cache invalidated for domain', [
                'domain' => $domain,
                'keys_deleted' => $deleted
            ]);

            return $deleted;

        } catch (Exception $e) {
            Log::error('Failed to invalidate cache by domain', [
                'domain' => $domain,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Get cache statistics and health metrics
     */
    public function getCacheStatistics(): array
    {
        try {
            // File cache doesn't support Redis info
            if (config('cache.default') !== 'redis') {
                return [
                    'total_keys' => 0,
                    'total_size' => 0,
                    'hit_ratio' => 0,
                    'memory_usage' => 0,
                    'avg_size' => 0
                ];
            }
            $info = \Illuminate\Support\Facades\Redis::info();
            $pattern = $this->keyPrefix . "*";
            // File cache doesn't support pattern matching, return empty array
            if (config('cache.default') !== 'redis') {
                return true;
            }
            $keys = \Illuminate\Support\Facades\Redis::keys($pattern);

            $totalSize = 0;
            $keysByType = [];

            foreach ($keys as $key) {
                $size = \Illuminate\Support\Facades\Redis::memory('usage', $key) ?? 0;
                $totalSize += $size;

                // Extract type from key pattern
                $type = $this->extractTypeFromKey($key);
                $keysByType[$type] = ($keysByType[$type] ?? 0) + 1;
            }

            return [
                'total_keys' => count($keys),
                'total_size_bytes' => $totalSize,
                'total_size_mb' => round($totalSize / 1024 / 1024, 2),
                'keys_by_type' => $keysByType,
                'redis_info' => [
                    'used_memory' => $info['used_memory'] ?? 0,
                    'used_memory_human' => $info['used_memory_human'] ?? '0B',
                    'connected_clients' => $info['connected_clients'] ?? 0,
                    'total_connections_received' => $info['total_connections_received'] ?? 0,
                    'keyspace_hits' => $info['keyspace_hits'] ?? 0,
                    'keyspace_misses' => $info['keyspace_misses'] ?? 0
                ],
                'cache_hit_ratio' => $this->calculateHitRatio($info)
            ];

        } catch (Exception $e) {
            Log::error('Failed to get cache statistics', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Warm up cache with popular URLs
     */
    public function warmupCache(array $urls, callable $analysisProvider = null): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'skipped' => 0
        ];

        foreach ($urls as $url) {
            try {
                // Check if already cached
                if ($this->getAnalysis($url) !== null) {
                    $results['skipped']++;
                    continue;
                }

                // Get fresh analysis data
                if ($analysisProvider && is_callable($analysisProvider)) {
                    $analysisData = $analysisProvider($url);

                    if ($analysisData && $this->storeAnalysis($url, $analysisData)) {
                        $results['success']++;
                    } else {
                        $results['failed']++;
                    }
                } else {
                    $results['skipped']++;
                }

            } catch (Exception $e) {
                Log::error('Cache warmup failed for URL', [
                    'url' => $url,
                    'error' => $e->getMessage()
                ]);
                $results['failed']++;
            }
        }

        Log::info('Cache warmup completed', $results);
        return $results;
    }

    /**
     * Clean expired cache entries
     */
    public function cleanupExpiredEntries(): int
    {
        try {
            $pattern = $this->keyPrefix . "*";
            // File cache doesn't support pattern matching, return empty array
            if (config('cache.default') !== 'redis') {
                return true;
            }
            $keys = \Illuminate\Support\Facades\Redis::keys($pattern);
            $cleaned = 0;

            foreach ($keys as $key) {
                $ttl = \Illuminate\Support\Facades\Redis::ttl($key);

                // Remove keys that are expired or have invalid TTL
                if ($ttl === -1 || $ttl === -2) {
                    if (\Illuminate\Support\Facades\Redis::del($key)) {
                        $cleaned++;
                    }
                }
            }

            Log::info('Cache cleanup completed', ['entries_cleaned' => $cleaned]);
            return $cleaned;

        } catch (Exception $e) {
            Log::error('Cache cleanup failed', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Private helper methods
     */

    private function verifyConnection(): void
    {
        try {
            if (config('cache.default') === 'redis') {
                \Illuminate\Support\Facades\Redis::ping();
            }
        } catch (Exception $e) {
            Log::error('Redis connection failed', ['error' => $e->getMessage()]);
            throw new Exception('Redis cache is not available');
        }
    }

    private function generateCacheKey(string $pattern, string $identifier, array $context = []): string
    {
        $keyTemplate = $this->keyPatterns[$pattern] ?? 'default:{hash}';

        $replacements = [
            '{hash}' => $this->hashUrl($identifier),
            '{domain}' => parse_url($identifier, PHP_URL_HOST) ?? $identifier,
            '{user_id}' => $context['user_id'] ?? 'anonymous',
            '{batch_id}' => $identifier,
            '{competitor}' => $context['competitor'] ?? 'unknown'
        ];

        $key = str_replace(array_keys($replacements), array_values($replacements), $keyTemplate);

        // Add context hash if provided
        if (!empty($context)) {
            $contextHash = substr(md5(serialize($context)), 0, 8);
            $key .= ":{$contextHash}";
        }

        return $this->keyPrefix . $key;
    }

    private function hashUrl(string $url): string
    {
        return substr(md5($url), 0, 16);
    }

    private function getTtl(string $type, array $context = []): int
    {
        $baseTtl = $this->ttlConfigs[$type] ?? $this->defaultTtl;

        // Adjust TTL based on context
        if (isset($context['priority']) && $context['priority'] === 'high') {
            $baseTtl = min($baseTtl, 1800); // Max 30 minutes for high priority
        }

        if (isset($context['content_type'])) {
            $contentType = $context['content_type'];
            if ($contentType === 'news') {
                $baseTtl = min($baseTtl, 900); // 15 minutes for news content
            } elseif ($contentType === 'static') {
                $baseTtl = max($baseTtl, 7200); // Min 2 hours for static content
            }
        }

        return $baseTtl;
    }

    private function getUserSpecificTtl(array $userPreferences): int
    {
        $cacheDuration = $userPreferences['cache_duration'] ?? 'normal';

        return match ($cacheDuration) {
            'short' => 900,    // 15 minutes
            'long' => 7200,    // 2 hours
            'extended' => 21600, // 6 hours
            default => $this->defaultTtl // 1 hour
        };
    }

    private function prepareCacheData(array $analysisData, string $type, string $url, array $context): array
    {
        return [
            'data' => $analysisData,
            'metadata' => [
                'type' => $type,
                'url' => $url,
                'cached_at' => now()->toISOString(),
                'version' => config('app.version', '1.0.0'),
                'context' => $context
            ]
        ];
    }

    private function serializeData(array $data): string
    {
        $serialized = serialize($data);

        // Compress if enabled and data is large enough
        if ($this->compressionEnabled && strlen($serialized) > 1024) {
            $compressed = gzcompress($serialized);
            if ($compressed !== false && strlen($compressed) < strlen($serialized)) {
                return 'gz:' . $compressed;
            }
        }

        return $serialized;
    }

    private function unserializeData(string $data): array
    {
        // Check if data is compressed
        if (str_starts_with($data, 'gz:')) {
            $compressed = substr($data, 3);
            $decompressed = gzuncompress($compressed);
            if ($decompressed === false) {
                throw new Exception('Failed to decompress cache data');
            }
            $data = $decompressed;
        }

        $unserialized = unserialize($data);
        if ($unserialized === false) {
            throw new Exception('Failed to unserialize cache data');
        }

        return $unserialized;
    }

    private function isCacheValid(array $cacheData): bool
    {
        $metadata = $cacheData['metadata'] ?? [];

        // Check version compatibility
        $cacheVersion = $metadata['version'] ?? '0.0.0';
        $currentVersion = config('app.version', '1.0.0');

        if (version_compare($cacheVersion, $currentVersion, '<')) {
            return false; // Cache is from older version
        }

        // Add other validation logic as needed
        return true;
    }

    private function invalidateKey(string $key): bool
    {
        try {
            return Cache::forget($key);
        } catch (Exception $e) {
            Log::error('Failed to invalidate cache key', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function trackCacheOperation(string $operation, string $key, int $size = 0): void
    {
        // This could be expanded to store detailed analytics
        Log::debug('Cache operation tracked', [
            'operation' => $operation,
            'key' => $key,
            'size' => $size,
            'timestamp' => now()->toISOString()
        ]);
    }

    private function extractTypeFromKey(string $key): string
    {
        $keyWithoutPrefix = str_replace($this->keyPrefix, '', $key);

        foreach ($this->keyPatterns as $type => $pattern) {
            $regexPattern = '/^' . str_replace(['{hash}', '{domain}', '{user_id}', '{batch_id}', '{competitor}'],
                                               ['[a-f0-9]+', '[^:]+', '\d+', '[^:]+', '[^:]+'],
                                               preg_quote($pattern, '/')) . '/';

            if (preg_match($regexPattern, $keyWithoutPrefix)) {
                return $type;
            }
        }

        return 'unknown';
    }

    private function calculateHitRatio(array $redisInfo): float
    {
        $hits = $redisInfo['keyspace_hits'] ?? 0;
        $misses = $redisInfo['keyspace_misses'] ?? 0;
        $total = $hits + $misses;

        return $total > 0 ? round(($hits / $total) * 100, 2) : 0.0;
    }
}