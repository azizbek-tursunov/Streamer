<?php

namespace App\Jobs;

use App\Models\PeopleCount;
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
        $retentionSeconds = 30 * 60; // 30 minutes
        $pruneBefore = now()->subSeconds($retentionSeconds);
        $deletedCount = 0;

        if (file_exists($snapshotDir)) {
            $files = glob("{$snapshotDir}/*.jpg");
            $now = time();

            foreach ($files as $file) {
                if (is_file($file) && $now - filemtime($file) >= $retentionSeconds) {
                    unlink($file);
                    $deletedCount++;
                }
            }
        }

        $deletedPeopleCounts = PeopleCount::query()
            ->where('counted_at', '<=', $pruneBefore)
            ->delete();

        Log::info("Snapshot Prune: Deleted {$deletedCount} old snapshots and {$deletedPeopleCounts} old people counts.");
    }
}
