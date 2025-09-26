<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class HealthCheckEndpointsTest extends TestCase
{
    public function test_basic_health_endpoint_returns_healthy_status(): void
    {
        $response = $this->get('/health');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'timestamp',
            'application',
            'version',
            'environment'
        ]);

        $data = $response->json();
        $this->assertEquals('healthy', $data['status']);
        $this->assertEquals(config('app.name'), $data['application']);
        $this->assertEquals(app()->environment(), $data['environment']);
    }

    public function test_comprehensive_health_endpoint_returns_detailed_status(): void
    {
        // Mock successful database connection
        DB::shouldReceive('select')
            ->with('SELECT 1 as test')
            ->andReturn([]);

        // Mock successful cache operations
        Cache::shouldReceive('put')->andReturn(true);
        Cache::shouldReceive('get')->andReturn('test');

        // Mock successful queue operations
        \Queue::shouldReceive('size')->andReturn(5);
        DB::shouldReceive('table')->with('failed_jobs')->andReturnSelf();
        DB::shouldReceive('count')->andReturn(2);

        $response = $this->get('/health/comprehensive');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'overall_status',
            'timestamp',
            'checks' => [
                'database',
                'cache',
                'queue',
                'external_services',
                'performance',
                'security',
                'storage',
                'resources'
            ],
            'alerts',
            'performance_summary'
        ]);

        $data = $response->json();
        $this->assertContains($data['overall_status'], ['healthy', 'degraded', 'unhealthy']);
    }

    public function test_database_health_endpoint_checks_connection(): void
    {
        // Mock successful database operations
        DB::shouldReceive('select')
            ->with('SELECT 1 as test')
            ->andReturn([]);

        DB::shouldReceive('table')
            ->with('users')
            ->andReturnSelf();
        DB::shouldReceive('where')
            ->with('id', 0)
            ->andReturnSelf();
        DB::shouldReceive('count')
            ->andReturn(0);

        $response = $this->get('/health/database');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'connection_time_ms',
            'query_time_ms',
            'metrics',
            'timestamp'
        ]);

        $data = $response->json();
        $this->assertEquals('healthy', $data['status']);
        $this->assertIsNumeric($data['connection_time_ms']);
        $this->assertIsNumeric($data['query_time_ms']);
    }

    public function test_database_health_endpoint_handles_failures(): void
    {
        // Mock database failure
        DB::shouldReceive('select')
            ->with('SELECT 1 as test')
            ->andThrow(new \Exception('Database connection failed'));

        $response = $this->get('/health/database');

        $response->assertStatus(503);
        $response->assertJsonStructure([
            'status',
            'error',
            'timestamp'
        ]);

        $data = $response->json();
        $this->assertEquals('unhealthy', $data['status']);
        $this->assertStringContainsString('Database connection failed', $data['error']);
    }

    public function test_cache_health_endpoint_tests_redis_and_laravel_cache(): void
    {
        // Mock Redis operations
        Redis::shouldReceive('set')->andReturn(true);
        Redis::shouldReceive('get')->andReturn('test_value');
        Redis::shouldReceive('del')->andReturn(1);

        // Mock Laravel Cache operations
        Cache::shouldReceive('put')->andReturn(true);
        Cache::shouldReceive('get')->andReturn('test_value');
        Cache::shouldReceive('forget')->andReturn(true);

        $response = $this->get('/health/cache');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'redis_time_ms',
            'cache_time_ms',
            'statistics',
            'timestamp'
        ]);

        $data = $response->json();
        $this->assertEquals('healthy', $data['status']);
        $this->assertIsNumeric($data['redis_time_ms']);
        $this->assertIsNumeric($data['cache_time_ms']);
    }

    public function test_cache_health_endpoint_handles_failures(): void
    {
        // Mock cache failure
        Redis::shouldReceive('set')
            ->andThrow(new \Exception('Redis connection failed'));

        $response = $this->get('/health/cache');

        $response->assertStatus(503);
        $response->assertJsonStructure([
            'status',
            'error',
            'timestamp'
        ]);

        $data = $response->json();
        $this->assertEquals('unhealthy', $data['status']);
        $this->assertStringContainsString('Redis connection failed', $data['error']);
    }

    public function test_queue_health_endpoint_monitors_queue_status(): void
    {
        // Mock normal queue conditions
        \Queue::shouldReceive('size')->andReturn(25);
        DB::shouldReceive('table')->with('failed_jobs')->andReturnSelf();
        DB::shouldReceive('count')->andReturn(3);

        $response = $this->get('/health/queue');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'queue_size',
            'failed_jobs',
            'warnings',
            'timestamp'
        ]);

        $data = $response->json();
        $this->assertEquals('healthy', $data['status']);
        $this->assertEquals(25, $data['queue_size']);
        $this->assertEquals(3, $data['failed_jobs']);
    }

    public function test_queue_health_endpoint_detects_high_queue_depth(): void
    {
        // Mock high queue depth
        \Queue::shouldReceive('size')->andReturn(150);
        DB::shouldReceive('table')->with('failed_jobs')->andReturnSelf();
        DB::shouldReceive('count')->andReturn(2);

        $response = $this->get('/health/queue');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'queue_size',
            'failed_jobs',
            'warnings',
            'timestamp'
        ]);

        $data = $response->json();
        $this->assertEquals('degraded', $data['status']);
        $this->assertEquals(150, $data['queue_size']);
        $this->assertContains('High queue depth', $data['warnings']);
    }

    public function test_queue_health_endpoint_detects_too_many_failed_jobs(): void
    {
        // Mock too many failed jobs
        \Queue::shouldReceive('size')->andReturn(5);
        DB::shouldReceive('table')->with('failed_jobs')->andReturnSelf();
        DB::shouldReceive('count')->andReturn(15);

        $response = $this->get('/health/queue');

        $response->assertStatus(503);
        $response->assertJsonStructure([
            'status',
            'queue_size',
            'failed_jobs',
            'warnings',
            'timestamp'
        ]);

        $data = $response->json();
        $this->assertEquals('unhealthy', $data['status']);
        $this->assertEquals(15, $data['failed_jobs']);
        $this->assertContains('Too many failed jobs', $data['warnings']);
    }

    public function test_storage_health_endpoint_checks_disk_space(): void
    {
        $response = $this->get('/health/storage');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'disk_usage_percent',
            'free_space_gb',
            'write_time_ms',
            'read_time_ms',
            'warnings',
            'timestamp'
        ]);

        $data = $response->json();
        $this->assertContains($data['status'], ['healthy', 'warning', 'critical']);
        $this->assertIsNumeric($data['disk_usage_percent']);
        $this->assertIsNumeric($data['free_space_gb']);
        $this->assertIsNumeric($data['write_time_ms']);
        $this->assertIsNumeric($data['read_time_ms']);
    }

    public function test_external_services_health_endpoint_checks_api_status(): void
    {
        $response = $this->get('/health/external');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'services' => [
                'google_pagespeed',
                'moz_api'
            ],
            'timestamp'
        ]);

        $data = $response->json();
        $this->assertContains($data['status'], ['healthy', 'degraded', 'unhealthy']);
        $this->assertIsArray($data['services']);
    }

    public function test_performance_health_endpoint_returns_metrics(): void
    {
        $response = $this->get('/health/performance');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'performance_metrics',
            'timestamp'
        ]);

        $data = $response->json();
        $this->assertEquals('success', $data['status']);
        $this->assertIsArray($data['performance_metrics']);
    }

    public function test_seo_analysis_health_endpoint_tests_analysis_capability(): void
    {
        $response = $this->get('/health/seo-analysis');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'test_analysis_time_ms',
            'test_url_accessible',
            'seo_metrics',
            'timestamp'
        ]);

        $data = $response->json();
        $this->assertContains($data['status'], ['healthy', 'degraded', 'unhealthy']);
        $this->assertIsNumeric($data['test_analysis_time_ms']);
        $this->assertIsBool($data['test_url_accessible']);
    }

    public function test_readiness_probe_endpoint_for_kubernetes(): void
    {
        // Mock successful dependency checks
        DB::shouldReceive('select')->andReturn([]);
        Cache::shouldReceive('put')->andReturn(true);
        Cache::shouldReceive('get')->andReturn('test');
        Cache::shouldReceive('forget')->andReturn(true);

        $response = $this->get('/ready');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'ready',
            'checks',
            'timestamp'
        ]);

        $data = $response->json();
        $this->assertTrue($data['ready']);
        $this->assertIsArray($data['checks']);
    }

    public function test_readiness_probe_fails_when_dependencies_unavailable(): void
    {
        // Mock failed database connection
        DB::shouldReceive('select')
            ->andThrow(new \Exception('Database unavailable'));

        $response = $this->get('/ready');

        $response->assertStatus(503);
        $response->assertJsonStructure([
            'ready',
            'checks',
            'timestamp'
        ]);

        $data = $response->json();
        $this->assertFalse($data['ready']);
    }

    public function test_liveness_probe_endpoint_for_kubernetes(): void
    {
        $response = $this->get('/live');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'alive',
            'timestamp'
        ]);

        $data = $response->json();
        $this->assertTrue($data['alive']);
    }

    public function test_metrics_endpoint_returns_system_information(): void
    {
        $response = $this->get('/metrics');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'metrics' => [
                'memory' => [
                    'usage_bytes',
                    'peak_bytes',
                    'limit_bytes'
                ],
                'php' => [
                    'version',
                    'extensions'
                ],
                'system' => [
                    'load_average'
                ],
                'application' => [
                    'version',
                    'environment',
                    'debug'
                ]
            ],
            'timestamp'
        ]);

        $data = $response->json();
        $this->assertIsArray($data['metrics']);
        $this->assertIsNumeric($data['metrics']['memory']['usage_bytes']);
        $this->assertIsNumeric($data['metrics']['memory']['peak_bytes']);
        $this->assertEquals(PHP_VERSION, $data['metrics']['php']['version']);
        $this->assertEquals(app()->environment(), $data['metrics']['application']['environment']);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}