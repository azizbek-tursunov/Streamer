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

        if ($request->filled('search')) {
            $term = '%'.mb_strtolower($request->input('search')).'%';
            $query->where(function ($q) use ($term) {
                $q->whereRaw('LOWER(subject_name) LIKE ?', [$term])
                  ->orWhereRaw('LOWER(employee_name) LIKE ?', [$term])
                  ->orWhereRaw('LOWER(group_name) LIKE ?', [$term])
                  ->orWhereHas('auditorium', fn ($q2) =>
                      $q2->whereRaw('LOWER(name) LIKE ?', [$term])
                  );
            });
        }

        $paraRanges = [
            1 => ['00:00:00', '09:14:59'],
            2 => ['09:15:00', '10:44:59'],
            3 => ['10:45:00', '12:29:59'],
            4 => ['12:30:00', '14:59:59'],
            5 => ['15:00:00', '16:29:59'],
            6 => ['16:30:00', '23:59:59'],
        ];
        if ($para = (int) $request->input('para')) {
            if (isset($paraRanges[$para])) {
                $query->whereBetween('start_time', $paraRanges[$para]);
            }
        }

        $schedules = $query->paginate(50)->withQueryString();

        return Inertia::render('LessonSchedules/Index', [
            'schedules' => $schedules,
            'filters' => $request->only(['search', 'para']),
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
