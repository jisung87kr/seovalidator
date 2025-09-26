<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Queue;
use App\Jobs\CrawlUrl;

class TestQueueSetup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:queue-setup {--url=https://example.com}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the queue setup and SEO analysis jobs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Queue and Horizon Setup for SEO Validator');
        $this->newLine();

        // Test Redis connection
        try {
            $this->info('Testing Redis connection...');
            $redis = Redis::connection();
            $redis->ping();
            $this->info('✅ Redis connection successful');
        } catch (\Exception $e) {
            $this->error('❌ Redis connection failed: ' . $e->getMessage());
            return 1;
        }

        // Test queue configuration
        try {
            $this->info('Testing queue configuration...');
            $driver = config('queue.default');
            $this->info("✅ Queue driver: {$driver}");

            if ($driver === 'redis') {
                $connection = config('queue.connections.redis.connection');
                $this->info("✅ Queue Redis connection: {$connection}");
            }
        } catch (\Exception $e) {
            $this->error('❌ Queue configuration test failed: ' . $e->getMessage());
            return 1;
        }

        // Test Horizon configuration
        try {
            $this->info('Testing Horizon configuration...');
            $horizonConnection = config('horizon.use');
            $this->info("✅ Horizon Redis connection: {$horizonConnection}");

            $supervisors = array_keys(config('horizon.defaults'));
            $this->info('✅ Configured supervisors: ' . implode(', ', $supervisors));
        } catch (\Exception $e) {
            $this->error('❌ Horizon configuration test failed: ' . $e->getMessage());
            return 1;
        }

        // Test job dispatch
        try {
            $url = $this->option('url');
            $this->info("Testing job dispatch for URL: {$url}");

            // Dispatch the job
            CrawlUrl::dispatch($url, 1, ['test' => true]);
            $this->info('✅ SEO analysis job dispatched successfully');

            $this->newLine();
            $this->info('Job has been queued. To process it, run:');
            $this->comment('php artisan horizon');
            $this->info('Or for a single worker:');
            $this->comment('php artisan queue:work redis --queue=seo_crawling');

            $this->newLine();
            $this->info('Monitor the job at: http://localhost:8000/horizon');

        } catch (\Exception $e) {
            $this->error('❌ Job dispatch test failed: ' . $e->getMessage());
            return 1;
        }

        $this->newLine();
        $this->info('🎉 All queue setup tests passed!');

        return 0;
    }
}
