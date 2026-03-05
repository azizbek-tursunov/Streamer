<?php

namespace App\Http\Controllers;

use App\Models\LessonFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
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

        $feedbacks->getCollection()->transform(function ($feedback) {
            $feedback->snapshot_url = $feedback->snapshot_path
                ? asset('storage/'.$feedback->snapshot_path)
                : null;

            return $feedback;
        });

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

        // Capture current camera snapshot if auditorium has a camera
        if (! empty($validated['auditorium_id'])) {
            $auditorium = \App\Models\Hemis\Auditorium::with('camera')->find($validated['auditorium_id']);
            if ($auditorium?->camera_id) {
                $snapshotDir = storage_path('app/public/snapshots');
                $files = glob($snapshotDir."/camera_{$auditorium->camera_id}_*.jpg");
                if (! empty($files)) {
                    usort($files, fn ($a, $b) => filemtime($b) - filemtime($a));
                    $feedbackDir = storage_path('app/public/feedback_snapshots');
                    if (! is_dir($feedbackDir)) {
                        mkdir($feedbackDir, 0755, true);
                    }
                    $filename = 'feedback_'.now()->format('Y-m-d_H-i-s').'_cam'.$auditorium->camera_id.'.jpg';
                    copy($files[0], $feedbackDir.'/'.$filename);
                    $validated['snapshot_path'] = 'feedback_snapshots/'.$filename;
                }
            }
        }

        LessonFeedback::create($validated);

        return redirect()->back()->with('success', 'Fikr-mulohaza muvaffaqiyatli saqlandi!');
    }

    public function export(Request $request)
    {
        $query = LessonFeedback::with(['user', 'auditorium'])->latest();

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

        $feedbacks = $query->get();

        $rows = [];
        $rows[] = ['#', 'Sana', 'Bino', 'Auditoriya', 'Fan', "O'qituvchi", 'Guruh', 'Vaqt', 'Holati', 'Mulohaza', 'Kiritdi'];

        foreach ($feedbacks as $i => $f) {
            $rows[] = [
                $i + 1,
                $f->created_at->format('d.m.Y H:i'),
                $f->auditorium?->building_name ?? '',
                $f->auditorium?->name ?? '',
                $f->lesson_name ?? '',
                $f->employee_name ?? '',
                $f->group_name ?? '',
                ($f->start_time ? substr($f->start_time, 0, 5) : '?').' - '.($f->end_time ? substr($f->end_time, 0, 5) : '?'),
                $f->type === 'good' ? 'Ijobiy' : 'Salbiy',
                $f->message ?? '',
                $f->user?->name ?? '',
            ];
        }

        $callback = function () use ($rows) {
            $file = fopen('php://output', 'w');
            // UTF-8 BOM for Excel
            fwrite($file, "\xEF\xBB\xBF");
            foreach ($rows as $row) {
                fputcsv($file, $row, ';');
            }
            fclose($file);
        };

        $filename = 'dars_tahlili_'.now()->format('Y-m-d').'.csv';

        return Response::stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
