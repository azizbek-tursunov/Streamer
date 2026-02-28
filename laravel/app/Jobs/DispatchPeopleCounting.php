<?php

namespace App\Jobs;

use App\Models\Camera;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class DispatchPeopleCounting implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Push YOLO people-counting jobs to Redis for all active cameras.
     */
    public function handle(): void
    {
        $cameras = Camera::where('is_active', true)->get();
        $dispatched = 0;

        foreach ($cameras as $camera) {
            $snapshotDir = storage_path('app/public/snapshots');
            $files = glob("{$snapshotDir}/camera_{$camera->id}_*.jpg");

            if (empty($files)) {
                continue;
            }

            // Sort by modification time descending to get the latest snapshot
            usort($files, function ($a, $b) {
                return filemtime($b) - filemtime($a);
            });

            $latestFile = $files[0];

            // Push job to Redis for Python YOLO worker
            Redis::lpush('yolo:jobs', json_encode([
                'camera_id' => $camera->id,
                'image_path' => $latestFile,
                'requested_at' => now()->toIso8601String(),
            ]));

            $dispatched++;
        }

        Log::info("PeopleCounting: Dispatched {$dispatched} YOLO jobs for {$cameras->count()} active cameras");
    }
}
