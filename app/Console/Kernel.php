<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Process scheduled reports every minute
        $schedule->command('reports:process-scheduled --queue')
                 ->everyMinute()
                 ->description('Process due scheduled SEO reports')
                 ->withoutOverlapping()
                 ->runInBackground();

        // Clean up old reports monthly
        $schedule->call(function () {
            app(\App\Services\Report\ReportGeneratorService::class)->cleanupOldReports(30);
        })->monthly()
          ->description('Clean up old report files')
          ->onOneServer();

        // Clean up old schedule logs monthly
        $schedule->call(function () {
            app(\App\Services\Report\ScheduledReportService::class)->cleanupOldLogs(90);
        })->monthly()
          ->description('Clean up old schedule execution logs')
          ->onOneServer();

        // Health check for scheduler (optional)
        $schedule->command('reports:process-scheduled --dry-run')
                 ->hourly()
                 ->description('Scheduler health check')
                 ->onOneServer();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}