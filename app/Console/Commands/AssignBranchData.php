<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Branch;
use App\Models\Floor;
use App\Models\Camera;

class AssignBranchData extends Command
{
    protected $signature = 'data:assign-to-yuksalish';

    protected $description = 'Assign all floors and cameras to Yuksalish branch';

    public function handle(): int
    {
        $branch = Branch::where('name', 'like', '%Yuksalish%')->first();
        
        if (!$branch) {
            $this->error('Yuksalish branch not found!');
            return Command::FAILURE;
        }
        
        $floorsUpdated = Floor::query()->update(['branch_id' => $branch->id]);
        $camerasUpdated = Camera::query()->update(['branch_id' => $branch->id]);
        
        $this->info("Updated {$floorsUpdated} floors and {$camerasUpdated} cameras to branch: {$branch->name}");
        
        return Command::SUCCESS;
    }
}
