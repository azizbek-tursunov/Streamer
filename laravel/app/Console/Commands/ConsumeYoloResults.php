<?php

namespace App\Console\Commands;

use App\Models\PeopleCount;
use App\Services\AnomalyDetectionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ConsumeYoloResults extends Command
{
    protected $signature = 'yolo:consume';

    protected $description = 'Continuously consume YOLO people counting results from Redis';

    public function handle(AnomalyDetectionService $anomalyDetectionService): void
    {
        $this->info('YOLO consumer started. Waiting for results...');

        while (true) {
            // Blocking pop with 5 second timeout (returns null if no data)
            $result = Redis::brpop('yolo:results', 5);

            if ($result === null) {
                continue;
            }

            // Redis::brpop returns [key, value]
            $data = json_decode($result[1], true);

            if (! $data || ! isset($data['camera_id'], $data['people_count'])) {
                Log::warning('YOLO consumer: Invalid result received', ['data' => $result[1]]);

                continue;
            }

            PeopleCount::create([
                'camera_id' => $data['camera_id'],
                'people_count' => $data['people_count'],
                'snapshot_path' => $data['snapshot_path'] ?? '',
                // Freshness checks should use the moment Laravel received the result.
                // The worker emits UTC ISO timestamps, but this column is used
                // operationally for local recency windows in anomaly detection.
                'counted_at' => now(config('app.timezone')),
            ]);

            $anomalyDetectionService->syncForCamera((int) $data['camera_id']);

            $this->line("Camera {$data['camera_id']}: {$data['people_count']} people detected");
        }
    }
}
