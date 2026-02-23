<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'cameras' => [
                'total' => Camera::count(),
                'active' => Camera::where('is_active', true)->count(),
                'public' => Camera::where('is_public', true)->count(),
                'streaming' => Camera::where('is_streaming_to_youtube', true)->count(),
            ],
            'auditoriums' => [
                'total' => \App\Models\Hemis\Auditorium::count(),
                'active' => \App\Models\Hemis\Auditorium::where('active', true)->count(),
                'with_camera' => \App\Models\Hemis\Auditorium::whereNotNull('camera_id')->count(),
            ],
            'faculties' => \App\Models\Faculty::count(),
        ];

        return Inertia::render('Dashboard', [
            'stats' => $stats,
        ]);
    }
}
