<?php

namespace App\Http\Controllers;

use App\Models\Anomaly;
use App\Models\Camera;
use App\Models\LessonSchedule;
use App\Services\AuditoriumAccessService;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(
        private readonly AuditoriumAccessService $auditoriumAccessService,
    ) {}

    public function index()
    {
        $user = auth()->user();

        $stats = [
            'cameras' => [
                'total' => Camera::count(),
                'active' => Camera::where('is_active', true)->count(),
                'public' => Camera::where('is_public', true)->count(),
                'streaming' => Camera::where('is_streaming_to_youtube', true)->count(),
            ],
            'auditoriums' => [
                'total' => \App\Models\Hemis\Auditorium::where('active', true)->count(),
                'with_camera' => \App\Models\Hemis\Auditorium::where('active', true)->whereNotNull('camera_id')->count(),
            ],
            'faculties' => \App\Models\Faculty::count(),
        ];

        $deanDashboard = null;

        if ($user && ($user->hasRole('deans') || $user->can('view-auditoriums'))) {
            $visibleAuditoriums = $this->auditoriumAccessService
                ->visibleAuditoriumsQuery($user)
                ->where('active', true)
                ->get(['id', 'code', 'name', 'building_name', 'camera_id']);

            $auditoriumIds = $visibleAuditoriums->pluck('id');
            $auditoriumCodes = $visibleAuditoriums->pluck('code');

            $now = now(config('app.timezone'));

            $activeLessonsCount = LessonSchedule::query()
                ->whereIn('auditorium_code', $auditoriumCodes)
                ->where('lesson_date', $now->toDateString())
                ->where('start_timestamp', '<=', $now)
                ->where('end_timestamp', '>=', $now)
                ->count();

            $openAnomaliesQuery = $this->auditoriumAccessService
                ->visibleAnomaliesQuery($user)
                ->where('status', Anomaly::STATUS_OPEN);

            $openAnomalies = (clone $openAnomaliesQuery)
                ->latest('detected_at')
                ->limit(5)
                ->get()
                ->map(fn (Anomaly $anomaly) => [
                    'id' => $anomaly->id,
                    'type' => $anomaly->type,
                    'detected_at' => $anomaly->detected_at?->toIso8601String(),
                    'auditorium' => [
                        'id' => $anomaly->auditorium?->id,
                        'name' => $anomaly->auditorium?->name,
                        'building_name' => $anomaly->auditorium?->building['name'] ?? null,
                    ],
                    'lesson' => $anomaly->lessonSchedule ? [
                        'subject_name' => $anomaly->lessonSchedule->subject_name,
                        'employee_name' => $anomaly->lessonSchedule->employee_name,
                    ] : null,
                ])
                ->values();

            $deanDashboard = [
                'faculty_name' => $user->faculty?->name,
                'scope' => [
                    'auditoriums' => $visibleAuditoriums->count(),
                    'with_camera' => $visibleAuditoriums->whereNotNull('camera_id')->count(),
                ],
                'today' => [
                    'active_lessons' => $activeLessonsCount,
                    'open_anomalies' => (clone $openAnomaliesQuery)->count(),
                    'lesson_no_people' => (clone $openAnomaliesQuery)->where('type', Anomaly::TYPE_LESSON_NO_PEOPLE)->count(),
                    'people_no_lesson' => (clone $openAnomaliesQuery)->where('type', Anomaly::TYPE_PEOPLE_NO_LESSON)->count(),
                ],
                'recent_anomalies' => $openAnomalies,
            ];
        }

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'deanDashboard' => $deanDashboard,
        ]);
    }
}
