<?php

namespace Tests\Unit\Services\Report;

use App\Services\Report\ScheduledReportService;
use App\Services\Report\ReportGeneratorService;
use App\Jobs\GenerateScheduledReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Mockery;
use Carbon\Carbon;

class ScheduledReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private ScheduledReportService $service;
    private $mockReportGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock
        $this->mockReportGenerator = Mockery::mock(ReportGeneratorService::class);

        // Create service with mock
        $this->service = new ScheduledReportService($this->mockReportGenerator);

        // Setup storage fake
        Storage::fake('local');

        // Fake queue for testing
        Queue::fake();
    }

    public function test_can_create_schedule()
    {
        // Arrange
        $scheduleData = [
            'name' => 'Daily SEO Report',
            'description' => 'Daily monitoring of key pages',
            'urls' => ['https://example.com', 'https://example.com/about'],
            'frequency' => 'daily',
            'time' => '09:00',
            'timezone' => 'UTC',
            'formats' => ['pdf', 'excel'],
            'email_recipients' => ['admin@example.com'],
            'template' => 'standard',
            'combine_reports' => true,
            'active' => true,
            'user_id' => 1
        ];

        // Act
        $schedule = $this->service->createSchedule($scheduleData);

        // Assert
        $this->assertArrayHasKey('schedule_id', $schedule);
        $this->assertArrayHasKey('next_run', $schedule);
        $this->assertEquals('Daily SEO Report', $schedule['name']);
        $this->assertEquals('daily', $schedule['frequency']);
        $this->assertEquals(2, count($schedule['urls']));
        $this->assertEquals(1, count($schedule['email_recipients']));
        $this->assertTrue($schedule['active']);
        $this->assertEquals(0, $schedule['run_count']);

        // Verify schedule was stored
        $this->assertTrue(Storage::exists("schedules/{$schedule['schedule_id']}.json"));
    }

    public function test_can_update_schedule()
    {
        // Arrange
        $schedule = $this->createSampleSchedule();
        $scheduleId = $schedule['schedule_id'];

        $updateData = [
            'name' => 'Updated Report Name',
            'frequency' => 'weekly',
            'active' => false
        ];

        // Act
        $updatedSchedule = $this->service->updateSchedule($scheduleId, $updateData);

        // Assert
        $this->assertEquals('Updated Report Name', $updatedSchedule['name']);
        $this->assertEquals('weekly', $updatedSchedule['frequency']);
        $this->assertFalse($updatedSchedule['active']);
        $this->assertNotEquals($schedule['next_run'], $updatedSchedule['next_run']); // Should recalculate
    }

    public function test_can_execute_schedule()
    {
        // Arrange
        $schedule = $this->createSampleSchedule();
        $scheduleId = $schedule['schedule_id'];

        $expectedResult = [
            'batch_id' => 'batch_123',
            'reports' => [
                ['url' => 'https://example.com', 'success' => true],
                ['url' => 'https://example.com/about', 'success' => true]
            ],
            'total_urls' => 2,
            'generated_at' => now()
        ];

        $this->mockReportGenerator
            ->shouldReceive('generateScheduledReport')
            ->once()
            ->with(Mockery::type('array'))
            ->andReturn($expectedResult);

        // Act
        $result = $this->service->executeSchedule($scheduleId);

        // Assert
        $this->assertEquals($expectedResult, $result);

        // Verify schedule was updated
        $updatedSchedule = $this->service->getScheduleConfig($scheduleId);
        $this->assertNotNull($updatedSchedule['last_run']);
        $this->assertEquals(1, $updatedSchedule['run_count']);
        $this->assertTrue($updatedSchedule['last_result']['success']);
    }

    public function test_execute_schedule_handles_failure()
    {
        // Arrange
        $schedule = $this->createSampleSchedule();
        $scheduleId = $schedule['schedule_id'];

        $this->mockReportGenerator
            ->shouldReceive('generateScheduledReport')
            ->once()
            ->andThrow(new \Exception('Report generation failed'));

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Report generation failed');

        try {
            $this->service->executeSchedule($scheduleId);
        } finally {
            // Verify failure was recorded
            $updatedSchedule = $this->service->getScheduleConfig($scheduleId);
            $this->assertNotNull($updatedSchedule['last_run']);
            $this->assertFalse($updatedSchedule['last_result']['success']);
            $this->assertEquals('Report generation failed', $updatedSchedule['last_result']['error']);
        }
    }

    public function test_cannot_execute_inactive_schedule()
    {
        // Arrange
        $schedule = $this->createSampleSchedule(['active' => false]);
        $scheduleId = $schedule['schedule_id'];

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('inactive');

        $this->service->executeSchedule($scheduleId);
    }

    public function test_can_get_due_schedules()
    {
        // Arrange
        $pastTime = now()->subHour()->toISOString();
        $futureTime = now()->addHour()->toISOString();

        $dueSchedule = $this->createSampleSchedule(['next_run' => $pastTime]);
        $notDueSchedule = $this->createSampleSchedule(['next_run' => $futureTime]);

        // Act
        $dueSchedules = $this->service->getDueSchedules();

        // Assert
        $this->assertCount(1, $dueSchedules);
        $this->assertEquals($dueSchedule['schedule_id'], $dueSchedules[0]['schedule_id']);
    }

    public function test_can_execute_due_schedules()
    {
        // Arrange
        $pastTime = now()->subHour()->toISOString();
        $schedule1 = $this->createSampleSchedule(['next_run' => $pastTime]);
        $schedule2 = $this->createSampleSchedule(['next_run' => $pastTime]);

        $this->mockReportGenerator
            ->shouldReceive('generateScheduledReport')
            ->twice()
            ->andReturn([
                'batch_id' => 'batch_123',
                'reports' => [],
                'total_urls' => 0,
                'generated_at' => now()
            ]);

        // Act
        $results = $this->service->executeDueSchedules();

        // Assert
        $this->assertCount(2, $results);
        $this->assertTrue($results[$schedule1['schedule_id']]['success']);
        $this->assertTrue($results[$schedule2['schedule_id']]['success']);
    }

    public function test_can_queue_schedule()
    {
        // Arrange
        $schedule = $this->createSampleSchedule();
        $scheduleId = $schedule['schedule_id'];

        // Act
        $this->service->queueSchedule($scheduleId);

        // Assert
        Queue::assertPushed(GenerateScheduledReport::class, function ($job) use ($schedule) {
            return $job->scheduleConfig['schedule_id'] === $schedule['schedule_id'];
        });
    }

    public function test_can_get_user_schedules()
    {
        // Arrange
        $userId = 1;
        $otherUserId = 2;

        $userSchedule1 = $this->createSampleSchedule(['user_id' => $userId]);
        $userSchedule2 = $this->createSampleSchedule(['user_id' => $userId]);
        $otherUserSchedule = $this->createSampleSchedule(['user_id' => $otherUserId]);

        // Act
        $userSchedules = $this->service->getUserSchedules($userId);

        // Assert
        $this->assertCount(2, $userSchedules);
        $scheduleIds = array_column($userSchedules, 'schedule_id');
        $this->assertContains($userSchedule1['schedule_id'], $scheduleIds);
        $this->assertContains($userSchedule2['schedule_id'], $scheduleIds);
        $this->assertNotContains($otherUserSchedule['schedule_id'], $scheduleIds);
    }

    public function test_can_delete_schedule()
    {
        // Arrange
        $schedule = $this->createSampleSchedule();
        $scheduleId = $schedule['schedule_id'];

        // Create some execution logs
        Storage::put("schedule-logs/{$scheduleId}/exec_1.json", '{"execution_id":"exec_1"}');
        Storage::put("schedule-logs/{$scheduleId}/exec_2.json", '{"execution_id":"exec_2"}');

        // Act
        $result = $this->service->deleteSchedule($scheduleId);

        // Assert
        $this->assertTrue($result);
        $this->assertFalse(Storage::exists("schedules/{$scheduleId}.json"));
        $this->assertFalse(Storage::exists("schedule-logs/{$scheduleId}/exec_1.json"));
        $this->assertFalse(Storage::exists("schedule-logs/{$scheduleId}/exec_2.json"));
    }

    public function test_can_toggle_schedule()
    {
        // Arrange
        $schedule = $this->createSampleSchedule(['active' => true]);
        $scheduleId = $schedule['schedule_id'];

        // Act - Deactivate
        $deactivatedSchedule = $this->service->toggleSchedule($scheduleId, false);

        // Assert
        $this->assertFalse($deactivatedSchedule['active']);

        // Act - Activate
        $activatedSchedule = $this->service->toggleSchedule($scheduleId, true);

        // Assert
        $this->assertTrue($activatedSchedule['active']);
    }

    public function test_can_get_schedule_history()
    {
        // Arrange
        $schedule = $this->createSampleSchedule();
        $scheduleId = $schedule['schedule_id'];

        // Create execution logs
        $log1 = [
            'execution_id' => 'exec_1',
            'schedule_id' => $scheduleId,
            'executed_at' => now()->subDay()->toISOString(),
            'success' => true
        ];

        $log2 = [
            'execution_id' => 'exec_2',
            'schedule_id' => $scheduleId,
            'executed_at' => now()->toISOString(),
            'success' => false,
            'error' => 'Test error'
        ];

        Storage::put("schedule-logs/{$scheduleId}/exec_1.json", json_encode($log1));
        Storage::put("schedule-logs/{$scheduleId}/exec_2.json", json_encode($log2));

        // Act
        $history = $this->service->getScheduleHistory($scheduleId);

        // Assert
        $this->assertCount(2, $history);
        $this->assertEquals('exec_2', $history[0]['execution_id']); // Newest first
        $this->assertEquals('exec_1', $history[1]['execution_id']);
    }

    public function test_can_get_schedule_stats()
    {
        // Arrange
        $schedule = $this->createSampleSchedule(['run_count' => 5]);
        $scheduleId = $schedule['schedule_id'];

        // Create execution history
        $successLog = [
            'execution_id' => 'exec_success',
            'success' => true,
            'result' => [
                'reports' => [
                    ['summary' => ['total_issues' => 3]],
                    ['summary' => ['total_issues' => 5]]
                ]
            ]
        ];

        $failureLog = [
            'execution_id' => 'exec_failure',
            'success' => false,
            'error' => 'Test error'
        ];

        Storage::put("schedule-logs/{$scheduleId}/exec_success.json", json_encode($successLog));
        Storage::put("schedule-logs/{$scheduleId}/exec_failure.json", json_encode($failureLog));

        // Act
        $stats = $this->service->getScheduleStats($scheduleId);

        // Assert
        $this->assertEquals($scheduleId, $stats['schedule_id']);
        $this->assertEquals(2, $stats['total_runs']);
        $this->assertEquals(1, $stats['successful_runs']);
        $this->assertEquals(1, $stats['failed_runs']);
        $this->assertEquals(50.0, $stats['success_rate']);
        $this->assertEquals(2, $stats['total_reports_generated']);
        $this->assertEquals(8, $stats['total_issues_found']);
        $this->assertEquals(8.0, $stats['average_issues_per_run']);
    }

    public function test_can_cleanup_old_logs()
    {
        // Arrange
        $scheduleId = 'test_schedule';
        $oldDate = now()->subDays(100)->toISOString();
        $newDate = now()->subDays(30)->toISOString();

        $oldLog = ['executed_at' => $oldDate];
        $newLog = ['executed_at' => $newDate];

        Storage::put("schedule-logs/{$scheduleId}/old_log.json", json_encode($oldLog));
        Storage::put("schedule-logs/{$scheduleId}/new_log.json", json_encode($newLog));

        // Act
        $deletedCount = $this->service->cleanupOldLogs(90);

        // Assert
        $this->assertEquals(1, $deletedCount);
        $this->assertFalse(Storage::exists("schedule-logs/{$scheduleId}/old_log.json"));
        $this->assertTrue(Storage::exists("schedule-logs/{$scheduleId}/new_log.json"));
    }

    public function test_calculates_next_run_correctly()
    {
        // Test daily frequency
        $dailySchedule = $this->createSampleSchedule([
            'frequency' => 'daily',
            'time' => '09:00'
        ]);

        $nextRun = Carbon::parse($dailySchedule['next_run']);
        $this->assertEquals(9, $nextRun->hour);
        $this->assertEquals(0, $nextRun->minute);
        $this->assertTrue($nextRun->isFuture());

        // Test weekly frequency
        $weeklySchedule = $this->createSampleSchedule([
            'frequency' => 'weekly',
            'time' => '14:30'
        ]);

        $nextRun = Carbon::parse($weeklySchedule['next_run']);
        $this->assertEquals(14, $nextRun->hour);
        $this->assertEquals(30, $nextRun->minute);
        $this->assertTrue($nextRun->isFuture());

        // Test monthly frequency
        $monthlySchedule = $this->createSampleSchedule([
            'frequency' => 'monthly',
            'time' => '08:00'
        ]);

        $nextRun = Carbon::parse($monthlySchedule['next_run']);
        $this->assertEquals(8, $nextRun->hour);
        $this->assertEquals(0, $nextRun->minute);
        $this->assertTrue($nextRun->isFuture());
    }

    public function test_throws_exception_for_invalid_frequency()
    {
        // Arrange & Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid frequency');

        $this->createSampleSchedule(['frequency' => 'invalid']);
    }

    /**
     * Private helper methods
     */

    private function createSampleSchedule(array $overrides = []): array
    {
        $defaultData = [
            'name' => 'Test Schedule',
            'description' => 'Test description',
            'urls' => ['https://example.com', 'https://example.com/about'],
            'frequency' => 'daily',
            'time' => '09:00',
            'timezone' => 'UTC',
            'formats' => ['pdf'],
            'email_recipients' => ['test@example.com'],
            'template' => 'standard',
            'combine_reports' => true,
            'active' => true,
            'user_id' => 1
        ];

        $scheduleData = array_merge($defaultData, $overrides);
        return $this->service->createSchedule($scheduleData);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}