<?php

namespace App\Console\Commands;

use App\Models\PeopleCount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ConsumeYoloResults extends Command
{
    protected $signature = 'yolo:consume';

    protected $description = 'Continuously consume YOLO people counting results from Redis';

    public function handle(): void
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
                'counted_at' => $data['counted_at'] ?? now(),
            ]);

            $this->line("Camera {$data['camera_id']}: {$data['people_count']} people detected");
        }
    }
}
