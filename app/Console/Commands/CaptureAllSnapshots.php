<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Camera;
use App\Jobs\CaptureSnapshot;

class CaptureAllSnapshots extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'snapshots:capture-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Capture snapshots for all active cameras';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $cameras = Camera::where('is_active', true)->get();
        
        $this->info("Capturing snapshots for {$cameras->count()} active cameras...");
        
        foreach ($cameras as $camera) {
            $this->line("  → Camera {$camera->id}: {$camera->name}");
            CaptureSnapshot::dispatchSync($camera);
        }
        
        $this->info('Done!');
        
        return Command::SUCCESS;
    }
}
