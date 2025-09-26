<?php

namespace App\Services\Monitoring;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\Storage\DatabaseEntriesRepository;

/**
 * Production Monitoring Service
 * Comprehensive monitoring for performance, errors, and system health
 */
class ProductionMonitoringService
{
    private array $healthChecks = [];
    private array $performanceMetrics = [];
    private array $alertThresholds = [];

    public function __construct()
    {
        $this->alertThresholds = config('monitoring.alert_thresholds', [
            'response_time_ms' => 1000,
            'error_rate_percent' => 5,
            'queue_depth' => 100,
            'memory_usage_percent' => 85,
            'disk_usage_percent' => 90
        ]);
    }

    /**
     * Comprehensive system health check
     */
    public function performHealthCheck(): array
    {
        Log::info('Starting production health check');

        $healthStatus = [
            'overall_status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'checks' => [],
            'alerts' => [],
            'performance_summary' => []
        ];

        try {
            // Database health check
            $healthStatus['checks']['database'] = $this->checkDatabaseHealth();

            // Redis cache health check
            $healthStatus['checks']['cache'] = $this->checkCacheHealth();

            // Queue health check
            $healthStatus['checks']['queue'] = $this->checkQueueHealth();

            // External services health check
            $healthStatus['checks']['external_services'] = $this->checkExternalServicesHealth();

            // Application performance check
            $healthStatus['checks']['performance'] = $this->checkApplicationPerformance();

            // Security health check
            $healthStatus['checks']['security'] = $this->checkSecurityHealth();

            // Storage health check
            $healthStatus['checks']['storage'] = $this->checkStorageHealth();

            // Memory and resource check
            $healthStatus['checks']['resources'] = $this->checkResourceUsage();

            // Determine overall status
            $healthStatus['overall_status'] = $this->determineOverallStatus($healthStatus['checks']);

            // Generate alerts if needed
            $healthStatus['alerts'] = $this->generateHealthAlerts($healthStatus['checks']);

            // Performance summary
            $healthStatus['performance_summary'] = $this->generatePerformanceSummary();

            Log::info('Production health check completed', [
                'overall_status' => $healthStatus['overall_status'],
                'alerts_count' => count($healthStatus['alerts'])
            ]);

        } catch (\Exception $e) {
            $healthStatus['overall_status'] = 'critical';
            $healthStatus['alerts'][] = [
                'level' => 'critical',
                'message' => 'Health check system failure: ' . $e->getMessage(),
                'timestamp' => now()->toISOString()
            ];

            Log::error('Health check system failure', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $healthStatus;
    }

    /**
     * Monitor application performance metrics
     */
    public function monitorPerformanceMetrics(): array
    {
        $metrics = [
            'response_times' => $this->getResponseTimeMetrics(),
            'error_rates' => $this->getErrorRateMetrics(),
            'throughput' => $this->getThroughputMetrics(),
            'resource_usage' => $this->getResourceUsageMetrics(),
            'cache_performance' => $this->getCachePerformanceMetrics(),
            'database_performance' => $this->getDatabasePerformanceMetrics(),
            'queue_performance' => $this->getQueuePerformanceMetrics()
        ];

        // Store metrics for trending
        $this->storePerformanceMetrics($metrics);

        // Check for performance alerts
        $alerts = $this->checkPerformanceAlerts($metrics);

        return [
            'metrics' => $metrics,
            'alerts' => $alerts,
            'recorded_at' => now()->toISOString()
        ];
    }

    /**
     * Analyze error patterns and trends
     */
    public function analyzeErrorPatterns(): array
    {
        $errorAnalysis = [
            'error_summary' => $this->getErrorSummary(),
            'error_trends' => $this->getErrorTrends(),
            'critical_errors' => $this->getCriticalErrors(),
            'error_patterns' => $this->identifyErrorPatterns(),
            'resolution_recommendations' => []
        ];

        // Generate recommendations based on error patterns
        foreach ($errorAnalysis['error_patterns'] as $pattern) {
            $recommendations = $this->generateErrorResolutionRecommendations($pattern);
            $errorAnalysis['resolution_recommendations'] = array_merge(
                $errorAnalysis['resolution_recommendations'],
                $recommendations
            );
        }

        return $errorAnalysis;
    }

    /**
     * Monitor SEO analysis performance specifically
     */
    public function monitorSeoAnalysisPerformance(): array
    {
        return [
            'analysis_throughput' => $this->getSeoAnalysisThroughput(),
            'analysis_success_rate' => $this->getSeoAnalysisSuccessRate(),
            'analysis_performance' => $this->getSeoAnalysisPerformance(),
            'user_satisfaction' => $this->getSeoUserSatisfactionMetrics(),
            'api_performance' => $this->getSeoApiPerformance()
        ];
    }

    /**
     * Generate monitoring dashboard data
     */
    public function generateDashboardData(): array
    {
        return [
            'system_overview' => $this->getSystemOverview(),
            'performance_charts' => $this->getPerformanceChartData(),
            'recent_alerts' => $this->getRecentAlerts(),
            'seo_analysis_stats' => $this->getSeoAnalysisStats(),
            'uptime_stats' => $this->getUptimeStats(),
            'user_activity' => $this->getUserActivityStats()
        ];
    }

    /**
     * Private health check methods
     */

    private function checkDatabaseHealth(): array
    {
        try {
            $startTime = microtime(true);
            $result = DB::select('SELECT 1 as test');
            $responseTime = (microtime(true) - $startTime) * 1000;

            // Check connection pool
            $connections = $this->getDatabaseConnectionInfo();

            // Check table sizes
            $tableSizes = $this->getTableSizes();

            return [
                'status' => 'healthy',
                'response_time_ms' => round($responseTime, 2),
                'connections' => $connections,
                'table_sizes' => $tableSizes,
                'last_check' => now()->toISOString()
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'last_check' => now()->toISOString()
            ];
        }
    }

    private function checkCacheHealth(): array
    {
        try {
            $startTime = microtime(true);
            Cache::put('health_check', 'test', 60);
            $value = Cache::get('health_check');
            $responseTime = (microtime(true) - $startTime) * 1000;

            $stats = $this->getCacheStats();

            return [
                'status' => $value === 'test' ? 'healthy' : 'degraded',
                'response_time_ms' => round($responseTime, 2),
                'stats' => $stats,
                'last_check' => now()->toISOString()
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'last_check' => now()->toISOString()
            ];
        }
    }

    private function checkQueueHealth(): array
    {
        try {
            $queueSize = Queue::size();
            $failedJobs = DB::table('failed_jobs')->count();

            $status = 'healthy';
            if ($queueSize > $this->alertThresholds['queue_depth']) {
                $status = 'degraded';
            }
            if ($failedJobs > 10) {
                $status = 'unhealthy';
            }

            return [
                'status' => $status,
                'queue_size' => $queueSize,
                'failed_jobs' => $failedJobs,
                'last_check' => now()->toISOString()
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'last_check' => now()->toISOString()
            ];
        }
    }

    private function checkExternalServicesHealth(): array
    {
        $services = [
            'google_pagespeed' => $this->checkGooglePageSpeedHealth(),
            'moz_api' => $this->checkMozApiHealth(),
        ];

        $overallStatus = 'healthy';
        foreach ($services as $service) {
            if ($service['status'] === 'unhealthy') {
                $overallStatus = 'degraded';
                break;
            }
        }

        return [
            'status' => $overallStatus,
            'services' => $services,
            'last_check' => now()->toISOString()
        ];
    }

    private function checkApplicationPerformance(): array
    {
        // Get recent performance data from Telescope
        $recentEntries = $this->getRecentTelescopeEntries();
        $avgResponseTime = $this->calculateAverageResponseTime($recentEntries);
        $errorRate = $this->calculateErrorRate($recentEntries);

        $status = 'healthy';
        if ($avgResponseTime > $this->alertThresholds['response_time_ms']) {
            $status = 'degraded';
        }
        if ($errorRate > $this->alertThresholds['error_rate_percent']) {
            $status = 'unhealthy';
        }

        return [
            'status' => $status,
            'avg_response_time_ms' => $avgResponseTime,
            'error_rate_percent' => $errorRate,
            'request_count' => count($recentEntries),
            'last_check' => now()->toISOString()
        ];
    }

    private function checkSecurityHealth(): array
    {
        $securityChecks = [
            'failed_login_attempts' => $this->getFailedLoginAttempts(),
            'suspicious_requests' => $this->getSuspiciousRequests(),
            'rate_limit_violations' => $this->getRateLimitViolations(),
        ];

        $status = 'healthy';
        if ($securityChecks['failed_login_attempts'] > 50 ||
            $securityChecks['suspicious_requests'] > 20) {
            $status = 'warning';
        }

        return [
            'status' => $status,
            'checks' => $securityChecks,
            'last_check' => now()->toISOString()
        ];
    }

    private function checkStorageHealth(): array
    {
        $diskUsage = disk_free_space('/') / disk_total_space('/');
        $diskUsagePercent = (1 - $diskUsage) * 100;

        $status = 'healthy';
        if ($diskUsagePercent > $this->alertThresholds['disk_usage_percent']) {
            $status = 'critical';
        } elseif ($diskUsagePercent > ($this->alertThresholds['disk_usage_percent'] - 10)) {
            $status = 'warning';
        }

        return [
            'status' => $status,
            'disk_usage_percent' => round($diskUsagePercent, 2),
            'free_space_gb' => round(disk_free_space('/') / 1024 / 1024 / 1024, 2),
            'last_check' => now()->toISOString()
        ];
    }

    private function checkResourceUsage(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->getMemoryLimit();
        $memoryUsagePercent = ($memoryUsage / $memoryLimit) * 100;

        $cpuUsage = $this->getCpuUsage();

        $status = 'healthy';
        if ($memoryUsagePercent > $this->alertThresholds['memory_usage_percent']) {
            $status = 'critical';
        } elseif ($cpuUsage > 80) {
            $status = 'warning';
        }

        return [
            'status' => $status,
            'memory_usage_percent' => round($memoryUsagePercent, 2),
            'memory_usage_mb' => round($memoryUsage / 1024 / 1024, 2),
            'cpu_usage_percent' => $cpuUsage,
            'last_check' => now()->toISOString()
        ];
    }

    /**
     * Helper methods
     */

    private function determineOverallStatus(array $checks): string
    {
        $statuses = collect($checks)->pluck('status')->toArray();

        if (in_array('critical', $statuses) || in_array('unhealthy', $statuses)) {
            return 'unhealthy';
        }

        if (in_array('degraded', $statuses) || in_array('warning', $statuses)) {
            return 'degraded';
        }

        return 'healthy';
    }

    private function generateHealthAlerts(array $checks): array
    {
        $alerts = [];

        foreach ($checks as $checkType => $check) {
            if (in_array($check['status'], ['unhealthy', 'critical', 'degraded', 'warning'])) {
                $alerts[] = [
                    'level' => $this->mapStatusToAlertLevel($check['status']),
                    'type' => $checkType,
                    'message' => $this->generateAlertMessage($checkType, $check),
                    'timestamp' => now()->toISOString()
                ];
            }
        }

        return $alerts;
    }

    private function mapStatusToAlertLevel(string $status): string
    {
        return match ($status) {
            'critical', 'unhealthy' => 'critical',
            'degraded' => 'warning',
            'warning' => 'info',
            default => 'info'
        };
    }

    private function generateAlertMessage(string $checkType, array $check): string
    {
        return match ($checkType) {
            'database' => "Database health check failed: " . ($check['error'] ?? 'Slow response time'),
            'cache' => "Cache system issues detected: " . ($check['error'] ?? 'Performance degraded'),
            'queue' => "Queue system issues: " . ($check['queue_size'] ?? 0) . " jobs pending",
            'performance' => "Application performance degraded: " . ($check['avg_response_time_ms'] ?? 0) . "ms avg response",
            'storage' => "Disk usage critical: " . ($check['disk_usage_percent'] ?? 0) . "% used",
            'resources' => "Resource usage high: " . ($check['memory_usage_percent'] ?? 0) . "% memory used",
            default => ucfirst($checkType) . " health check issue detected"
        };
    }

    private function generatePerformanceSummary(): array
    {
        return [
            'avg_response_time_24h' => $this->getAverageResponseTime24h(),
            'requests_per_minute' => $this->getRequestsPerMinute(),
            'error_rate_24h' => $this->getErrorRate24h(),
            'cache_hit_rate' => $this->getCacheHitRate(),
            'database_query_time' => $this->getAverageDatabaseQueryTime()
        ];
    }

    private function getRecentTelescopeEntries(): array
    {
        if (class_exists(\Laravel\Telescope\Telescope::class)) {
            $repository = app(DatabaseEntriesRepository::class);
            return $repository->get('request', [
                'limit' => 100,
                'before' => now()->subMinutes(15)->timestamp
            ])->toArray();
        }

        return [];
    }

    private function calculateAverageResponseTime(array $entries): float
    {
        if (empty($entries)) return 0;

        $totalTime = array_sum(array_column($entries, 'duration'));
        return round($totalTime / count($entries), 2);
    }

    private function calculateErrorRate(array $entries): float
    {
        if (empty($entries)) return 0;

        $errorCount = count(array_filter($entries, function ($entry) {
            $payload = is_string($entry['content']) ? json_decode($entry['content'], true) : $entry['content'];
            return isset($payload['response_status']) && $payload['response_status'] >= 400;
        }));

        return round(($errorCount / count($entries)) * 100, 2);
    }

    // Placeholder methods - would be implemented based on specific requirements
    private function getDatabaseConnectionInfo(): array { return ['active' => 5, 'max' => 20]; }
    private function getTableSizes(): array { return ['seo_analyses' => '150MB']; }
    private function getCacheStats(): array { return ['hit_rate' => 85.5]; }
    private function checkGooglePageSpeedHealth(): array { return ['status' => 'healthy']; }
    private function checkMozApiHealth(): array { return ['status' => 'healthy']; }
    private function getFailedLoginAttempts(): int { return 0; }
    private function getSuspiciousRequests(): int { return 0; }
    private function getRateLimitViolations(): int { return 0; }
    private function getMemoryLimit(): int { return 512 * 1024 * 1024; }
    private function getCpuUsage(): float { return 25.0; }
    private function getAverageResponseTime24h(): float { return 250.0; }
    private function getRequestsPerMinute(): float { return 15.5; }
    private function getErrorRate24h(): float { return 1.2; }
    private function getCacheHitRate(): float { return 92.5; }
    private function getAverageDatabaseQueryTime(): float { return 45.0; }

    // Performance metrics methods
    private function getResponseTimeMetrics(): array { return ['avg' => 250, 'p95' => 500, 'p99' => 1000]; }
    private function getErrorRateMetrics(): array { return ['current' => 1.2, 'target' => 2.0]; }
    private function getThroughputMetrics(): array { return ['rpm' => 120, 'rps' => 2.0]; }
    private function getResourceUsageMetrics(): array { return ['memory' => 65, 'cpu' => 30]; }
    private function getCachePerformanceMetrics(): array { return ['hit_rate' => 92, 'miss_rate' => 8]; }
    private function getDatabasePerformanceMetrics(): array { return ['avg_query_time' => 45]; }
    private function getQueuePerformanceMetrics(): array { return ['throughput' => 50, 'failures' => 2]; }

    private function storePerformanceMetrics(array $metrics): void { /* Implementation */ }
    private function checkPerformanceAlerts(array $metrics): array { return []; }

    // Error analysis methods
    private function getErrorSummary(): array { return ['total' => 15, 'critical' => 2, 'warnings' => 13]; }
    private function getErrorTrends(): array { return ['trend' => 'decreasing', 'change_percent' => -15]; }
    private function getCriticalErrors(): array { return []; }
    private function identifyErrorPatterns(): array { return []; }
    private function generateErrorResolutionRecommendations(array $pattern): array { return []; }

    // SEO-specific monitoring methods
    private function getSeoAnalysisThroughput(): array { return ['analyses_per_hour' => 45]; }
    private function getSeoAnalysisSuccessRate(): float { return 98.5; }
    private function getSeoAnalysisPerformance(): array { return ['avg_time' => 15000]; }
    private function getSeoUserSatisfactionMetrics(): array { return ['satisfaction_score' => 4.2]; }
    private function getSeoApiPerformance(): array { return ['uptime' => 99.9]; }

    // Dashboard data methods
    private function getSystemOverview(): array { return ['status' => 'healthy', 'uptime' => '99.95%']; }
    private function getPerformanceChartData(): array { return []; }
    private function getRecentAlerts(): array { return []; }
    private function getSeoAnalysisStats(): array { return ['total_today' => 150, 'avg_score' => 78.5]; }
    private function getUptimeStats(): array { return ['uptime_percent' => 99.95]; }
    private function getUserActivityStats(): array { return ['active_users' => 25, 'sessions' => 45]; }
}