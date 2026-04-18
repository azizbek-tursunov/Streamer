<?php

namespace App\Jobs;

use App\Services\AnomalyDetectionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DetectAuditoriumAnomalies implements ShouldQueue
{
    use Queueable;

    public function handle(AnomalyDetectionService $anomalyDetectionService): void
    {
        $anomalyDetectionService->syncCurrentAnomalies();
    }
}
