<?php

namespace App\Http\Controllers;

use App\Exports\FeedbackExport;
use App\Jobs\CaptureFeedbackSnapshot;
use App\Models\Hemis\Auditorium;
use App\Models\LessonFeedback;
use App\Models\User;
use App\Notifications\DeanFeedbackSubmitted;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class LessonFeedbackController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        abort_unless($this->canViewFeedbacks($request), 403);

        $query = $this->feedbackQuery($request)->latest();

        if ($search = $request->input('search')) {
            $term = '%'.mb_strtolower($search).'%';
            $query->where(function ($feedbackQuery) use ($term) {
                $feedbackQuery->whereRaw('LOWER(lesson_name) LIKE ?', [$term])
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
            $query->whereHas('auditorium', function ($auditoriumQuery) use ($building) {
                $auditoriumQuery->where('building_name', $building);
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

        $buildings = $this->buildingQuery($request->user())
            ->where('active', true)
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
            'faculty_id' => 'nullable|exists:faculties,id',
            'lesson_name' => 'nullable|string|max:255',
            'employee_name' => 'nullable|string|max:255',
            'group_name' => 'nullable|string|max:255',
            'start_time' => 'nullable|string|max:255',
            'end_time' => 'nullable|string|max:255',
            'type' => 'required|in:good,bad',
            'message' => 'nullable|string',
        ]);

        $validated['user_id'] = auth()->id();
        $validated['faculty_id'] = $this->resolveFacultyId($validated);

        $feedback = LessonFeedback::create($validated);
        $feedback->load(['faculty.dean', 'auditorium']);

        if ($dean = $feedback->faculty?->dean) {
            $dean->notify(new DeanFeedbackSubmitted($feedback));
        }

        if (! empty($validated['auditorium_id'])) {
            $auditorium = Auditorium::find($validated['auditorium_id']);
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
        abort_unless($this->canViewFeedbacks($request), 403);

        $query = $this->feedbackQuery($request)->latest();

        if ($search = $request->input('search')) {
            $term = '%'.mb_strtolower($search).'%';
            $query->where(function ($feedbackQuery) use ($term) {
                $feedbackQuery->whereRaw('LOWER(lesson_name) LIKE ?', [$term])
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
            $query->whereHas('auditorium', function ($auditoriumQuery) use ($building) {
                $auditoriumQuery->where('building_name', $building);
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

    protected function canViewFeedbacks(Request $request): bool
    {
        return (bool) ($request->user()?->can('view-feedbacks') || $request->user()?->hasRole('deans'));
    }

    protected function feedbackQuery(Request $request): Builder
    {
        $query = LessonFeedback::with(['user', 'auditorium', 'faculty']);
        $user = $request->user();

        if ($user?->hasRole('deans')) {
            if ($user->faculty_id) {
                $query->where('faculty_id', $user->faculty_id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        return $query;
    }

    protected function buildingQuery(User $user): Builder
    {
        $query = Auditorium::query();

        if ($user->hasRole('deans')) {
            if ($user->faculty_id) {
                $query->whereHas('faculties', fn ($facultyQuery) => $facultyQuery->where('faculties.id', $user->faculty_id));
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        return $query;
    }

    protected function resolveFacultyId(array $validated): int
    {
        if (empty($validated['auditorium_id'])) {
            if (! empty($validated['faculty_id'])) {
                return (int) $validated['faculty_id'];
            }

            throw ValidationException::withMessages([
                'faculty_id' => ['Fakultet tanlanmadi.'],
            ]);
        }

        $auditorium = Auditorium::with('faculties:id')->findOrFail($validated['auditorium_id']);
        $facultyIds = $auditorium->faculties->pluck('id');

        if ($facultyIds->isEmpty()) {
            throw ValidationException::withMessages([
                'auditorium_id' => ['Tanlangan auditoriya hech qaysi fakultetga biriktirilmagan.'],
            ]);
        }

        if (! empty($validated['faculty_id'])) {
            $selectedFacultyId = (int) $validated['faculty_id'];

            if (! $facultyIds->contains($selectedFacultyId)) {
                throw ValidationException::withMessages([
                    'faculty_id' => ['Tanlangan fakultet ushbu auditoriyaga biriktirilmagan.'],
                ]);
            }

            return $selectedFacultyId;
        }

        if ($facultyIds->count() === 1) {
            return (int) $facultyIds->first();
        }

        throw ValidationException::withMessages([
            'faculty_id' => ['Bir nechta fakultet mavjud. Fakultetni tanlang.'],
        ]);
    }
}
