<?php

namespace App\Services\Monitoring;

use Illuminate\Support\Facades\Log;
use Sentry\Laravel\Facade as Sentry;
use Sentry\State\Scope;
use Sentry\Severity;

/**
 * Sentry Integration Service
 * Enhanced error tracking and monitoring for SEO Validator application
 */
class SentryIntegrationService
{
    private array $contextData = [];
    private array $userData = [];

    /**
     * Configure Sentry context for SEO analysis operations
     */
    public function configureSeoAnalysisContext(array $analysisData): void
    {
        Sentry::configureScope(function (Scope $scope) use ($analysisData) {
            $scope->setTag('operation_type', 'seo_analysis');
            $scope->setTag('analysis_url', $analysisData['url'] ?? 'unknown');
            $scope->setTag('analysis_type', $analysisData['type'] ?? 'full');

            $scope->setContext('seo_analysis', [
                'url' => $analysisData['url'] ?? null,
                'analysis_type' => $analysisData['type'] ?? null,
                'user_id' => $analysisData['user_id'] ?? null,
                'analysis_id' => $analysisData['analysis_id'] ?? null,
                'started_at' => $analysisData['started_at'] ?? now()->toISOString()
            ]);

            if (isset($analysisData['user_id'])) {
                $scope->setUser([
                    'id' => $analysisData['user_id'],
                    'analysis_count' => $analysisData['user_analysis_count'] ?? null
                ]);
            }
        });
    }

    /**
     * Track SEO analysis performance metrics
     */
    public function trackSeoAnalysisPerformance(array $performanceData): void
    {
        Sentry::configureScope(function (Scope $scope) use ($performanceData) {
            $scope->setContext('performance_metrics', [
                'duration_ms' => $performanceData['duration_ms'] ?? null,
                'memory_usage_mb' => $performanceData['memory_usage_mb'] ?? null,
                'external_api_calls' => $performanceData['external_api_calls'] ?? null,
                'cache_hits' => $performanceData['cache_hits'] ?? null,
                'cache_misses' => $performanceData['cache_misses'] ?? null,
                'database_queries' => $performanceData['database_queries'] ?? null
            ]);

            // Set performance-related tags
            if (isset($performanceData['duration_ms'])) {
                $duration = $performanceData['duration_ms'];
                if ($duration > 30000) {
                    $scope->setTag('performance_category', 'very_slow');
                } elseif ($duration > 15000) {
                    $scope->setTag('performance_category', 'slow');
                } elseif ($duration > 5000) {
                    $scope->setTag('performance_category', 'moderate');
                } else {
                    $scope->setTag('performance_category', 'fast');
                }
            }
        });
    }

    /**
     * Report SEO analysis errors with enhanced context
     */
    public function reportSeoAnalysisError(\Throwable $exception, array $context = []): void
    {
        Sentry::configureScope(function (Scope $scope) use ($context) {
            $scope->setTag('error_category', 'seo_analysis');
            $scope->setTag('analysis_stage', $context['stage'] ?? 'unknown');

            if (isset($context['url'])) {
                $scope->setTag('failed_url', $context['url']);
                $scope->setContext('failed_analysis', [
                    'url' => $context['url'],
                    'stage' => $context['stage'] ?? null,
                    'retry_count' => $context['retry_count'] ?? 0,
                    'external_service' => $context['external_service'] ?? null
                ]);
            }
        });

        Sentry::captureException($exception);

        // Also log locally for immediate visibility
        Log::error('SEO Analysis Error', [
            'exception' => $exception->getMessage(),
            'context' => $context,
            'trace' => $exception->getTraceAsString()
        ]);
    }

    /**
     * Track external API integration issues
     */
    public function trackExternalApiIssue(string $service, string $endpoint, array $errorData): void
    {
        Sentry::configureScope(function (Scope $scope) use ($service, $endpoint, $errorData) {
            $scope->setTag('error_category', 'external_api');
            $scope->setTag('external_service', $service);
            $scope->setTag('api_endpoint', $endpoint);

            $scope->setContext('external_api_error', [
                'service' => $service,
                'endpoint' => $endpoint,
                'response_code' => $errorData['response_code'] ?? null,
                'response_time_ms' => $errorData['response_time_ms'] ?? null,
                'error_message' => $errorData['error_message'] ?? null,
                'retry_attempt' => $errorData['retry_attempt'] ?? 0,
                'rate_limited' => $errorData['rate_limited'] ?? false
            ]);
        });

        $message = "External API Error: {$service} - {$endpoint}";
        Sentry::captureMessage($message, Severity::error());

        Log::warning('External API Issue', [
            'service' => $service,
            'endpoint' => $endpoint,
            'error_data' => $errorData
        ]);
    }

    /**
     * Track database performance issues
     */
    public function trackDatabasePerformanceIssue(array $queryData): void
    {
        Sentry::configureScope(function (Scope $scope) use ($queryData) {
            $scope->setTag('error_category', 'database_performance');
            $scope->setTag('query_type', $queryData['type'] ?? 'unknown');

            $scope->setContext('database_performance', [
                'query_type' => $queryData['type'] ?? null,
                'execution_time_ms' => $queryData['execution_time_ms'] ?? null,
                'rows_examined' => $queryData['rows_examined'] ?? null,
                'rows_returned' => $queryData['rows_returned'] ?? null,
                'connection_count' => $queryData['connection_count'] ?? null,
                'table_involved' => $queryData['table'] ?? null
            ]);
        });

        $message = "Database Performance Issue: " . ($queryData['type'] ?? 'Unknown query type');
        Sentry::captureMessage($message, Severity::warning());
    }

    /**
     * Track cache performance and issues
     */
    public function trackCacheIssue(string $operation, array $cacheData): void
    {
        Sentry::configureScope(function (Scope $scope) use ($operation, $cacheData) {
            $scope->setTag('error_category', 'cache_performance');
            $scope->setTag('cache_operation', $operation);

            $scope->setContext('cache_issue', [
                'operation' => $operation,
                'cache_key' => $cacheData['key'] ?? null,
                'cache_size' => $cacheData['size'] ?? null,
                'ttl' => $cacheData['ttl'] ?? null,
                'hit_rate' => $cacheData['hit_rate'] ?? null,
                'error_message' => $cacheData['error_message'] ?? null
            ]);
        });

        $message = "Cache Issue: {$operation}";
        Sentry::captureMessage($message, Severity::warning());
    }

    /**
     * Track user behavior and errors
     */
    public function trackUserError(int $userId, string $action, array $errorData): void
    {
        Sentry::configureScope(function (Scope $scope) use ($userId, $action, $errorData) {
            $scope->setUser([
                'id' => $userId,
                'action' => $action
            ]);

            $scope->setTag('error_category', 'user_action');
            $scope->setTag('user_action', $action);

            $scope->setContext('user_error', [
                'user_id' => $userId,
                'action' => $action,
                'error_type' => $errorData['type'] ?? null,
                'user_agent' => $errorData['user_agent'] ?? null,
                'ip_address' => $errorData['ip_address'] ?? null,
                'session_id' => $errorData['session_id'] ?? null
            ]);
        });

        $message = "User Error: {$action} by user {$userId}";
        Sentry::captureMessage($message, Severity::info());
    }

    /**
     * Track application deployment and version information
     */
    public function trackDeployment(array $deploymentData): void
    {
        Sentry::configureScope(function (Scope $scope) use ($deploymentData) {
            $scope->setTag('deployment_version', $deploymentData['version'] ?? 'unknown');
            $scope->setTag('deployment_environment', $deploymentData['environment'] ?? app()->environment());

            $scope->setContext('deployment', [
                'version' => $deploymentData['version'] ?? null,
                'environment' => $deploymentData['environment'] ?? null,
                'deployed_at' => $deploymentData['deployed_at'] ?? now()->toISOString(),
                'git_commit' => $deploymentData['git_commit'] ?? null,
                'deployed_by' => $deploymentData['deployed_by'] ?? null
            ]);
        });

        $message = "Application Deployment: " . ($deploymentData['version'] ?? 'unknown version');
        Sentry::captureMessage($message, Severity::info());

        Log::info('Application deployed', $deploymentData);
    }

    /**
     * Set up monitoring for specific features
     */
    public function setupFeatureMonitoring(string $feature, array $config = []): void
    {
        Sentry::configureScope(function (Scope $scope) use ($feature, $config) {
            $scope->setTag('feature', $feature);
            $scope->setTag('feature_enabled', $config['enabled'] ?? true);

            $scope->setContext('feature_monitoring', [
                'feature_name' => $feature,
                'enabled' => $config['enabled'] ?? true,
                'version' => $config['version'] ?? '1.0',
                'rollout_percentage' => $config['rollout_percentage'] ?? 100,
                'user_segments' => $config['user_segments'] ?? []
            ]);
        });
    }

    /**
     * Track business metrics and KPIs
     */
    public function trackBusinessMetric(string $metric, $value, array $metadata = []): void
    {
        Sentry::configureScope(function (Scope $scope) use ($metric, $value, $metadata) {
            $scope->setTag('metric_type', 'business');
            $scope->setTag('metric_name', $metric);

            $scope->setContext('business_metric', [
                'metric_name' => $metric,
                'value' => $value,
                'metadata' => $metadata,
                'recorded_at' => now()->toISOString()
            ]);
        });

        // For business metrics, we typically want to track positive events too
        $message = "Business Metric: {$metric} = {$value}";
        Sentry::captureMessage($message, Severity::info());
    }

    /**
     * Create performance transaction for monitoring
     */
    public function createPerformanceTransaction(string $operation, callable $callback, array $context = [])
    {
        $transaction = Sentry::startTransaction([
            'op' => $operation,
            'name' => $context['name'] ?? $operation,
            'description' => $context['description'] ?? null
        ]);

        Sentry::configureScope(function (Scope $scope) use ($context) {
            foreach ($context as $key => $value) {
                if (is_scalar($value)) {
                    $scope->setTag($key, $value);
                }
            }
        });

        try {
            $result = $callback();
            $transaction->setStatus('ok');
            return $result;
        } catch (\Throwable $e) {
            $transaction->setStatus('internal_error');
            $this->reportSeoAnalysisError($e, $context);
            throw $e;
        } finally {
            $transaction->finish();
        }
    }

    /**
     * Add breadcrumb for debugging
     */
    public function addBreadcrumb(string $message, string $category = 'default', array $data = []): void
    {
        Sentry::addBreadcrumb(
            message: $message,
            category: $category,
            data: $data,
            level: Severity::info()
        );
    }

    /**
     * Track API rate limiting issues
     */
    public function trackRateLimitIssue(string $service, array $rateLimitData): void
    {
        Sentry::configureScope(function (Scope $scope) use ($service, $rateLimitData) {
            $scope->setTag('error_category', 'rate_limit');
            $scope->setTag('external_service', $service);

            $scope->setContext('rate_limit', [
                'service' => $service,
                'limit' => $rateLimitData['limit'] ?? null,
                'remaining' => $rateLimitData['remaining'] ?? null,
                'reset_time' => $rateLimitData['reset_time'] ?? null,
                'current_usage' => $rateLimitData['current_usage'] ?? null
            ]);
        });

        $message = "Rate Limit Issue: {$service}";
        Sentry::captureMessage($message, Severity::warning());

        Log::warning('Rate limit encountered', [
            'service' => $service,
            'rate_limit_data' => $rateLimitData
        ]);
    }

    /**
     * Track security-related events
     */
    public function trackSecurityEvent(string $eventType, array $securityData): void
    {
        Sentry::configureScope(function (Scope $scope) use ($eventType, $securityData) {
            $scope->setTag('event_category', 'security');
            $scope->setTag('security_event_type', $eventType);

            $scope->setContext('security_event', [
                'event_type' => $eventType,
                'severity' => $securityData['severity'] ?? 'medium',
                'ip_address' => $securityData['ip_address'] ?? null,
                'user_agent' => $securityData['user_agent'] ?? null,
                'user_id' => $securityData['user_id'] ?? null,
                'additional_data' => $securityData['additional_data'] ?? []
            ]);
        });

        $severity = match ($securityData['severity'] ?? 'medium') {
            'low' => Severity::info(),
            'medium' => Severity::warning(),
            'high' => Severity::error(),
            'critical' => Severity::fatal(),
            default => Severity::warning()
        };

        $message = "Security Event: {$eventType}";
        Sentry::captureMessage($message, $severity);

        Log::warning('Security event detected', [
            'event_type' => $eventType,
            'security_data' => $securityData
        ]);
    }

    /**
     * Clear context after operation
     */
    public function clearContext(): void
    {
        Sentry::configureScope(function (Scope $scope) {
            $scope->clear();
        });
    }

    /**
     * Generate error report summary
     */
    public function generateErrorReport(string $period = '24h'): array
    {
        // This would typically integrate with Sentry API to fetch error data
        // For now, we'll return a structure that could be populated
        return [
            'period' => $period,
            'total_errors' => 0,
            'error_categories' => [
                'seo_analysis' => 0,
                'external_api' => 0,
                'database_performance' => 0,
                'cache_performance' => 0,
                'user_action' => 0,
                'security' => 0
            ],
            'top_errors' => [],
            'performance_impact' => [
                'affected_users' => 0,
                'affected_analyses' => 0,
                'avg_error_response_time' => 0
            ],
            'recommendations' => []
        ];
    }

    /**
     * Helper method to sanitize sensitive data before sending to Sentry
     */
    private function sanitizeContext(array $context): array
    {
        $sensitiveKeys = ['password', 'token', 'api_key', 'secret', 'credit_card'];

        return collect($context)->map(function ($value, $key) use ($sensitiveKeys) {
            if (in_array(strtolower($key), $sensitiveKeys)) {
                return '[REDACTED]';
            }

            if (is_array($value)) {
                return $this->sanitizeContext($value);
            }

            return $value;
        })->toArray();
    }
}