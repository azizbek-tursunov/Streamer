<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;
use App\Models\Camera;
use App\Jobs\CaptureSnapshot;
use App\Jobs\PruneSnapshots;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule camera snapshots every 10 minutes
Schedule::call(function () {
    Camera::where('is_active', true)->chunk(10, function ($cameras) {
        foreach ($cameras as $camera) {
            CaptureSnapshot::dispatch($camera);
        }
    });
})->everyTenMinutes();

// Prune old snapshots every hour
Schedule::job(new PruneSnapshots)->hourly();
