<?php

namespace App\Http\Controllers;

use App\Exports\FeedbackExport;
use App\Jobs\CaptureFeedbackSnapshot;
use App\Models\LessonFeedback;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

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
            $term = '%'.mb_strtolower($search).'%';
            $query->where(function ($q) use ($term) {
                $q->whereRaw('LOWER(lesson_name) LIKE ?', [$term])
                  ->orWhereRaw('LOWER(employee_name) LIKE ?', [$term])
                  ->orWhereRaw('LOWER(group_name) LIKE ?', [$term])
                  ->orWhereRaw('LOWER(message) LIKE ?', [$term]);
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

        $chartData = (clone $query)
            ->reorder()
            ->selectRaw('DATE(created_at) as date')
            ->selectRaw("SUM(CASE WHEN type = 'good' THEN 1 ELSE 0 END) as good_count")
            ->selectRaw("SUM(CASE WHEN type = 'bad' THEN 1 ELSE 0 END) as bad_count")
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date,
                'good' => (int) $row->good_count,
                'bad' => (int) $row->bad_count,
            ]);

        $feedbacks = $query->paginate(15)->withQueryString();

        $feedbacks->getCollection()->transform(function ($feedback) {
            $feedback->snapshot_url = $feedback->snapshot_path
                ? asset('storage/'.$feedback->snapshot_path)
                : null;

            return $feedback;
        });

        // Get unique building names from active auditoriums for the filter dropdown
        $buildings = \App\Models\Hemis\Auditorium::where('active', true)
            ->whereNotNull('building_name')
            ->distinct()
            ->orderBy('building_name')
            ->pluck('building_name');

        return Inertia::render('LessonFeedbacks/Index', [
            'feedbacks' => $feedbacks,
            'chartData' => $chartData,
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

        $feedback = LessonFeedback::create($validated);

        // Dispatch job to capture a fresh snapshot from the camera
        if (! empty($validated['auditorium_id'])) {
            $auditorium = \App\Models\Hemis\Auditorium::find($validated['auditorium_id']);
            if ($auditorium?->camera_id) {
                $camera = \App\Models\Camera::find($auditorium->camera_id);
                if ($camera) {
                    CaptureFeedbackSnapshot::dispatch($feedback, $camera);
                }
            }
        }

        return redirect()->back()->with('success', 'Fikr-mulohaza muvaffaqiyatli saqlandi!');
    }

    public function export(Request $request)
    {
        $query = LessonFeedback::query()->latest();

        if ($search = $request->input('search')) {
            $term = '%'.mb_strtolower($search).'%';
            $query->where(function ($q) use ($term) {
                $q->whereRaw('LOWER(lesson_name) LIKE ?', [$term])
                  ->orWhereRaw('LOWER(employee_name) LIKE ?', [$term])
                  ->orWhereRaw('LOWER(group_name) LIKE ?', [$term])
                  ->orWhereRaw('LOWER(message) LIKE ?', [$term]);
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

        $filename = 'dars_tahlili_'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(new FeedbackExport($query), $filename);
    }

    public function destroy(Request $request, LessonFeedback $feedback)
    {
        abort_unless(
            $request->user()?->hasAnyRole(['admin', 'super-admin']),
            403
        );

        $feedback->delete();

        return redirect()->back()->with('success', 'Fikr-mulohaza o\'chirildi.');
    }
}
