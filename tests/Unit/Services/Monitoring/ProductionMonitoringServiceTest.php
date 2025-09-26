<?php

namespace Tests\Unit\Services\Monitoring;

use Tests\TestCase;
use App\Services\Monitoring\ProductionMonitoringService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class ProductionMonitoringServiceTest extends TestCase
{
    private ProductionMonitoringService $monitoringService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monitoringService = new ProductionMonitoringService();
    }

    public function test_perform_health_check_returns_comprehensive_status(): void
    {
        // Mock database connection
        DB::shouldReceive('select')
            ->with('SELECT 1 as test')
            ->once()
            ->andReturn([]);

        // Mock cache operations
        Cache::shouldReceive('put')
            ->with('health_check', 'test', 60)
            ->once();
        Cache::shouldReceive('get')
            ->with('health_check')
            ->once()
            ->andReturn('test');

        // Mock Redis operations
        Redis::shouldReceive('set')
            ->once()
            ->andReturn(true);
        Redis::shouldReceive('get')
            ->once()
            ->andReturn('test_value');
        Redis::shouldReceive('del')
            ->once()
            ->andReturn(1);

        // Mock Queue operations
        \Queue::shouldReceive('size')
            ->once()
            ->andReturn(5);

        DB::shouldReceive('table')
            ->with('failed_jobs')
            ->once()
            ->andReturnSelf();
        DB::shouldReceive('count')
            ->once()
            ->andReturn(2);

        Log::shouldReceive('info')->atLeast()->once();

        $result = $this->monitoringService->performHealthCheck();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('overall_status', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertArrayHasKey('checks', $result);
        $this->assertArrayHasKey('alerts', $result);
        $this->assertArrayHasKey('performance_summary', $result);

        // Verify individual check categories
        $this->assertArrayHasKey('database', $result['checks']);
        $this->assertArrayHasKey('cache', $result['checks']);
        $this->assertArrayHasKey('queue', $result['checks']);
        $this->assertArrayHasKey('external_services', $result['checks']);
        $this->assertArrayHasKey('performance', $result['checks']);
        $this->assertArrayHasKey('security', $result['checks']);
        $this->assertArrayHasKey('storage', $result['checks']);
        $this->assertArrayHasKey('resources', $result['checks']);

        $this->assertContains($result['overall_status'], ['healthy', 'degraded', 'unhealthy', 'critical']);
    }

    public function test_perform_health_check_handles_exceptions_gracefully(): void
    {
        // Mock database failure
        DB::shouldReceive('select')
            ->with('SELECT 1 as test')
            ->once()
            ->andThrow(new \Exception('Database connection failed'));

        Log::shouldReceive('info')->atLeast()->once();
        Log::shouldReceive('error')->atLeast()->once();

        $result = $this->monitoringService->performHealthCheck();

        $this->assertIsArray($result);
        $this->assertEquals('critical', $result['overall_status']);
        $this->assertNotEmpty($result['alerts']);
        $this->assertEquals('critical', $result['alerts'][0]['level']);
    }

    public function test_monitor_performance_metrics_returns_comprehensive_data(): void
    {
        $result = $this->monitoringService->monitorPerformanceMetrics();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('metrics', $result);
        $this->assertArrayHasKey('alerts', $result);
        $this->assertArrayHasKey('recorded_at', $result);

        $metrics = $result['metrics'];
        $this->assertArrayHasKey('response_times', $metrics);
        $this->assertArrayHasKey('error_rates', $metrics);
        $this->assertArrayHasKey('throughput', $metrics);
        $this->assertArrayHasKey('resource_usage', $metrics);
        $this->assertArrayHasKey('cache_performance', $metrics);
        $this->assertArrayHasKey('database_performance', $metrics);
        $this->assertArrayHasKey('queue_performance', $metrics);
    }

    public function test_analyze_error_patterns_returns_structured_analysis(): void
    {
        $result = $this->monitoringService->analyzeErrorPatterns();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error_summary', $result);
        $this->assertArrayHasKey('error_trends', $result);
        $this->assertArrayHasKey('critical_errors', $result);
        $this->assertArrayHasKey('error_patterns', $result);
        $this->assertArrayHasKey('resolution_recommendations', $result);

        $this->assertIsArray($result['error_summary']);
        $this->assertIsArray($result['error_trends']);
        $this->assertIsArray($result['critical_errors']);
        $this->assertIsArray($result['error_patterns']);
        $this->assertIsArray($result['resolution_recommendations']);
    }

    public function test_monitor_seo_analysis_performance_returns_specific_metrics(): void
    {
        $result = $this->monitoringService->monitorSeoAnalysisPerformance();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('analysis_throughput', $result);
        $this->assertArrayHasKey('analysis_success_rate', $result);
        $this->assertArrayHasKey('analysis_performance', $result);
        $this->assertArrayHasKey('user_satisfaction', $result);
        $this->assertArrayHasKey('api_performance', $result);
    }

    public function test_generate_dashboard_data_returns_complete_dashboard_info(): void
    {
        $result = $this->monitoringService->generateDashboardData();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('system_overview', $result);
        $this->assertArrayHasKey('performance_charts', $result);
        $this->assertArrayHasKey('recent_alerts', $result);
        $this->assertArrayHasKey('seo_analysis_stats', $result);
        $this->assertArrayHasKey('uptime_stats', $result);
        $this->assertArrayHasKey('user_activity', $result);

        // Verify system overview structure
        $this->assertIsArray($result['system_overview']);
        $this->assertArrayHasKey('status', $result['system_overview']);
        $this->assertArrayHasKey('uptime', $result['system_overview']);

        // Verify other dashboard components are arrays
        $this->assertIsArray($result['performance_charts']);
        $this->assertIsArray($result['recent_alerts']);
        $this->assertIsArray($result['seo_analysis_stats']);
        $this->assertIsArray($result['uptime_stats']);
        $this->assertIsArray($result['user_activity']);
    }

    public function test_health_check_database_component_returns_proper_structure(): void
    {
        DB::shouldReceive('select')
            ->with('SELECT 1 as test')
            ->once()
            ->andReturn([]);

        Log::shouldReceive('info')->atLeast()->once();

        $healthCheck = $this->monitoringService->performHealthCheck();
        $databaseCheck = $healthCheck['checks']['database'];

        $this->assertArrayHasKey('status', $databaseCheck);
        $this->assertArrayHasKey('response_time_ms', $databaseCheck);
        $this->assertArrayHasKey('last_check', $databaseCheck);

        $this->assertContains($databaseCheck['status'], ['healthy', 'degraded', 'unhealthy']);
        $this->assertIsNumeric($databaseCheck['response_time_ms']);
        $this->assertNotEmpty($databaseCheck['last_check']);
    }

    public function test_health_check_cache_component_returns_proper_structure(): void
    {
        Cache::shouldReceive('put')
            ->with('health_check', 'test', 60)
            ->once();
        Cache::shouldReceive('get')
            ->with('health_check')
            ->once()
            ->andReturn('test');

        Redis::shouldReceive('set')
            ->once()
            ->andReturn(true);
        Redis::shouldReceive('get')
            ->once()
            ->andReturn('test_value');
        Redis::shouldReceive('del')
            ->once()
            ->andReturn(1);

        Log::shouldReceive('info')->atLeast()->once();

        $healthCheck = $this->monitoringService->performHealthCheck();
        $cacheCheck = $healthCheck['checks']['cache'];

        $this->assertArrayHasKey('status', $cacheCheck);
        $this->assertArrayHasKey('response_time_ms', $cacheCheck);
        $this->assertArrayHasKey('last_check', $cacheCheck);

        $this->assertContains($cacheCheck['status'], ['healthy', 'degraded', 'unhealthy']);
        $this->assertIsNumeric($cacheCheck['response_time_ms']);
    }

    public function test_health_check_queue_component_detects_high_queue_depth(): void
    {
        \Queue::shouldReceive('size')
            ->once()
            ->andReturn(150); // Above threshold

        DB::shouldReceive('table')
            ->with('failed_jobs')
            ->once()
            ->andReturnSelf();
        DB::shouldReceive('count')
            ->once()
            ->andReturn(2);

        Log::shouldReceive('info')->atLeast()->once();

        $healthCheck = $this->monitoringService->performHealthCheck();
        $queueCheck = $healthCheck['checks']['queue'];

        $this->assertEquals('degraded', $queueCheck['status']);
        $this->assertEquals(150, $queueCheck['queue_size']);
        $this->assertEquals(2, $queueCheck['failed_jobs']);
    }

    public function test_health_check_queue_component_detects_too_many_failed_jobs(): void
    {
        \Queue::shouldReceive('size')
            ->once()
            ->andReturn(5);

        DB::shouldReceive('table')
            ->with('failed_jobs')
            ->once()
            ->andReturnSelf();
        DB::shouldReceive('count')
            ->once()
            ->andReturn(15); // Above threshold

        Log::shouldReceive('info')->atLeast()->once();

        $healthCheck = $this->monitoringService->performHealthCheck();
        $queueCheck = $healthCheck['checks']['queue'];

        $this->assertEquals('unhealthy', $queueCheck['status']);
        $this->assertEquals(5, $queueCheck['queue_size']);
        $this->assertEquals(15, $queueCheck['failed_jobs']);
    }

    public function test_health_check_generates_appropriate_alerts(): void
    {
        // Mock unhealthy database
        DB::shouldReceive('select')
            ->with('SELECT 1 as test')
            ->once()
            ->andThrow(new \Exception('Database error'));

        // Mock other components as healthy
        Cache::shouldReceive('put')->once();
        Cache::shouldReceive('get')->once()->andReturn('test');
        \Queue::shouldReceive('size')->once()->andReturn(5);
        DB::shouldReceive('table')->once()->andReturnSelf();
        DB::shouldReceive('count')->once()->andReturn(1);

        Log::shouldReceive('info')->atLeast()->once();
        Log::shouldReceive('error')->atLeast()->once();

        $healthCheck = $this->monitoringService->performHealthCheck();

        $this->assertNotEmpty($healthCheck['alerts']);
        $this->assertEquals('unhealthy', $healthCheck['overall_status']);

        $databaseAlert = collect($healthCheck['alerts'])->firstWhere('type', 'database');
        $this->assertNotNull($databaseAlert);
        $this->assertEquals('critical', $databaseAlert['level']);
        $this->assertStringContainsString('Database health check failed', $databaseAlert['message']);
    }

    public function test_performance_summary_contains_expected_metrics(): void
    {
        Log::shouldReceive('info')->atLeast()->once();

        $healthCheck = $this->monitoringService->performHealthCheck();
        $performanceSummary = $healthCheck['performance_summary'];

        $this->assertArrayHasKey('avg_response_time_24h', $performanceSummary);
        $this->assertArrayHasKey('requests_per_minute', $performanceSummary);
        $this->assertArrayHasKey('error_rate_24h', $performanceSummary);
        $this->assertArrayHasKey('cache_hit_rate', $performanceSummary);
        $this->assertArrayHasKey('database_query_time', $performanceSummary);

        $this->assertIsNumeric($performanceSummary['avg_response_time_24h']);
        $this->assertIsNumeric($performanceSummary['requests_per_minute']);
        $this->assertIsNumeric($performanceSummary['error_rate_24h']);
        $this->assertIsNumeric($performanceSummary['cache_hit_rate']);
        $this->assertIsNumeric($performanceSummary['database_query_time']);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}