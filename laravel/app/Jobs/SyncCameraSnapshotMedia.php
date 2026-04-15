<?php

namespace App\Jobs;

use App\Models\Camera;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncCameraSnapshotMedia implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $cameraId,
        public string $snapshotPath,
        public ?int $capturedAt = null,
    ) {
        $this->queue = 'media';
    }

    public function handle(): void
    {
        if (! is_file($this->snapshotPath)) {
            Log::warning("Snapshot Media: Source snapshot missing for camera {$this->cameraId}", [
                'path' => $this->snapshotPath,
            ]);

            return;
        }

        $camera = Camera::find($this->cameraId);

        if (! $camera) {
            Log::warning("Snapshot Media: Camera {$this->cameraId} not found");

            return;
        }

        $camera
            ->addMedia($this->snapshotPath)
            ->usingName("camera-{$camera->id}-snapshot")
            ->usingFileName("camera-{$camera->id}-latest.jpg")
            ->withCustomProperties([
                'captured_at' => $this->capturedAt,
                'source_path' => $this->snapshotPath,
            ])
            ->toMediaCollection('latest_snapshot');
    }
}
