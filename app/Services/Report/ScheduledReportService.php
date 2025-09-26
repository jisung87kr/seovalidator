<?php

namespace App\Services\Report;

use App\Jobs\GenerateScheduledReport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

/**
 * Scheduled Report Service
 *
 * Handles automated report scheduling, execution, and management.
 * Supports recurring reports with flexible scheduling options.
 */
class ScheduledReportService
{
    private ReportGeneratorService $reportGenerator;

    public function __construct(ReportGeneratorService $reportGenerator)
    {
        $this->reportGenerator = $reportGenerator;
    }

    /**
     * Create a new scheduled report configuration
     */
    public function createSchedule(array $scheduleData): array
    {
        $scheduleId = uniqid('schedule_', true);

        $schedule = [
            'schedule_id' => $scheduleId,
            'name' => $scheduleData['name'],
            'description' => $scheduleData['description'] ?? '',
            'urls' => $scheduleData['urls'],
            'frequency' => $scheduleData['frequency'], // daily, weekly, monthly
            'time' => $scheduleData['time'] ?? '09:00',
            'timezone' => $scheduleData['timezone'] ?? 'UTC',
            'formats' => $scheduleData['formats'] ?? ['pdf', 'excel'],
            'email_recipients' => $scheduleData['email_recipients'] ?? [],
            'template' => $scheduleData['template'] ?? 'standard',
            'combine_reports' => $scheduleData['combine_reports'] ?? true,
            'active' => $scheduleData['active'] ?? true,
            'created_at' => now(),
            'updated_at' => now(),
            'last_run' => null,
            'next_run' => $this->calculateNextRun($scheduleData['frequency'], $scheduleData['time'], $scheduleData['timezone']),
            'run_count' => 0,
            'user_id' => $scheduleData['user_id'] ?? null
        ];

        // Store schedule configuration
        $this->storeScheduleConfig($schedule);

        Log::info('Scheduled report created', [
            'schedule_id' => $scheduleId,
            'frequency' => $schedule['frequency'],
            'url_count' => count($schedule['urls']),
            'recipients' => count($schedule['email_recipients'])
        ]);

        return $schedule;
    }

    /**
     * Update existing schedule configuration
     */
    public function updateSchedule(string $scheduleId, array $updateData): array
    {
        $schedule = $this->getScheduleConfig($scheduleId);

        if (!$schedule) {
            throw new \InvalidArgumentException("Schedule {$scheduleId} not found");
        }

        // Update fields
        foreach ($updateData as $key => $value) {
            if (isset($schedule[$key])) {
                $schedule[$key] = $value;
            }
        }

        $schedule['updated_at'] = now();

        // Recalculate next run if frequency or time changed
        if (isset($updateData['frequency']) || isset($updateData['time']) || isset($updateData['timezone'])) {
            $schedule['next_run'] = $this->calculateNextRun(
                $schedule['frequency'],
                $schedule['time'],
                $schedule['timezone']
            );
        }

        $this->storeScheduleConfig($schedule);

        Log::info('Scheduled report updated', [
            'schedule_id' => $scheduleId,
            'updated_fields' => array_keys($updateData)
        ]);

        return $schedule;
    }

    /**
     * Execute scheduled report
     */
    public function executeSchedule(string $scheduleId): array
    {
        $schedule = $this->getScheduleConfig($scheduleId);

        if (!$schedule) {
            throw new \InvalidArgumentException("Schedule {$scheduleId} not found");
        }

        if (!$schedule['active']) {
            throw new \RuntimeException("Schedule {$scheduleId} is inactive");
        }

        Log::info('Executing scheduled report', [
            'schedule_id' => $scheduleId,
            'url_count' => count($schedule['urls'])
        ]);

        try {
            // Prepare URLs data for analysis
            $urlsData = array_map(function($url) {
                return ['url' => $url];
            }, $schedule['urls']);

            // Generate the report
            $result = $this->reportGenerator->generateScheduledReport($schedule);

            // Update schedule metadata
            $schedule['last_run'] = now();
            $schedule['next_run'] = $this->calculateNextRun(
                $schedule['frequency'],
                $schedule['time'],
                $schedule['timezone']
            );
            $schedule['run_count']++;
            $schedule['last_result'] = [
                'success' => true,
                'total_reports' => count($result['reports']),
                'successful_reports' => count(array_filter($result['reports'], fn($r) => $r['success'] ?? true)),
                'generated_at' => now()
            ];

            $this->storeScheduleConfig($schedule);

            // Store execution log
            $this->logScheduleExecution($scheduleId, $result, true);

            Log::info('Scheduled report executed successfully', [
                'schedule_id' => $scheduleId,
                'batch_id' => $result['batch_id'],
                'total_reports' => count($result['reports'])
            ]);

            return $result;

        } catch (\Exception $e) {
            // Update schedule with error
            $schedule['last_run'] = now();
            $schedule['last_result'] = [
                'success' => false,
                'error' => $e->getMessage(),
                'generated_at' => now()
            ];

            $this->storeScheduleConfig($schedule);

            // Log the failure
            $this->logScheduleExecution($scheduleId, null, false, $e->getMessage());

            Log::error('Scheduled report execution failed', [
                'schedule_id' => $scheduleId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Get all due schedules for execution
     */
    public function getDueSchedules(): array
    {
        $scheduleFiles = Storage::files('schedules');
        $dueSchedules = [];
        $now = now();

        foreach ($scheduleFiles as $file) {
            $schedule = json_decode(Storage::get($file), true);

            if ($schedule && $schedule['active'] && isset($schedule['next_run'])) {
                $nextRun = Carbon::parse($schedule['next_run']);

                if ($nextRun->lte($now)) {
                    $dueSchedules[] = $schedule;
                }
            }
        }

        Log::info('Found due schedules', [
            'count' => count($dueSchedules)
        ]);

        return $dueSchedules;
    }

    /**
     * Execute all due schedules
     */
    public function executeDueSchedules(): array
    {
        $dueSchedules = $this->getDueSchedules();
        $results = [];

        Log::info('Executing due scheduled reports', [
            'count' => count($dueSchedules)
        ]);

        foreach ($dueSchedules as $schedule) {
            try {
                $result = $this->executeSchedule($schedule['schedule_id']);
                $results[$schedule['schedule_id']] = [
                    'success' => true,
                    'result' => $result
                ];

            } catch (\Exception $e) {
                $results[$schedule['schedule_id']] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];

                Log::warning('Failed to execute scheduled report', [
                    'schedule_id' => $schedule['schedule_id'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * Queue scheduled report for background execution
     */
    public function queueSchedule(string $scheduleId): void
    {
        $schedule = $this->getScheduleConfig($scheduleId);

        if (!$schedule) {
            throw new \InvalidArgumentException("Schedule {$scheduleId} not found");
        }

        GenerateScheduledReport::dispatch($schedule)->onQueue('reporting');

        Log::info('Scheduled report queued for execution', [
            'schedule_id' => $scheduleId
        ]);
    }

    /**
     * Get schedule configuration
     */
    public function getScheduleConfig(string $scheduleId): ?array
    {
        $filename = "schedules/{$scheduleId}.json";

        if (!Storage::exists($filename)) {
            return null;
        }

        return json_decode(Storage::get($filename), true);
    }

    /**
     * Get all schedules for a user
     */
    public function getUserSchedules(int $userId): array
    {
        $scheduleFiles = Storage::files('schedules');
        $userSchedules = [];

        foreach ($scheduleFiles as $file) {
            $schedule = json_decode(Storage::get($file), true);

            if ($schedule && ($schedule['user_id'] ?? null) == $userId) {
                $userSchedules[] = $schedule;
            }
        }

        // Sort by creation date (newest first)
        usort($userSchedules, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

        return $userSchedules;
    }

    /**
     * Delete schedule
     */
    public function deleteSchedule(string $scheduleId): bool
    {
        $filename = "schedules/{$scheduleId}.json";

        if (!Storage::exists($filename)) {
            return false;
        }

        Storage::delete($filename);

        // Also delete execution logs
        $logFiles = Storage::files("schedule-logs/{$scheduleId}");
        foreach ($logFiles as $logFile) {
            Storage::delete($logFile);
        }

        Log::info('Scheduled report deleted', [
            'schedule_id' => $scheduleId
        ]);

        return true;
    }

    /**
     * Activate/deactivate schedule
     */
    public function toggleSchedule(string $scheduleId, bool $active): array
    {
        $schedule = $this->updateSchedule($scheduleId, ['active' => $active]);

        Log::info('Schedule toggled', [
            'schedule_id' => $scheduleId,
            'active' => $active
        ]);

        return $schedule;
    }

    /**
     * Get schedule execution history
     */
    public function getScheduleHistory(string $scheduleId, int $limit = 50): array
    {
        $logFiles = Storage::files("schedule-logs/{$scheduleId}");
        $history = [];

        foreach ($logFiles as $logFile) {
            $log = json_decode(Storage::get($logFile), true);
            if ($log) {
                $history[] = $log;
            }
        }

        // Sort by execution time (newest first)
        usort($history, fn($a, $b) => strtotime($b['executed_at']) - strtotime($a['executed_at']));

        return array_slice($history, 0, $limit);
    }

    /**
     * Get schedule statistics
     */
    public function getScheduleStats(string $scheduleId): array
    {
        $schedule = $this->getScheduleConfig($scheduleId);
        $history = $this->getScheduleHistory($scheduleId);

        if (!$schedule) {
            return [];
        }

        $successfulRuns = count(array_filter($history, fn($h) => $h['success']));
        $failedRuns = count($history) - $successfulRuns;
        $successRate = count($history) > 0 ? round(($successfulRuns / count($history)) * 100, 1) : 0;

        $totalReports = 0;
        $totalIssues = 0;
        foreach ($history as $run) {
            if ($run['success'] && isset($run['result'])) {
                $totalReports += count($run['result']['reports'] ?? []);
                foreach ($run['result']['reports'] ?? [] as $report) {
                    $totalIssues += $report['summary']['total_issues'] ?? 0;
                }
            }
        }

        return [
            'schedule_id' => $scheduleId,
            'total_runs' => count($history),
            'successful_runs' => $successfulRuns,
            'failed_runs' => $failedRuns,
            'success_rate' => $successRate,
            'total_reports_generated' => $totalReports,
            'total_issues_found' => $totalIssues,
            'average_issues_per_run' => $successfulRuns > 0 ? round($totalIssues / $successfulRuns, 1) : 0,
            'last_run' => $schedule['last_run'],
            'next_run' => $schedule['next_run'],
            'created_at' => $schedule['created_at']
        ];
    }

    /**
     * Private helper methods
     */

    private function storeScheduleConfig(array $schedule): void
    {
        $filename = "schedules/{$schedule['schedule_id']}.json";
        Storage::put($filename, json_encode($schedule, JSON_PRETTY_PRINT));
    }

    private function calculateNextRun(string $frequency, string $time, string $timezone): string
    {
        $now = now($timezone);
        $timeparts = explode(':', $time);
        $hour = (int)$timeparts[0];
        $minute = (int)($timeparts[1] ?? 0);

        switch ($frequency) {
            case 'daily':
                $nextRun = $now->copy()->addDay()->setTime($hour, $minute, 0);
                break;

            case 'weekly':
                $nextRun = $now->copy()->addWeek()->setTime($hour, $minute, 0);
                break;

            case 'monthly':
                $nextRun = $now->copy()->addMonth()->setTime($hour, $minute, 0);
                break;

            case 'hourly':
                $nextRun = $now->copy()->addHour()->setMinute($minute)->setSecond(0);
                break;

            default:
                throw new \InvalidArgumentException("Invalid frequency: {$frequency}");
        }

        // Ensure next run is in the future
        if ($nextRun->lte($now)) {
            switch ($frequency) {
                case 'daily':
                    $nextRun->addDay();
                    break;
                case 'weekly':
                    $nextRun->addWeek();
                    break;
                case 'monthly':
                    $nextRun->addMonth();
                    break;
                case 'hourly':
                    $nextRun->addHour();
                    break;
            }
        }

        return $nextRun->utc()->toISOString();
    }

    private function logScheduleExecution(string $scheduleId, ?array $result, bool $success, ?string $error = null): void
    {
        $executionId = uniqid('exec_', true);

        $log = [
            'execution_id' => $executionId,
            'schedule_id' => $scheduleId,
            'executed_at' => now()->toISOString(),
            'success' => $success,
            'result' => $result,
            'error' => $error,
            'duration_seconds' => 0 // Would be calculated in real implementation
        ];

        $filename = "schedule-logs/{$scheduleId}/{$executionId}.json";
        Storage::put($filename, json_encode($log, JSON_PRETTY_PRINT));
    }

    /**
     * Cleanup old execution logs
     */
    public function cleanupOldLogs(int $daysOld = 90): int
    {
        $cutoffDate = now()->subDays($daysOld);
        $deletedCount = 0;

        $scheduleLogDirs = Storage::directories('schedule-logs');

        foreach ($scheduleLogDirs as $dir) {
            $logFiles = Storage::files($dir);

            foreach ($logFiles as $file) {
                $log = json_decode(Storage::get($file), true);

                if ($log && isset($log['executed_at'])) {
                    $executedAt = Carbon::parse($log['executed_at']);

                    if ($executedAt->lt($cutoffDate)) {
                        Storage::delete($file);
                        $deletedCount++;
                    }
                }
            }
        }

        Log::info('Completed schedule log cleanup', [
            'cutoff_date' => $cutoffDate->toISOString(),
            'deleted_count' => $deletedCount
        ]);

        return $deletedCount;
    }
}