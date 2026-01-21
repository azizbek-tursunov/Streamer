<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total' => Camera::count(),
            'active' => Camera::where('is_active', true)->count(),
            'streaming' => Camera::where('is_streaming_to_youtube', true)->count(),
        ];

        // Fetch active cameras for the live mosaic
        // Limiting to e.g. 6 to avoid overwhelming the browser if there are hundreds
        $activeCameras = Camera::where('is_active', true)->take(6)->get();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'activeCameras' => $activeCameras,
        ]);
    }
}
