<?php

use App\Jobs\CaptureSnapshot;
use App\Jobs\PruneSnapshots;
use App\Jobs\SyncLessonSchedules;
use App\Models\Camera;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule camera snapshots every 5 minutes
Schedule::call(function () {
    Camera::where('is_active', true)->chunk(10, function ($cameras) {
        foreach ($cameras as $camera) {
            CaptureSnapshot::dispatch($camera);
        }
    });
})->everyFiveMinutes();

// Prune old snapshots every hour
Schedule::job(new PruneSnapshots)->hourly();

// Sync lesson schedules from HEMIS daily at 3:00 AM
Schedule::job(new SyncLessonSchedules)->dailyAt('03:00');
