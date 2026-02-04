<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Camera;
use App\Services\MediaMtxService;
use Illuminate\Support\Facades\Log;

class InitializeCameraStreams extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cameras:initialize-streams';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Re-initialize media streams for all active cameras';

    /**
     * Execute the console command.
     */
    public function handle(MediaMtxService $mediaMtxService)
    {
        $this->info('Initializing camera streams...');

        $cameras = Camera::where('is_active', true)->get();

        if ($cameras->isEmpty()) {
            $this->info('No active cameras found.');
            return;
        }

        foreach ($cameras as $camera) {
            try {
                $this->info("Initializing stream for camera: {$camera->name} (ID: {$camera->id})");
                $mediaMtxService->addPath($camera);
            } catch (\Exception $e) {
                $this->error("Failed to initialize camera {$camera->id}: " . $e->getMessage());
                Log::error("Failed to initialize camera stream on startup", [
                    'camera_id' => $camera->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->info('Camera initialization complete.');
    }
}
