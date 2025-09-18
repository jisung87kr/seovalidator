<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SystemController extends BaseApiController
{
    /**
     * Health check endpoint
     */
    public function health(): JsonResponse
    {
        try {
            // Check database connection
            DB::connection()->getPdo();
            $databaseStatus = 'healthy';
        } catch (\Exception $e) {
            $databaseStatus = 'unhealthy';
        }

        // Check cache connection
        try {
            Cache::put('health_check', 'ok', 10);
            $cacheStatus = Cache::get('health_check') === 'ok' ? 'healthy' : 'unhealthy';
        } catch (\Exception $e) {
            $cacheStatus = 'unhealthy';
        }

        $isHealthy = $databaseStatus === 'healthy' && $cacheStatus === 'healthy';

        $data = [
            'status' => $isHealthy ? 'healthy' : 'unhealthy',
            'services' => [
                'database' => $databaseStatus,
                'cache' => $cacheStatus
            ],
            'uptime' => $this->getUptime()
        ];

        return $this->success($data, 'Health check completed', $isHealthy ? 200 : 503);
    }

    /**
     * System status endpoint
     */
    public function status(): JsonResponse
    {
        $data = [
            'version' => config('app.version', '1.0.0'),
            'environment' => config('app.env'),
            'debug' => config('app.debug'),
            'timezone' => config('app.timezone'),
            'locale' => config('app.locale'),
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'memory_usage' => [
                'current' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true)
            ]
        ];

        return $this->success($data, 'System status retrieved successfully');
    }

    /**
     * Get system uptime (simplified)
     */
    private function getUptime(): string
    {
        if (function_exists('sys_getloadavg')) {
            $uptime = file_get_contents('/proc/uptime');
            if ($uptime) {
                $uptimeSeconds = (int) explode(' ', trim($uptime))[0];
                return gmdate('H:i:s', $uptimeSeconds);
            }
        }
        
        return 'unknown';
    }
}