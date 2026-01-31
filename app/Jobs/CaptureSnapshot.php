<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Camera;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;

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
        
        if (!file_exists($snapshotDir)) {
            mkdir($snapshotDir, 0755, true);
        }

        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "camera_{$this->camera->id}_{$timestamp}.jpg";
        $outputPath = "{$snapshotDir}/{$filename}";

        // FFmpeg command to capture single frame
        // -y: overwrite
        // -i: input url
        // -vframes 1: take 1 frame
        // -q:v 2: high quality jpeg
        $cmd = "ffmpeg -y -i \"{$this->camera->rtsp_url}\" -vframes 1 -q:v 2 \"{$outputPath}\"";

        Log::info("Snapshot: Starting for Camera {$this->camera->id}", ['cmd' => $cmd]);

        Process::timeout(30)->run($cmd);

        if (file_exists($outputPath)) {
            Log::info("Snapshot: Success for Camera {$this->camera->id}", ['file' => $filename]);
        } else {
            Log::error("Snapshot: Failed for Camera {$this->camera->id}");
        }
    }
}
