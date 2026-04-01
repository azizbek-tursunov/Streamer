<?php

use App\Jobs\CaptureSnapshot;
use App\Jobs\DispatchPeopleCounting;
use App\Jobs\PruneSnapshots;
use App\Jobs\SyncLessonSchedules;
use App\Models\Camera;
use App\Services\MediaMtxService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

// Re-initialize camera streams if MediaMTX restarted and lost its path configs
Schedule::call(function () {
    try {
        $host = config('services.mediamtx.host', 'localhost');
        $port = config('services.mediamtx.port', '9997');
        $user = config('services.mediamtx.user', 'admin');
        $pass = config('services.mediamtx.password');

        $response = Http::timeout(5)->get("http://{$user}:{$pass}@{$host}:{$port}/v3/config/paths/list");

        if ($response->successful() && ($response->json('itemCount') ?? 0) === 0) {
            $activeCameraCount = Camera::where('is_active', true)->count();
            if ($activeCameraCount > 0) {
                Log::warning('MediaMTX has 0 configured paths but has active cameras — re-initializing streams.');
                Artisan::call('cameras:initialize-streams');
            }
        }
    } catch (\Exception $e) {
        Log::warning('MediaMTX path watchdog check failed', ['error' => $e->getMessage()]);
    }
})->everyFiveMinutes();
