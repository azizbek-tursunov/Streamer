<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class SnapshotController extends Controller
{
    public function index()
    {
        $files = glob(storage_path('app/public/snapshots/*.jpg'));
        
        $snapshots = collect($files)->map(function ($file) {
            $filename = basename($file);
            // Expected format: camera_{id}_{timestamp}.jpg
            // Timestamp format: Y-m-d_H-i-s
            if (preg_match('/camera_(\d+)_(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})\.jpg/', $filename, $matches)) {
                return [
                    'filename' => $filename,
                    'url' => asset('storage/snapshots/' . $filename),
                    'camera_id' => (int)$matches[1],
                    'timestamp' => $matches[2],
                    'time_formatted' => \Carbon\Carbon::createFromFormat('Y-m-d_H-i-s', $matches[2])->format('H:i'),
                ];
            }
            return null;
        })->filter()->sortByDesc('timestamp')->values();

        // Group by Camera ID if needed, or just list them.
        // Let's send them all, frontend can filter/grid.
        
        return Inertia::render('Snapshots/Index', [
            'snapshots' => $snapshots,
        ]);
    }
}
