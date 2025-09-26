<?php

namespace App\Jobs;

use App\Services\Report\ScheduledReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateScheduledReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 2;
    public $backoff = 30;

    private array $scheduleConfig;

    /**
     * Create a new job instance.
     */
    public function __construct(array $scheduleConfig)
    {
        $this->scheduleConfig = $scheduleConfig;
        $this->onQueue('reporting');
    }

    /**
     * Execute the job.
     */
    public function handle(ScheduledReportService $scheduledReportService): void
    {
        $scheduleId = $this->scheduleConfig['schedule_id'];

        Log::info('Processing scheduled report job', [
            'schedule_id' => $scheduleId,
            'job_id' => $this->job->getJobId()
        ]);

        try {
            $result = $scheduledReportService->executeSchedule($scheduleId);

            Log::info('Scheduled report job completed successfully', [
                'schedule_id' => $scheduleId,
                'batch_id' => $result['batch_id'],
                'total_reports' => count($result['reports'])
            ]);

        } catch (\Exception $e) {
            Log::error('Scheduled report job failed', [
                'schedule_id' => $scheduleId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateScheduledReport job failed permanently', [
            'schedule_id' => $this->scheduleConfig['schedule_id'],
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'scheduled-reporting',
            'schedule:' . $this->scheduleConfig['schedule_id'],
            'frequency:' . $this->scheduleConfig['frequency']
        ];
    }
}