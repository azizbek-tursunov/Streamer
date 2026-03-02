<?php

namespace App\Http\Controllers;

use App\Models\Hemis\Auditorium;
use App\Models\LessonSchedule;
use App\Services\HemisIntegrations\HemisApiService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LessonScheduleController extends Controller
{
    /**
     * Display a listing of today's lesson schedules.
     */
    public function index(Request $request)
    {
        $timezone = config('app.timezone', 'Asia/Tashkent');
        $today = now($timezone)->toDateString();

        $query = LessonSchedule::query()
            ->with('auditorium')
            ->where('lesson_date', $today)
            ->orderBy('start_timestamp');

        // Optional search filtering by subject, employee, or group
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('subject_name', 'like', "%{$search}%")
                  ->orWhere('employee_name', 'like', "%{$search}%")
                  ->orWhere('group_name', 'like', "%{$search}%")
                  ->orWhereHas('auditorium', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $schedules = $query->paginate(20)->withQueryString();

        return Inertia::render('LessonSchedules/Index', [
            'schedules' => $schedules,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Get the list of active auditoriums that need their schedules synced.
     */
    public function syncInit()
    {
        $auditoriums = Auditorium::where('active', true)->pluck('code');

        return response()->json([
            'auditoriums' => $auditoriums,
        ]);
    }

    /**
     * Sync the lesson schedules for a single given auditorium.
     */
    public function syncAuditorium(Request $request, string $code, HemisApiService $hemisApi)
    {
        try {
            $syncedCount = $hemisApi->syncAuditoriumSchedule($code);
            
            return response()->json([
                'success' => true,
                'auditorium_code' => $code,
                'synced_count' => $syncedCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'auditorium_code' => $code,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
