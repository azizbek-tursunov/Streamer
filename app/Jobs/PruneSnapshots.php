<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class PruneSnapshots implements ShouldQueue
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
     * Execute the job.
     */
    public function handle(): void
    {
        $snapshotDir = storage_path('app/public/snapshots');
        
        if (!file_exists($snapshotDir)) {
            return;
        }

        $files = glob("{$snapshotDir}/*.jpg");
        $now = time();
        $retentionSeconds = 2 * 3600; // 2 hours

        $deletedCount = 0;

        foreach ($files as $file) {
            if (is_file($file)) {
                if ($now - filemtime($file) >= $retentionSeconds) {
                    unlink($file);
                    $deletedCount++;
                }
            }
        }

        Log::info("Snapshot Prune: Deleted {$deletedCount} old snapshots.");
    }
}
