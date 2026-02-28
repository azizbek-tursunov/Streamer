<?php

use App\Jobs\CaptureSnapshot;
use App\Jobs\DispatchPeopleCounting;
use App\Jobs\PruneSnapshots;
use App\Jobs\SyncLessonSchedules;
use App\Models\Camera;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule camera snapshots every 5 minutes (staggered to avoid 200 simultaneous processes)
Schedule::call(function () {
    Camera::where('is_active', true)->get()->each(function (Camera $camera, int $index) {
        CaptureSnapshot::dispatch($camera)
            ->delay(now()->addSeconds($index)); // 1 second apart
    });
})->everyFiveMinutes();

// Count people via YOLO every 5 minutes
Schedule::job(new DispatchPeopleCounting)->everyFiveMinutes();

// Prune old snapshots every hour
Schedule::job(new PruneSnapshots)->hourly();

// Sync lesson schedules from HEMIS daily at 3:00 AM
Schedule::job(new SyncLessonSchedules)->dailyAt('03:00');
