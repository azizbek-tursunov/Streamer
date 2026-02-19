<?php

namespace App\Jobs;

use App\Services\HemisIntegrations\HemisApiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncLessonSchedules implements ShouldQueue
{
    use Queueable;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct() {}

    /**
     * Execute the job.
     */
    public function handle(HemisApiService $hemisApi): void
    {
        Log::info('SyncLessonSchedules: Starting daily lesson schedule sync.');

        try {
            $count = $hemisApi->syncSchedules();

            Log::info("SyncLessonSchedules: Successfully synced {$count} lesson schedules.");
        } catch (\Exception $e) {
            Log::error('SyncLessonSchedules: Failed to sync.', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
