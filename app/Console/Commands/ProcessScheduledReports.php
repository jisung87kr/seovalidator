<?php

namespace App\Console\Commands;

use App\Services\Report\ScheduledReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessScheduledReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:process-scheduled
                            {--queue : Queue reports instead of executing immediately}
                            {--schedule-id= : Process specific schedule ID only}
                            {--dry-run : Show what would be executed without running}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process scheduled SEO reports that are due for execution';

    private ScheduledReportService $scheduledReportService;

    public function __construct(ScheduledReportService $scheduledReportService)
    {
        parent::__construct();
        $this->scheduledReportService = $scheduledReportService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Processing scheduled SEO reports...');

        try {
            if ($scheduleId = $this->option('schedule-id')) {
                $this->processSpecificSchedule($scheduleId);
            } else {
                $this->processAllDueSchedules();
            }

        } catch (\Exception $e) {
            $this->error("Failed to process scheduled reports: {$e->getMessage()}");
            Log::error('ProcessScheduledReports command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }

    /**
     * Process a specific schedule
     */
    private function processSpecificSchedule(string $scheduleId): void
    {
        $this->info("Processing specific schedule: {$scheduleId}");

        $schedule = $this->scheduledReportService->getScheduleConfig($scheduleId);
        if (!$schedule) {
            $this->error("Schedule {$scheduleId} not found");
            return;
        }

        if ($this->option('dry-run')) {
            $this->showScheduleInfo($schedule);
            return;
        }

        if ($this->option('queue')) {
            $this->scheduledReportService->queueSchedule($scheduleId);
            $this->info("✅ Schedule {$scheduleId} queued for execution");
        } else {
            $this->executeScheduleWithProgress($schedule);
        }
    }

    /**
     * Process all due schedules
     */
    private function processAllDueSchedules(): void
    {
        $dueSchedules = $this->scheduledReportService->getDueSchedules();

        if (empty($dueSchedules)) {
            $this->info('✅ No scheduled reports are due for execution');
            return;
        }

        $this->info("Found {count($dueSchedules)} scheduled report(s) due for execution");

        if ($this->option('dry-run')) {
            $this->showDueSchedules($dueSchedules);
            return;
        }

        if ($this->option('queue')) {
            $this->queueAllSchedules($dueSchedules);
        } else {
            $this->executeAllSchedules($dueSchedules);
        }
    }

    /**
     * Show due schedules in dry-run mode
     */
    private function showDueSchedules(array $schedules): void
    {
        $this->info('📋 Schedules that would be executed:');

        $headers = ['Schedule ID', 'Name', 'Frequency', 'URLs', 'Recipients', 'Next Run'];
        $rows = [];

        foreach ($schedules as $schedule) {
            $rows[] = [
                $schedule['schedule_id'],
                $schedule['name'],
                $schedule['frequency'],
                count($schedule['urls']),
                count($schedule['email_recipients']),
                $schedule['next_run']
            ];
        }

        $this->table($headers, $rows);
    }

    /**
     * Show specific schedule info
     */
    private function showScheduleInfo(array $schedule): void
    {
        $this->info('📋 Schedule Information:');
        $this->line("ID: {$schedule['schedule_id']}");
        $this->line("Name: {$schedule['name']}");
        $this->line("Frequency: {$schedule['frequency']}");
        $this->line("URLs: " . count($schedule['urls']));
        $this->line("Recipients: " . count($schedule['email_recipients']));
        $this->line("Active: " . ($schedule['active'] ? 'Yes' : 'No'));
        $this->line("Next Run: {$schedule['next_run']}");
        $this->line("Last Run: " . ($schedule['last_run'] ?? 'Never'));
    }

    /**
     * Queue all due schedules
     */
    private function queueAllSchedules(array $schedules): void
    {
        $this->info('📤 Queueing scheduled reports...');

        $progressBar = $this->output->createProgressBar(count($schedules));
        $progressBar->start();

        $queuedCount = 0;
        foreach ($schedules as $schedule) {
            try {
                $this->scheduledReportService->queueSchedule($schedule['schedule_id']);
                $queuedCount++;
            } catch (\Exception $e) {
                $this->newLine();
                $this->warn("Failed to queue schedule {$schedule['schedule_id']}: {$e->getMessage()}");
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("✅ Queued {$queuedCount} out of " . count($schedules) . " scheduled reports");
    }

    /**
     * Execute all due schedules immediately
     */
    private function executeAllSchedules(array $schedules): void
    {
        $this->info('⚡ Executing scheduled reports immediately...');

        $successCount = 0;
        $failureCount = 0;

        foreach ($schedules as $schedule) {
            try {
                $this->executeScheduleWithProgress($schedule);
                $successCount++;
            } catch (\Exception $e) {
                $this->error("Failed to execute schedule {$schedule['schedule_id']}: {$e->getMessage()}");
                $failureCount++;
            }
        }

        $this->newLine();
        $this->info("✅ Execution Summary:");
        $this->line("  Successful: {$successCount}");
        if ($failureCount > 0) {
            $this->line("  Failed: {$failureCount}");
        }
    }

    /**
     * Execute a schedule with progress indication
     */
    private function executeScheduleWithProgress(array $schedule): void
    {
        $scheduleId = $schedule['schedule_id'];
        $urlCount = count($schedule['urls']);

        $this->info("🔄 Executing schedule: {$schedule['name']} ({$scheduleId})");
        $this->line("  URLs to analyze: {$urlCount}");
        $this->line("  Recipients: " . count($schedule['email_recipients']));

        $progressBar = $this->output->createProgressBar($urlCount);
        $progressBar->setFormat('  [%bar%] %current%/%max% URLs analyzed (%percent%%)');
        $progressBar->start();

        // Simulate progress (in real implementation, this would track actual progress)
        for ($i = 0; $i < $urlCount; $i++) {
            usleep(100000); // 100ms delay for demo
            $progressBar->advance();
        }

        // Execute the actual report
        $result = $this->scheduledReportService->executeSchedule($scheduleId);

        $progressBar->finish();
        $this->newLine();

        $successfulReports = count(array_filter($result['reports'], fn($r) => $r['success'] ?? true));
        $this->info("✅ Schedule executed successfully");
        $this->line("  Batch ID: {$result['batch_id']}");
        $this->line("  Successful reports: {$successfulReports}/{$urlCount}");

        if ($successfulReports < $urlCount) {
            $failedCount = $urlCount - $successfulReports;
            $this->warn("  Failed reports: {$failedCount}");
        }
    }
}
