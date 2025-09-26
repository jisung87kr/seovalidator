<?php

namespace App\Http\Controllers;

use App\Services\Monitoring\ProductionMonitoringService;
use App\Services\Performance\DatabaseOptimizationService;
use App\Services\Cache\AnalysisCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

/**
 * Health Check Controller
 * Provides various health check endpoints for monitoring system status
 */
class HealthController extends Controller
{
    private ProductionMonitoringService $monitoringService;
    private DatabaseOptimizationService $dbOptimization;
    private AnalysisCache $analysisCache;

    public function __construct(
        ProductionMonitoringService $monitoringService,
        DatabaseOptimizationService $dbOptimization,
        AnalysisCache $analysisCache
    ) {
        $this->monitoringService = $monitoringService;
        $this->dbOptimization = $dbOptimization;
        $this->analysisCache = $analysisCache;
    }

    /**
     * Basic health check endpoint
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'application' => config('app.name'),
            'version' => config('app.version', '1.0.0'),
            'environment' => app()->environment()
        ]);
    }

    /**
     * Comprehensive health check
     */
    public function comprehensive(): JsonResponse
    {
        try {
            $healthStatus = $this->monitoringService->performHealthCheck();

            $httpStatus = match ($healthStatus['overall_status']) {
                'healthy' => 200,
                'degraded' => 200, // Still operational but with warnings
                'unhealthy' => 503,
                'critical' => 503,
                default => 503
            };

            return response()->json($healthStatus, $httpStatus);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Health check failed',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ], 503);
        }
    }

    /**
     * Database health check
     */
    public function database(): JsonResponse
    {
        try {
            $startTime = microtime(true);

            // Test basic connection
            $result = DB::select('SELECT 1 as test');
            $connectionTime = (microtime(true) - $startTime) * 1000;

            // Test write operation
            $startTime = microtime(true);
            DB::table('users')->where('id', 0)->count(); // Non-existent user
            $queryTime = (microtime(true) - $startTime) * 1000;

            // Get database metrics
            $metrics = $this->dbOptimization->monitorDatabasePerformance();

            return response()->json([
                'status' => 'healthy',
                'connection_time_ms' => round($connectionTime, 2),
                'query_time_ms' => round($queryTime, 2),
                'metrics' => $metrics,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ], 503);
        }
    }

    /**
     * Cache health check
     */
    public function cache(): JsonResponse
    {
        try {
            $testKey = 'health_check_' . time();
            $testValue = 'test_value';

            // Test Redis cache
            $redisStartTime = microtime(true);
            Redis::set($testKey, $testValue, 'EX', 60);
            $redisValue = Redis::get($testKey);
            $redisTime = (microtime(true) - $redisStartTime) * 1000;
            Redis::del($testKey);

            // Test Laravel cache
            $cacheStartTime = microtime(true);
            Cache::put($testKey, $testValue, 60);
            $cacheValue = Cache::get($testKey);
            $cacheTime = (microtime(true) - $cacheStartTime) * 1000;
            Cache::forget($testKey);

            // Get cache statistics
            $cacheStats = $this->analysisCache->getCacheStatistics();

            $status = ($redisValue === $testValue && $cacheValue === $testValue) ? 'healthy' : 'unhealthy';

            return response()->json([
                'status' => $status,
                'redis_time_ms' => round($redisTime, 2),
                'cache_time_ms' => round($cacheTime, 2),
                'statistics' => $cacheStats,
                'timestamp' => now()->toISOString()
            ], $status === 'healthy' ? 200 : 503);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ], 503);
        }
    }

    /**
     * Queue health check
     */
    public function queue(): JsonResponse
    {
        try {
            $queueSize = \Queue::size();
            $failedJobs = DB::table('failed_jobs')->count();

            $status = 'healthy';
            $warnings = [];

            if ($queueSize > 100) {
                $status = 'degraded';
                $warnings[] = 'High queue depth';
            }

            if ($failedJobs > 10) {
                $status = 'unhealthy';
                $warnings[] = 'Too many failed jobs';
            }

            return response()->json([
                'status' => $status,
                'queue_size' => $queueSize,
                'failed_jobs' => $failedJobs,
                'warnings' => $warnings,
                'timestamp' => now()->toISOString()
            ], $status === 'unhealthy' ? 503 : 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ], 503);
        }
    }

    /**
     * Storage health check
     */
    public function storage(): JsonResponse
    {
        try {
            // Check disk space
            $freeBytes = disk_free_space('/');
            $totalBytes = disk_total_space('/');
            $usedPercent = (($totalBytes - $freeBytes) / $totalBytes) * 100;

            // Test file write
            $testFile = storage_path('app/health_check_' . time() . '.tmp');
            $startTime = microtime(true);
            file_put_contents($testFile, 'health check test');
            $writeTime = (microtime(true) - $startTime) * 1000;

            // Test file read
            $startTime = microtime(true);
            $content = file_get_contents($testFile);
            $readTime = (microtime(true) - $startTime) * 1000;

            // Clean up
            unlink($testFile);

            $status = 'healthy';
            $warnings = [];

            if ($usedPercent > 90) {
                $status = 'critical';
                $warnings[] = 'Disk usage critical';
            } elseif ($usedPercent > 80) {
                $status = 'warning';
                $warnings[] = 'Disk usage high';
            }

            if ($writeTime > 100 || $readTime > 100) {
                $warnings[] = 'Slow disk I/O';
            }

            return response()->json([
                'status' => $status,
                'disk_usage_percent' => round($usedPercent, 2),
                'free_space_gb' => round($freeBytes / 1024 / 1024 / 1024, 2),
                'write_time_ms' => round($writeTime, 2),
                'read_time_ms' => round($readTime, 2),
                'warnings' => $warnings,
                'timestamp' => now()->toISOString()
            ], $status === 'critical' ? 503 : 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ], 503);
        }
    }

    /**
     * External services health check
     */
    public function external(): JsonResponse
    {
        try {
            $services = [];

            // Test Google PageSpeed API
            $services['google_pagespeed'] = $this->testGooglePageSpeedApi();

            // Test Moz API
            $services['moz_api'] = $this->testMozApi();

            // Determine overall status
            $overallStatus = 'healthy';
            foreach ($services as $service) {
                if ($service['status'] === 'unhealthy') {
                    $overallStatus = 'degraded';
                    break;
                }
            }

            return response()->json([
                'status' => $overallStatus,
                'services' => $services,
                'timestamp' => now()->toISOString()
            ], $overallStatus === 'unhealthy' ? 503 : 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ], 503);
        }
    }

    /**
     * Application performance metrics
     */
    public function performance(): JsonResponse
    {
        try {
            $metrics = $this->monitoringService->monitorPerformanceMetrics();

            return response()->json([
                'status' => 'success',
                'performance_metrics' => $metrics,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ], 500);
        }
    }

    /**
     * SEO analysis specific health check
     */
    public function seoAnalysis(): JsonResponse
    {
        try {
            $metrics = $this->monitoringService->monitorSeoAnalysisPerformance();

            // Test a quick analysis
            $testStartTime = microtime(true);
            $testUrl = 'https://httpbin.org/html'; // Simple test URL

            // Simulate quick analysis check
            $headers = get_headers($testUrl, 1);
            $isAccessible = strpos($headers[0], '200') !== false;

            $testDuration = (microtime(true) - $testStartTime) * 1000;

            $status = 'healthy';
            if (!$isAccessible) {
                $status = 'degraded';
            }

            return response()->json([
                'status' => $status,
                'test_analysis_time_ms' => round($testDuration, 2),
                'test_url_accessible' => $isAccessible,
                'seo_metrics' => $metrics,
                'timestamp' => now()->toISOString()
            ], $status === 'unhealthy' ? 503 : 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ], 503);
        }
    }

    /**
     * Readiness probe for Kubernetes
     */
    public function ready(): JsonResponse
    {
        try {
            // Check critical dependencies
            $checks = [
                'database' => $this->quickDatabaseCheck(),
                'cache' => $this->quickCacheCheck(),
            ];

            $ready = true;
            foreach ($checks as $check) {
                if (!$check) {
                    $ready = false;
                    break;
                }
            }

            return response()->json([
                'ready' => $ready,
                'checks' => $checks,
                'timestamp' => now()->toISOString()
            ], $ready ? 200 : 503);

        } catch (\Exception $e) {
            return response()->json([
                'ready' => false,
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ], 503);
        }
    }

    /**
     * Liveness probe for Kubernetes
     */
    public function live(): JsonResponse
    {
        // Basic liveness check - just verify the application can respond
        return response()->json([
            'alive' => true,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Detailed system metrics
     */
    public function metrics(): JsonResponse
    {
        try {
            $metrics = [
                'memory' => [
                    'usage_bytes' => memory_get_usage(true),
                    'peak_bytes' => memory_get_peak_usage(true),
                    'limit_bytes' => $this->getMemoryLimit(),
                ],
                'php' => [
                    'version' => PHP_VERSION,
                    'extensions' => get_loaded_extensions(),
                ],
                'system' => [
                    'load_average' => sys_getloadavg(),
                    'uptime' => $this->getSystemUptime(),
                ],
                'application' => [
                    'version' => config('app.version', '1.0.0'),
                    'environment' => app()->environment(),
                    'debug' => config('app.debug'),
                ],
            ];

            return response()->json([
                'metrics' => $metrics,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ], 500);
        }
    }

    /**
     * Private helper methods
     */

    private function testGooglePageSpeedApi(): array
    {
        try {
            $apiKey = config('services.google.pagespeed_api_key');
            if (empty($apiKey)) {
                return ['status' => 'not_configured', 'message' => 'API key not configured'];
            }

            // Test with a simple request timeout
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'method' => 'GET'
                ]
            ]);

            $testUrl = "https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=https://google.com&key={$apiKey}";
            $response = file_get_contents($testUrl, false, $context);

            return ['status' => $response ? 'healthy' : 'unhealthy'];

        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'error' => $e->getMessage()];
        }
    }

    private function testMozApi(): array
    {
        try {
            $accessId = config('services.moz.access_id');
            $secretKey = config('services.moz.secret_key');

            if (empty($accessId) || empty($secretKey)) {
                return ['status' => 'not_configured', 'message' => 'API credentials not configured'];
            }

            // For Moz API, we'll just check if credentials are configured
            // A full test would require making an actual API call
            return ['status' => 'configured'];

        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'error' => $e->getMessage()];
        }
    }

    private function quickDatabaseCheck(): bool
    {
        try {
            DB::select('SELECT 1');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function quickCacheCheck(): bool
    {
        try {
            Cache::put('health_check', 'test', 60);
            $value = Cache::get('health_check');
            Cache::forget('health_check');
            return $value === 'test';
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');
        if ($limit === '-1') {
            return PHP_INT_MAX;
        }

        return $this->parsePhpSize($limit);
    }

    private function parsePhpSize(string $size): int
    {
        $unit = strtoupper(substr($size, -1));
        $value = (int) substr($size, 0, -1);

        return match ($unit) {
            'G' => $value * 1024 * 1024 * 1024,
            'M' => $value * 1024 * 1024,
            'K' => $value * 1024,
            default => (int) $size
        };
    }

    private function getSystemUptime(): ?string
    {
        if (file_exists('/proc/uptime')) {
            $uptime = file_get_contents('/proc/uptime');
            $seconds = (float) explode(' ', $uptime)[0];
            return gmdate('H:i:s', $seconds);
        }

        return null;
    }
}