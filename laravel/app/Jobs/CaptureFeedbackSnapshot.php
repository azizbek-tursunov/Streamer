<?php

namespace App\Jobs;

use App\Models\Camera;
use App\Models\LessonFeedback;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CaptureFeedbackSnapshot implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(
        public LessonFeedback $feedback,
        public Camera $camera,
    ) {
        $this->queue = 'snapshots';
    }

    public function handle(): void
    {
        $feedbackDir = storage_path('app/public/feedback_snapshots');
        if (! is_dir($feedbackDir)) {
            mkdir($feedbackDir, 0755, true);
        }

        $filename = "feedback_{$this->feedback->id}_cam{$this->camera->id}.jpg";
        $outputPath = "{$feedbackDir}/{$filename}";

        // Try to capture a fresh snapshot from the camera via ISAPI
        $captured = $this->captureFromCamera($outputPath);

        // Fallback: copy latest existing snapshot
        if (! $captured) {
            $captured = $this->copyLatestSnapshot($outputPath);
        }

        if ($captured) {
            $this->feedback->update(['snapshot_path' => "feedback_snapshots/{$filename}"]);
        }
    }

    private function captureFromCamera(string $outputPath): bool
    {
        $url = "http://{$this->camera->ip_address}/ISAPI/Streaming/channels/101/picture";

        try {
            $http = Http::timeout(10)->sink($outputPath);

            if (! empty($this->camera->username)) {
                $http->withDigestAuth($this->camera->username, $this->camera->password ?? '');
            }

            $response = $http->get($url);

            if ($response->successful() && file_exists($outputPath) && filesize($outputPath) > 1000) {
                return true;
            }

            // Clean up failed file
            if (file_exists($outputPath)) {
                unlink($outputPath);
            }
        } catch (\Exception $e) {
            Log::warning("FeedbackSnapshot: Failed to capture from camera {$this->camera->id}", [
                'error' => $e->getMessage(),
            ]);
            if (file_exists($outputPath)) {
                unlink($outputPath);
            }
        }

        return false;
    }

    private function copyLatestSnapshot(string $outputPath): bool
    {
        $files = glob(storage_path("app/public/snapshots/camera_{$this->camera->id}_*.jpg"));

        if (empty($files)) {
            return false;
        }

        usort($files, fn ($a, $b) => filemtime($b) - filemtime($a));

        return copy($files[0], $outputPath);
    }
}
