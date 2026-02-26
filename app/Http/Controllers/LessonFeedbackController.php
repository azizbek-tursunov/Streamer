<?php

namespace App\Http\Controllers;

use App\Models\LessonFeedback;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LessonFeedbackController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = LessonFeedback::with(['user', 'auditorium'])
            ->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('lesson_name', 'ilike', "%{$search}%")
                  ->orWhere('employee_name', 'ilike', "%{$search}%")
                  ->orWhere('group_name', 'ilike', "%{$search}%")
                  ->orWhere('message', 'ilike', "%{$search}%");
            });
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($date = $request->input('date')) {
            $query->whereDate('created_at', $date);
        }

        if ($building = $request->input('building')) {
            $query->whereHas('auditorium', function ($q) use ($building) {
                $q->where('building_name', $building);
            });
        }

        $feedbacks = $query->paginate(15)->withQueryString();

        // Get unique building names from all auditoriums for the filter dropdown
        $buildings = \App\Models\Hemis\Auditorium::whereNotNull('building_name')
            ->distinct()
            ->orderBy('building_name')
            ->pluck('building_name');

        return Inertia::render('LessonFeedbacks/Index', [
            'feedbacks' => $feedbacks,
            'buildings' => $buildings,
            'filters' => $request->only(['search', 'type', 'date', 'building']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'auditorium_id' => 'nullable|exists:auditoriums,id',
            'lesson_name' => 'nullable|string|max:255',
            'employee_name' => 'nullable|string|max:255',
            'group_name' => 'nullable|string|max:255',
            'start_time' => 'nullable|string|max:255',
            'end_time' => 'nullable|string|max:255',
            'type' => 'required|in:good,bad',
            'message' => 'nullable|string',
        ]);

        $validated['user_id'] = auth()->id();

        LessonFeedback::create($validated);

        return redirect()->back()->with('success', 'Fikr-mulohaza muvaffaqiyatli saqlandi!');
    }
}
