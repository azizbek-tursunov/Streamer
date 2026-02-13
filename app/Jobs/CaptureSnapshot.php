<?php

namespace App\Jobs;

use App\Models\Camera;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class CaptureSnapshot implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public Camera $camera)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $snapshotDir = storage_path('app/public/snapshots');

        if (! file_exists($snapshotDir)) {
            mkdir($snapshotDir, 0755, true);
        }

        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "camera_{$this->camera->id}_{$timestamp}.jpg";
        $outputPath = "{$snapshotDir}/{$filename}";

        // Use MediaMTX proxied stream (accessible from Docker network)
        // This is more reliable than direct camera RTSP which may not be reachable
        $host = config('services.mediamtx.host', 'mediamtx');
        $user = config('services.mediamtx.user', 'admin');
        $pass = config('services.mediamtx.password', '12345');
        $streamUrl = "rtsp://{$user}:{$pass}@{$host}:8554/cam_{$this->camera->id}";

        // FFmpeg command to capture single frame
        // -y: overwrite
        // -rtsp_transport tcp: use TCP for reliability
        // -i: input url
        // -vframes 1: take 1 frame
        // -q:v 2: high quality jpeg
        $cmd = "ffmpeg -y -rtsp_transport tcp -i \"{$streamUrl}\" -vframes 1 -q:v 2 \"{$outputPath}\"";

        Log::info("Snapshot: Starting for Camera {$this->camera->id}", ['cmd' => $cmd]);

        Process::timeout(60)->run($cmd);

        if (file_exists($outputPath)) {
            Log::info("Snapshot: Success for Camera {$this->camera->id}", ['file' => $filename]);
        } else {
            Log::error("Snapshot: Failed for Camera {$this->camera->id}");
        }
    }
}
