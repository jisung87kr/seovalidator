<?php

namespace App\Services\Performance;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\QueryException;

/**
 * Database Performance Optimization Service
 * Handles database indexing, query optimization, and performance monitoring
 */
class DatabaseOptimizationService
{
    private array $performanceMetrics = [];
    private array $slowQueries = [];

    /**
     * Optimize all database tables for SEO analysis workloads
     */
    public function optimizeDatabase(): array
    {
        Log::info('Starting database optimization');

        $results = [
            'indexes_created' => 0,
            'indexes_optimized' => 0,
            'partitions_created' => 0,
            'queries_optimized' => 0,
            'performance_improvements' => []
        ];

        try {
            // Optimize SEO analyses table
            $results['indexes_created'] += $this->optimizeSeoAnalysesTable();

            // Optimize cache tables
            $results['indexes_created'] += $this->optimizeCacheTables();

            // Create additional performance tables
            $results['indexes_created'] += $this->createPerformanceTables();

            // Optimize queries
            $results['queries_optimized'] = $this->optimizeCommonQueries();

            // Create partitions for large tables
            $results['partitions_created'] = $this->createTablePartitions();

            // Analyze table statistics
            $this->updateTableStatistics();

            // Enable query cache if available
            $this->enableQueryCache();

            Log::info('Database optimization completed', $results);

        } catch (\Exception $e) {
            Log::error('Database optimization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $results;
    }

    /**
     * Optimize SEO analyses table with proper indexing
     */
    private function optimizeSeoAnalysesTable(): int
    {
        $indexesCreated = 0;

        try {
            // Composite index for user queries with status filter
            if (!$this->indexExists('seo_analyses', 'idx_user_status_created')) {
                DB::statement('CREATE INDEX idx_user_status_created ON seo_analyses (user_id, status, created_at)');
                $indexesCreated++;
            }

            // Index for URL lookups (with hash for faster searches)
            if (!$this->indexExists('seo_analyses', 'idx_url_hash')) {
                DB::statement('CREATE INDEX idx_url_hash ON seo_analyses (url(255), MD5(url))');
                $indexesCreated++;
            }

            // Index for score-based queries
            if (!$this->indexExists('seo_analyses', 'idx_scores')) {
                DB::statement('CREATE INDEX idx_scores ON seo_analyses (overall_score, performance_score, analyzed_at)');
                $indexesCreated++;
            }

            // Index for date range queries
            if (!$this->indexExists('seo_analyses', 'idx_analyzed_at_status')) {
                DB::statement('CREATE INDEX idx_analyzed_at_status ON seo_analyses (analyzed_at, status)');
                $indexesCreated++;
            }

            // Full-text index for title searches
            if (!$this->indexExists('seo_analyses', 'idx_title_fulltext')) {
                DB::statement('CREATE FULLTEXT INDEX idx_title_fulltext ON seo_analyses (title)');
                $indexesCreated++;
            }

            // JSON index for analysis data (MySQL 5.7+)
            if ($this->supportsFunctionalIndexes()) {
                if (!$this->indexExists('seo_analyses', 'idx_analysis_data_scores')) {
                    DB::statement('CREATE INDEX idx_analysis_data_scores ON seo_analyses ((CAST(JSON_EXTRACT(analysis_data, "$.overall_score") AS DECIMAL(5,2))))');
                    $indexesCreated++;
                }
            }

            Log::info('SEO analyses table optimized', ['indexes_created' => $indexesCreated]);

        } catch (QueryException $e) {
            Log::warning('Failed to optimize seo_analyses table', ['error' => $e->getMessage()]);
        }

        return $indexesCreated;
    }

    /**
     * Optimize cache-related tables
     */
    private function optimizeCacheTables(): int
    {
        $indexesCreated = 0;

        try {
            // Create cache table if it doesn't exist
            if (!Schema::hasTable('analysis_cache')) {
                Schema::create('analysis_cache', function ($table) {
                    $table->id();
                    $table->string('url', 500)->unique();
                    $table->string('url_hash', 32)->index();
                    $table->longText('data');
                    $table->timestamp('expires_at')->index();
                    $table->enum('cache_type', ['full', 'partial', 'quick'])->default('full');
                    $table->integer('access_count')->default(0);
                    $table->timestamp('last_accessed_at')->nullable();
                    $table->timestamps();

                    $table->index(['expires_at', 'cache_type']);
                    $table->index(['last_accessed_at', 'access_count']);
                });
                $indexesCreated += 4;
            }

            // Create query cache table
            if (!Schema::hasTable('query_cache')) {
                Schema::create('query_cache', function ($table) {
                    $table->id();
                    $table->string('query_hash', 64)->unique();
                    $table->longText('query_sql');
                    $table->longText('result_data');
                    $table->integer('execution_time_ms');
                    $table->integer('result_count');
                    $table->timestamp('expires_at')->index();
                    $table->timestamps();

                    $table->index(['expires_at', 'execution_time_ms']);
                });
                $indexesCreated += 2;
            }

            Log::info('Cache tables optimized', ['indexes_created' => $indexesCreated]);

        } catch (QueryException $e) {
            Log::warning('Failed to optimize cache tables', ['error' => $e->getMessage()]);
        }

        return $indexesCreated;
    }

    /**
     * Create additional performance monitoring tables
     */
    private function createPerformanceTables(): int
    {
        $indexesCreated = 0;

        try {
            // Performance metrics table
            if (!Schema::hasTable('performance_metrics')) {
                Schema::create('performance_metrics', function ($table) {
                    $table->id();
                    $table->string('metric_type', 50);
                    $table->string('metric_name', 100);
                    $table->decimal('metric_value', 10, 4);
                    $table->json('metric_metadata')->nullable();
                    $table->timestamp('recorded_at');
                    $table->timestamps();

                    $table->index(['metric_type', 'recorded_at']);
                    $table->index(['metric_name', 'recorded_at']);
                });
                $indexesCreated += 2;
            }

            // Query performance log
            if (!Schema::hasTable('query_performance_log')) {
                Schema::create('query_performance_log', function ($table) {
                    $table->id();
                    $table->string('query_type', 50);
                    $table->text('query_sql');
                    $table->integer('execution_time_ms');
                    $table->integer('rows_examined');
                    $table->integer('rows_returned');
                    $table->text('explain_plan')->nullable();
                    $table->timestamp('executed_at');

                    $table->index(['query_type', 'execution_time_ms']);
                    $table->index(['executed_at']);
                });
                $indexesCreated += 2;
            }

            // Competitive analysis table
            if (!Schema::hasTable('competitive_analyses')) {
                Schema::create('competitive_analyses', function ($table) {
                    $table->id();
                    $table->foreignId('user_id')->constrained()->onDelete('cascade');
                    $table->string('primary_domain');
                    $table->string('competitor_url');
                    $table->json('comparison_data');
                    $table->decimal('competitive_score', 5, 2)->nullable();
                    $table->timestamp('analyzed_at')->nullable();
                    $table->timestamps();

                    $table->index(['user_id', 'primary_domain']);
                    $table->index(['analyzed_at']);
                });
                $indexesCreated += 2;
            }

            Log::info('Performance tables created', ['indexes_created' => $indexesCreated]);

        } catch (QueryException $e) {
            Log::warning('Failed to create performance tables', ['error' => $e->getMessage()]);
        }

        return $indexesCreated;
    }

    /**
     * Optimize common query patterns
     */
    private function optimizeCommonQueries(): int
    {
        $optimizedQueries = 0;

        try {
            // Create materialized view for user analysis summaries
            $this->createUserAnalysisSummaryView();
            $optimizedQueries++;

            // Create view for recent high-scoring analyses
            $this->createHighScoreAnalysesView();
            $optimizedQueries++;

            // Create view for performance trending
            $this->createPerformanceTrendingView();
            $optimizedQueries++;

            Log::info('Common queries optimized', ['count' => $optimizedQueries]);

        } catch (QueryException $e) {
            Log::warning('Failed to optimize queries', ['error' => $e->getMessage()]);
        }

        return $optimizedQueries;
    }

    /**
     * Create table partitions for large datasets
     */
    private function createTablePartitions(): int
    {
        $partitionsCreated = 0;

        try {
            // Partition seo_analyses by month if table is large
            $rowCount = DB::table('seo_analyses')->count();

            if ($rowCount > 100000) {
                // Note: This is MySQL-specific partitioning
                if ($this->getDatabaseDriver() === 'mysql') {
                    $this->partitionSeoAnalysesByMonth();
                    $partitionsCreated++;
                }
            }

            // Partition performance logs by date
            if (Schema::hasTable('query_performance_log')) {
                $this->partitionPerformanceLogsByDate();
                $partitionsCreated++;
            }

            Log::info('Table partitions created', ['count' => $partitionsCreated]);

        } catch (QueryException $e) {
            Log::warning('Failed to create partitions', ['error' => $e->getMessage()]);
        }

        return $partitionsCreated;
    }

    /**
     * Analyze slow queries and provide optimization suggestions
     */
    public function analyzeSlowQueries(int $thresholdMs = 1000): array
    {
        $slowQueries = [];

        try {
            // Enable slow query log if MySQL
            if ($this->getDatabaseDriver() === 'mysql') {
                $slowQueries = $this->analyzeMySQLSlowQueries($thresholdMs);
            }

            // Analyze application-level slow queries
            $appSlowQueries = $this->analyzeApplicationSlowQueries($thresholdMs);
            $slowQueries = array_merge($slowQueries, $appSlowQueries);

            // Generate optimization recommendations
            foreach ($slowQueries as &$query) {
                $query['recommendations'] = $this->generateQueryOptimizationRecommendations($query);
            }

            Log::info('Slow query analysis completed', [
                'threshold_ms' => $thresholdMs,
                'slow_queries_found' => count($slowQueries)
            ]);

        } catch (\Exception $e) {
            Log::error('Slow query analysis failed', ['error' => $e->getMessage()]);
        }

        return $slowQueries;
    }

    /**
     * Monitor database performance metrics
     */
    public function monitorDatabasePerformance(): array
    {
        $metrics = [];

        try {
            // Connection pool metrics
            $metrics['connections'] = $this->getConnectionMetrics();

            // Query performance metrics
            $metrics['query_performance'] = $this->getQueryPerformanceMetrics();

            // Index usage metrics
            $metrics['index_usage'] = $this->getIndexUsageMetrics();

            // Table size metrics
            $metrics['table_sizes'] = $this->getTableSizeMetrics();

            // Cache hit ratios
            $metrics['cache_performance'] = $this->getCachePerformanceMetrics();

            // Store metrics for trending
            $this->storePerformanceMetrics($metrics);

            Log::info('Database performance monitoring completed');

        } catch (\Exception $e) {
            Log::error('Database performance monitoring failed', ['error' => $e->getMessage()]);
        }

        return $metrics;
    }

    /**
     * Implement database connection pooling optimization
     */
    public function optimizeConnectionPooling(): array
    {
        $config = config('database.connections.' . config('database.default'));
        $optimizations = [];

        try {
            // Analyze current connection usage
            $connectionStats = $this->analyzeConnectionUsage();

            // Recommend optimal pool sizes
            $recommendations = $this->calculateOptimalPoolSizes($connectionStats);

            // Apply optimizations if safe
            if ($recommendations['can_apply_safely']) {
                $optimizations = $this->applyConnectionPoolOptimizations($recommendations);
            }

            Log::info('Connection pooling optimization completed', $optimizations);

        } catch (\Exception $e) {
            Log::error('Connection pooling optimization failed', ['error' => $e->getMessage()]);
        }

        return $optimizations;
    }

    /**
     * Private helper methods
     */

    private function indexExists(string $table, string $indexName): bool
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
            return !empty($indexes);
        } catch (QueryException $e) {
            return false;
        }
    }

    private function supportsFunctionalIndexes(): bool
    {
        try {
            $version = DB::select('SELECT VERSION() as version')[0]->version;
            return version_compare($version, '5.7.0', '>=');
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getDatabaseDriver(): string
    {
        return config('database.default');
    }

    private function updateTableStatistics(): void
    {
        try {
            $tables = ['seo_analyses', 'analysis_cache', 'performance_metrics'];

            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    if ($this->getDatabaseDriver() === 'mysql') {
                        DB::statement("ANALYZE TABLE {$table}");
                    }
                }
            }

            Log::info('Table statistics updated');
        } catch (QueryException $e) {
            Log::warning('Failed to update table statistics', ['error' => $e->getMessage()]);
        }
    }

    private function enableQueryCache(): void
    {
        try {
            if ($this->getDatabaseDriver() === 'mysql') {
                // Note: Query cache is deprecated in MySQL 8.0+
                $version = DB::select('SELECT VERSION() as version')[0]->version;
                if (version_compare($version, '8.0.0', '<')) {
                    DB::statement('SET GLOBAL query_cache_type = ON');
                    DB::statement('SET GLOBAL query_cache_size = 268435456'); // 256MB
                }
            }
        } catch (QueryException $e) {
            Log::warning('Failed to enable query cache', ['error' => $e->getMessage()]);
        }
    }

    private function createUserAnalysisSummaryView(): void
    {
        DB::statement('
            CREATE OR REPLACE VIEW user_analysis_summary AS
            SELECT
                user_id,
                COUNT(*) as total_analyses,
                AVG(overall_score) as avg_overall_score,
                AVG(performance_score) as avg_performance_score,
                MAX(analyzed_at) as last_analysis_date,
                COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_analyses,
                COUNT(CASE WHEN status = "failed" THEN 1 END) as failed_analyses
            FROM seo_analyses
            GROUP BY user_id
        ');
    }

    private function createHighScoreAnalysesView(): void
    {
        DB::statement('
            CREATE OR REPLACE VIEW high_score_analyses AS
            SELECT *
            FROM seo_analyses
            WHERE overall_score >= 80
            AND status = "completed"
            AND analyzed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY overall_score DESC, analyzed_at DESC
        ');
    }

    private function createPerformanceTrendingView(): void
    {
        if (Schema::hasTable('performance_metrics')) {
            DB::statement('
                CREATE OR REPLACE VIEW performance_trending AS
                SELECT
                    metric_type,
                    metric_name,
                    AVG(metric_value) as avg_value,
                    MIN(metric_value) as min_value,
                    MAX(metric_value) as max_value,
                    DATE(recorded_at) as date
                FROM performance_metrics
                WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY metric_type, metric_name, DATE(recorded_at)
                ORDER BY date DESC
            ');
        }
    }

    private function partitionSeoAnalysesByMonth(): void
    {
        try {
            // This is a simplified example - production would need more careful implementation
            DB::statement('
                ALTER TABLE seo_analyses
                PARTITION BY RANGE (YEAR(created_at) * 100 + MONTH(created_at)) (
                    PARTITION p202509 VALUES LESS THAN (202510),
                    PARTITION p202510 VALUES LESS THAN (202511),
                    PARTITION p202511 VALUES LESS THAN (202512),
                    PARTITION p202512 VALUES LESS THAN (202601),
                    PARTITION p_future VALUES LESS THAN MAXVALUE
                )
            ');
        } catch (QueryException $e) {
            Log::warning('Failed to partition seo_analyses table', ['error' => $e->getMessage()]);
        }
    }

    private function partitionPerformanceLogsByDate(): void
    {
        try {
            DB::statement('
                ALTER TABLE query_performance_log
                PARTITION BY RANGE (TO_DAYS(executed_at)) (
                    PARTITION p_last_week VALUES LESS THAN (TO_DAYS(DATE_SUB(NOW(), INTERVAL 7 DAY))),
                    PARTITION p_this_week VALUES LESS THAN (TO_DAYS(NOW())),
                    PARTITION p_future VALUES LESS THAN MAXVALUE
                )
            ');
        } catch (QueryException $e) {
            Log::warning('Failed to partition performance logs table', ['error' => $e->getMessage()]);
        }
    }

    private function analyzeMySQLSlowQueries(int $thresholdMs): array
    {
        try {
            // Enable slow query log temporarily
            DB::statement('SET GLOBAL slow_query_log = ON');
            DB::statement("SET GLOBAL long_query_time = " . ($thresholdMs / 1000));

            // Read slow query log (simplified - would need proper log parsing)
            return [];
        } catch (QueryException $e) {
            return [];
        }
    }

    private function analyzeApplicationSlowQueries(int $thresholdMs): array
    {
        if (Schema::hasTable('query_performance_log')) {
            return DB::table('query_performance_log')
                ->where('execution_time_ms', '>', $thresholdMs)
                ->orderBy('execution_time_ms', 'desc')
                ->take(20)
                ->get()
                ->toArray();
        }

        return [];
    }

    private function generateQueryOptimizationRecommendations(array $query): array
    {
        $recommendations = [];

        // High execution time
        if ($query['execution_time_ms'] > 5000) {
            $recommendations[] = 'Query execution time is very high - consider adding indexes or rewriting query';
        }

        // High rows examined vs returned ratio
        if (isset($query['rows_examined'], $query['rows_returned'])) {
            $ratio = $query['rows_examined'] / max($query['rows_returned'], 1);
            if ($ratio > 100) {
                $recommendations[] = 'Query examines too many rows - add more selective indexes';
            }
        }

        // Common optimization patterns
        if (strpos($query['query_sql'], 'SELECT *') !== false) {
            $recommendations[] = 'Avoid SELECT * - specify only needed columns';
        }

        if (strpos($query['query_sql'], 'ORDER BY') !== false && strpos($query['query_sql'], 'LIMIT') === false) {
            $recommendations[] = 'Consider adding LIMIT when using ORDER BY';
        }

        return $recommendations;
    }

    private function getConnectionMetrics(): array
    {
        try {
            if ($this->getDatabaseDriver() === 'mysql') {
                $status = DB::select('SHOW STATUS LIKE "Threads_%"');
                return [
                    'active_connections' => $this->getStatusValue($status, 'Threads_connected'),
                    'peak_connections' => $this->getStatusValue($status, 'Threads_created'),
                ];
            }
        } catch (\Exception $e) {
            // Fallback
        }

        return ['active_connections' => 0, 'peak_connections' => 0];
    }

    private function getQueryPerformanceMetrics(): array
    {
        if (Schema::hasTable('query_performance_log')) {
            return DB::table('query_performance_log')
                ->selectRaw('
                    AVG(execution_time_ms) as avg_execution_time,
                    MAX(execution_time_ms) as max_execution_time,
                    COUNT(*) as total_queries
                ')
                ->where('executed_at', '>=', now()->subHour())
                ->first() ?? [];
        }

        return [];
    }

    private function getIndexUsageMetrics(): array
    {
        try {
            if ($this->getDatabaseDriver() === 'mysql') {
                return DB::select('
                    SELECT
                        table_name,
                        index_name,
                        cardinality
                    FROM information_schema.statistics
                    WHERE table_schema = DATABASE()
                    ORDER BY cardinality DESC
                    LIMIT 10
                ');
            }
        } catch (\Exception $e) {
            // Fallback
        }

        return [];
    }

    private function getTableSizeMetrics(): array
    {
        try {
            if ($this->getDatabaseDriver() === 'mysql') {
                return DB::select('
                    SELECT
                        table_name,
                        ROUND(((data_length + index_length) / 1024 / 1024), 2) as size_mb,
                        table_rows
                    FROM information_schema.tables
                    WHERE table_schema = DATABASE()
                    ORDER BY (data_length + index_length) DESC
                    LIMIT 10
                ');
            }
        } catch (\Exception $e) {
            // Fallback
        }

        return [];
    }

    private function getCachePerformanceMetrics(): array
    {
        try {
            if ($this->getDatabaseDriver() === 'mysql') {
                $status = DB::select('SHOW STATUS LIKE "%query_cache%"');
                return [
                    'query_cache_hits' => $this->getStatusValue($status, 'Qcache_hits'),
                    'query_cache_inserts' => $this->getStatusValue($status, 'Qcache_inserts'),
                ];
            }
        } catch (\Exception $e) {
            // Fallback
        }

        return [];
    }

    private function getStatusValue(array $status, string $name): int
    {
        foreach ($status as $row) {
            if ($row->Variable_name === $name) {
                return (int) $row->Value;
            }
        }
        return 0;
    }

    private function storePerformanceMetrics(array $metrics): void
    {
        if (Schema::hasTable('performance_metrics')) {
            foreach ($metrics as $type => $typeMetrics) {
                if (is_array($typeMetrics)) {
                    foreach ($typeMetrics as $name => $value) {
                        if (is_numeric($value)) {
                            DB::table('performance_metrics')->insert([
                                'metric_type' => $type,
                                'metric_name' => $name,
                                'metric_value' => $value,
                                'recorded_at' => now(),
                                'created_at' => now(),
                                'updated_at' => now()
                            ]);
                        }
                    }
                }
            }
        }
    }

    private function analyzeConnectionUsage(): array
    {
        // Simplified connection usage analysis
        return [
            'peak_connections' => 10,
            'average_connections' => 5,
            'connection_errors' => 0
        ];
    }

    private function calculateOptimalPoolSizes(array $stats): array
    {
        // Simplified pool size calculation
        return [
            'recommended_min_pool_size' => max(2, $stats['average_connections']),
            'recommended_max_pool_size' => min(20, $stats['peak_connections'] * 1.5),
            'can_apply_safely' => true
        ];
    }

    private function applyConnectionPoolOptimizations(array $recommendations): array
    {
        // This would apply connection pool optimizations
        // Note: This is implementation-specific and would need proper configuration management
        return [
            'applied' => false,
            'reason' => 'Connection pool optimization requires manual configuration'
        ];
    }
}