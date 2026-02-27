<?php

namespace App\Jobs;

use App\Models\Camera;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CaptureSnapshot implements ShouldQueue
{
    use Queueable;

    /**
     * The queue this job should be dispatched to.
     */
    public string $queue = 'snapshots';

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

        // Directly use Hikvision ISAPI endpoint (massively more efficient than FFmpeg RTSP)
        $url = "http://{$this->camera->ip_address}/ISAPI/Streaming/channels/101/picture";

        Log::info("Snapshot: Starting HTTP ISAPI for Camera {$this->camera->id}", ['url' => $url]);

        $http = \Illuminate\Support\Facades\Http::timeout(10)->sink($outputPath);

        if (! empty($this->camera->username)) {
            $http->withDigestAuth($this->camera->username, $this->camera->password ?? '');
        }

        try {
            $response = $http->get($url);

            if ($response->successful() && file_exists($outputPath)) {
                // Double check if file is not an empty layout (sometimes ISAPI returns XML error but 200 OK)
                if (filesize($outputPath) > 1000) {
                    Log::info("Snapshot: Success for Camera {$this->camera->id}", ['file' => $filename]);
                } else {
                    unlink($outputPath);
                    Log::error("Snapshot: HTTP returned a tiny file (likely auth error or XML) for Camera {$this->camera->id}");
                }
            } else {
                Log::error("Snapshot: Failed HTTP ISAPI for Camera {$this->camera->id}", [
                    'status' => $response->status(),
                    'error' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Snapshot: Exception for Camera {$this->camera->id}", ['error' => $e->getMessage()]);
        }
    }
}
