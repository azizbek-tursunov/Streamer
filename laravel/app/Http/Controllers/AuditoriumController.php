<?php

namespace App\Http\Controllers;

use App\Jobs\CaptureSnapshot;
use App\Models\Camera;
use App\Models\Hemis\Auditorium;
use App\Models\PeopleCount;
use App\Services\HemisIntegrations\HemisApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;

class AuditoriumController extends Controller
{
    public function __construct(
        private readonly HemisApiService $hemisApi,
    ) {}

    public function index(Request $request): Response
    {
        $query = Auditorium::query()->with(['camera', 'faculties']);

        if ($request->search) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('faculty_id')) {
            $query->whereHas('faculties', fn ($q) => $q->where('faculties.id', $request->faculty_id));
        }

        // Deans only see auditoriums assigned to their faculty
        $user = auth()->user();
        if ($user->hasRole('deans') && $user->faculty_id) {
            $query->whereHas('faculties', fn ($q) => $q->where('faculties.id', $user->faculty_id));
        }

        // Regular users only see auditoriums that have a camera assigned.
        // IT-technicians, admins, and super-admins see all (so they can assign cameras).
        // Use can() not hasPermissionTo() — the latter skips Gate::before, breaking super-admin.
        if (!$user->can('manage-auditorium-cameras')) {
            $query->whereNotNull('camera_id');
        }

        $auditoriums = $query->orderBy('building_sort_order')->orderBy('building_name')->orderBy('sort_order')->orderBy('name')->get();

        // Fetch current lessons
        $timezone = config('app.timezone');
        $now = now($timezone);
        $currentLessons = \App\Models\LessonSchedule::where('lesson_date', $now->toDateString())
            ->where('start_timestamp', '<=', $now)
            ->where('end_timestamp', '>=', $now)
            ->get()
            ->keyBy('auditorium_code');

        // Fetch snapshots — single directory scan instead of per-camera glob
        $cameraIds = $auditoriums->whereNotNull('camera_id')->pluck('camera_id')->unique();
        $snapshots = [];
        $snapshotDir = storage_path('app/public/snapshots');
        $allFiles = glob($snapshotDir.'/camera_*.jpg');
        $latestPerCamera = [];
        foreach ($allFiles as $file) {
            if (preg_match('/camera_(\d+)_/', basename($file), $m)) {
                $camId = (int) $m[1];
                if (!isset($latestPerCamera[$camId]) || filemtime($file) > filemtime($latestPerCamera[$camId])) {
                    $latestPerCamera[$camId] = $file;
                }
            }
        }
        foreach ($cameraIds as $cameraId) {
            if (isset($latestPerCamera[$cameraId])) {
                $snapshots[$cameraId] = asset('storage/snapshots/'.basename($latestPerCamera[$cameraId]));
            }
        }

        // Fetch latest people counts per camera
        $peopleCounts = \App\Models\PeopleCount::whereIn('camera_id', $cameraIds)
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('people_counts')
                    ->groupBy('camera_id');
            })
            ->pluck('people_count', 'camera_id');

        $auditoriums->transform(function ($auditorium) use ($currentLessons, $snapshots, $peopleCounts) {
            $auditorium->current_lesson = $currentLessons->get($auditorium->code);
            if ($auditorium->camera_id && isset($snapshots[$auditorium->camera_id])) {
                $auditorium->camera_snapshot = $snapshots[$auditorium->camera_id];
            }
            $auditorium->people_count = $auditorium->camera_id
                ? ($peopleCounts[$auditorium->camera_id] ?? null)
                : null;
            return $auditorium;
        });

        return Inertia::render('Auditoriums/Index', [
            'auditoriums' => $auditoriums,
            'filters' => $request->only(['search', 'faculty_id']),
            'lastSyncedAt' => Auditorium::max('updated_at'),
            // Pass available cameras for linking
            'cameras' => Camera::select('id', 'name', 'ip_address')->get(),
            // Pass faculties for linking and filtering
            'faculties' => \App\Models\Faculty::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Auditorium $auditorium): RedirectResponse
    {
        $validated = $request->validate([
            'camera_id' => 'nullable|exists:cameras,id',
        ]);

        $auditorium->update([
            'camera_id' => $validated['camera_id'],
        ]);

        return back()->with('success', 'Kamera muvaffaqiyatli biriktirildi.');
    }

    public function show(Auditorium $auditorium): Response
    {
        $auditorium->load('camera', 'faculties');

        $timezone = config('app.timezone');
        $today = now($timezone)->toDateString();

        // Read from local DB (synced daily at 3 AM)
        $lessons = \App\Models\LessonSchedule::where('auditorium_code', $auditorium->code)
            ->where('lesson_date', $today)
            ->orderBy('start_timestamp')
            ->get()
            ->map(fn ($l) => [
                'id' => $l->id,
                'subject' => ['name' => $l->subject_name],
                'employee' => ['name' => $l->employee_name],
                'group' => ['name' => $l->group_name],
                'trainingType' => ['name' => $l->training_type_name ?? ''],
                'lessonPair' => [
                    'start_time' => $l->start_time instanceof \Carbon\Carbon ? $l->start_time->format('H:i') : $l->start_time,
                    'end_time' => $l->end_time instanceof \Carbon\Carbon ? $l->end_time->format('H:i') : $l->end_time,
                    'name' => $l->lesson_pair_name ?? '',
                ],
                'lesson_date' => $l->lesson_date->timestamp,
                'start_timestamp' => $l->start_timestamp->timestamp,
                'end_timestamp' => $l->end_timestamp->timestamp,
            ])
            ->toArray();

        $latestPeopleCount = $auditorium->camera_id
            ? PeopleCount::where('camera_id', $auditorium->camera_id)
                ->latest('counted_at')
                ->first()
            : null;

        return Inertia::render('Auditoriums/Show', [
            'auditorium' => $auditorium,
            'schedule' => $lessons,
            'now' => now()->timestamp,
            'people_count' => $latestPeopleCount?->people_count,
            'people_counted_at' => $latestPeopleCount?->counted_at?->toIso8601String(),
        ]);
    }

    public function triggerRealtimePeopleCount(Auditorium $auditorium): JsonResponse
    {
        $auditorium->load('camera');

        if (! $auditorium->camera) {
            return response()->json([
                'message' => 'Auditoriumga kamera biriktirilmagan.',
            ], 422);
        }

        CaptureSnapshot::dispatchSync($auditorium->camera);

        $latestSnapshot = $this->getLatestSnapshotPath($auditorium->camera->id);

        if (! $latestSnapshot) {
            return response()->json([
                'message' => 'Yangi snapshot olinmadi.',
            ], 422);
        }

        $queuedAt = now()->toIso8601String();
        $response = Http::timeout(120)
            ->acceptJson()
            ->post(rtrim(config('services.yolo_realtime.url'), '/').'/count', [
                'camera_id' => $auditorium->camera->id,
                'image_path' => $latestSnapshot,
            ]);

        if (! $response->successful()) {
            return response()->json([
                'message' => 'Realtime AI count xizmati javob bermadi.',
                'error' => $response->json('detail') ?? $response->body(),
            ], 503);
        }

        $peopleCount = PeopleCount::create([
            'camera_id' => $auditorium->camera->id,
            'people_count' => (int) $response->json('people_count'),
            'snapshot_path' => basename($latestSnapshot),
            'counted_at' => $queuedAt,
        ]);

        return response()->json([
            'queued' => false,
            'queued_at' => $queuedAt,
            'snapshot_path' => basename($latestSnapshot),
            'people_count' => $peopleCount->people_count,
            'counted_at' => $peopleCount->counted_at?->toIso8601String(),
            'model' => $response->json('model'),
        ]);
    }

    public function sync(): RedirectResponse
    {
        $count = $this->hemisApi->syncAuditoriums();

        return back()->with('success', "$count ta auditoriya muvaffaqiyatli sinxronlashtirildi.");
    }

    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:auditoriums,id',
            'items.*.sort_order' => 'required|integer',
        ]);

        foreach ($validated['items'] as $item) {
            Auditorium::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return back()->with('success', 'Auditoriyalar tartibi saqlandi.');
    }

    public function reorderBuildings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'buildings' => 'required|array',
            'buildings.*.building_id' => 'required|integer',
            'buildings.*.sort_order' => 'required|integer',
        ]);

        foreach ($validated['buildings'] as $building) {
            Auditorium::where('building_id', $building['building_id'])
                ->update(['building_sort_order' => $building['sort_order']]);
        }

        return back()->with('success', 'Binolar tartibi saqlandi.');
    }

    public function bulkAssignFaculty(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'auditorium_ids' => 'required|array',
            'auditorium_ids.*' => 'required|exists:auditoriums,id',
            'faculty_id' => 'nullable|exists:faculties,id',
        ]);

        $auditoriums = Auditorium::whereIn('id', $validated['auditorium_ids'])->get();

        if ($validated['faculty_id']) {
            // Attach faculty without removing existing ones
            foreach ($auditoriums as $auditorium) {
                $auditorium->faculties()->syncWithoutDetaching([$validated['faculty_id']]);
            }
            $message = count($validated['auditorium_ids']) . ' ta auditoriya fakultetga biriktirildi.';
        } else {
            // Detach all faculties
            foreach ($auditoriums as $auditorium) {
                $auditorium->faculties()->detach();
            }
            $message = count($validated['auditorium_ids']) . ' ta auditoriyadan barcha fakultetlar ochirildi.';
        }

        return back()->with('success', $message);
    }
    public function activeLessons()
    {
        $timezone = config('app.timezone');
        $now = now($timezone);
        $currentLessons = \App\Models\LessonSchedule::where('lesson_date', $now->toDateString())
            ->where('start_timestamp', '<=', $now)
            ->where('end_timestamp', '>=', $now)
            ->get()
            ->keyBy('auditorium_code');

        return response()->json($currentLessons);
    }

    public function peopleCounts()
    {
        $counts = \App\Models\PeopleCount::whereIn('id', function ($query) {
            $query->selectRaw('MAX(id)')
                ->from('people_counts')
                ->groupBy('camera_id');
        })->pluck('people_count', 'camera_id');

        return response()->json($counts);
    }

    private function getLatestSnapshotPath(int $cameraId): ?string
    {
        $snapshotDir = storage_path('app/public/snapshots');
        $files = glob("{$snapshotDir}/camera_{$cameraId}_*.jpg");

        if (empty($files)) {
            return null;
        }

        usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));

        return $files[0];
    }
}
