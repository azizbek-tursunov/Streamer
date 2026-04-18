<?php

namespace App\Http\Controllers;

use App\Models\Anomaly;
use App\Models\Faculty;
use App\Services\AuditoriumAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnomalyController extends Controller
{
    public function __construct(
        private readonly AuditoriumAccessService $auditoriumAccessService,
    ) {}

    public function index(Request $request): Response
    {
        $query = $this->auditoriumAccessService
            ->visibleAnomaliesQuery($request->user())
            ->latest('detected_at');

        if ($search = $request->string('search')->trim()->value()) {
            $term = '%'.mb_strtolower($search).'%';
            $query->where(function ($builder) use ($term) {
                $builder->whereRaw('LOWER(type) LIKE ?', [$term])
                    ->orWhereHas('auditorium', fn ($auditoriumQuery) => $auditoriumQuery->whereRaw('LOWER(name) LIKE ?', [$term]))
                    ->orWhereHas('lessonSchedule', fn ($lessonQuery) => $lessonQuery
                        ->whereRaw('LOWER(subject_name) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(employee_name) LIKE ?', [$term]));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($building = $request->input('building')) {
            $query->whereHas('auditorium', fn ($auditoriumQuery) => $auditoriumQuery->where('building_name', $building));
        }

        if ($facultyId = $request->input('faculty_id')) {
            $query->whereHas('auditorium.faculties', fn ($facultyQuery) => $facultyQuery->where('faculties.id', $facultyId));
        }

        $anomalies = $query->paginate(15)->withQueryString();

        $summaryQuery = $this->auditoriumAccessService->visibleAnomaliesQuery($request->user());

        $summary = [
            'open' => (clone $summaryQuery)->where('status', Anomaly::STATUS_OPEN)->count(),
            'resolved' => (clone $summaryQuery)->where('status', Anomaly::STATUS_RESOLVED)->count(),
            'dismissed' => (clone $summaryQuery)->where('status', Anomaly::STATUS_DISMISSED)->count(),
        ];

        $buildings = $this->auditoriumAccessService
            ->visibleAuditoriumsQuery($request->user())
            ->whereNotNull('building_name')
            ->distinct()
            ->orderBy('building_name')
            ->pluck('building_name');

        $faculties = Faculty::query()
            ->when(
                $request->user()?->hasRole('deans'),
                fn ($facultyQuery) => $facultyQuery->whereKey([$request->user()?->faculty_id]),
            )
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Anomalies/Index', [
            'anomalies' => $anomalies->through(fn (Anomaly $anomaly) => $this->transformAnomaly($anomaly)),
            'summary' => $summary,
            'buildings' => $buildings,
            'faculties' => $faculties,
            'filters' => $request->only(['search', 'status', 'type', 'building', 'faculty_id']),
            'typeOptions' => $this->typeOptions(),
        ]);
    }

    public function show(Request $request, Anomaly $anomaly): Response
    {
        $visible = $this->auditoriumAccessService
            ->visibleAnomaliesQuery($request->user())
            ->with(['events.user'])
            ->whereKey($anomaly->id)
            ->firstOrFail();

        return Inertia::render('Anomalies/Show', [
            'anomaly' => $this->transformAnomaly($visible, true),
            'related' => [
                'other_open_count' => $this->auditoriumAccessService
                    ->visibleAnomaliesQuery($request->user())
                    ->where('auditorium_id', $visible->auditorium_id)
                    ->where('status', Anomaly::STATUS_OPEN)
                    ->where('id', '!=', $visible->id)
                    ->count(),
            ],
            'history' => $visible->events->map(fn ($event) => [
                'id' => $event->id,
                'from_status' => $event->from_status,
                'to_status' => $event->to_status,
                'note' => $event->note,
                'created_at' => $event->created_at?->toIso8601String(),
                'user' => $event->user ? [
                    'id' => $event->user->id,
                    'name' => $event->user->name,
                ] : null,
            ])->values(),
            'typeOptions' => $this->typeOptions(),
        ]);
    }

    public function updateStatus(Request $request, Anomaly $anomaly): RedirectResponse
    {
        $visible = $this->auditoriumAccessService
            ->visibleAnomaliesQuery($request->user())
            ->whereKey($anomaly->id)
            ->firstOrFail();

        $validated = $request->validate([
            'status' => 'required|in:open,resolved,dismissed',
            'note' => 'nullable|string|max:1000',
        ]);

        $status = $validated['status'];
        $fromStatus = $visible->status;

        if ($fromStatus === $status && empty($validated['note'])) {
            return back();
        }

        $visible->update([
            'status' => $status,
            'resolved_at' => $status === Anomaly::STATUS_OPEN ? null : now(),
            'last_seen_at' => now(),
        ]);

        $visible->events()->create([
            'user_id' => $request->user()?->id,
            'from_status' => $fromStatus,
            'to_status' => $status,
            'note' => $validated['note'] ?? null,
        ]);

        $message = match ($status) {
            Anomaly::STATUS_RESOLVED => 'Anomaliya hal qilingan deb belgilandi.',
            Anomaly::STATUS_DISMISSED => 'Anomaliya bekor qilindi.',
            default => 'Anomaliya qayta ochildi.',
        };

        return back()->with('success', $message);
    }

    private function transformAnomaly(Anomaly $anomaly, bool $includePayload = false): array
    {
        $faculties = $anomaly->auditorium?->faculties
            ? $anomaly->auditorium->faculties->map(fn ($faculty) => [
                'id' => $faculty->id,
                'name' => $faculty->name,
            ])->values()->all()
            : [];

        return [
            'id' => $anomaly->id,
            'type' => $anomaly->type,
            'status' => $anomaly->status,
            'detected_at' => $anomaly->detected_at?->toIso8601String(),
            'last_seen_at' => $anomaly->last_seen_at?->toIso8601String(),
            'resolved_at' => $anomaly->resolved_at?->toIso8601String(),
            'payload' => $includePayload ? $anomaly->payload : null,
            'auditorium' => $anomaly->auditorium ? [
                'id' => $anomaly->auditorium->id,
                'name' => $anomaly->auditorium->name,
                'building' => $anomaly->auditorium->building,
                'faculties' => $faculties,
            ] : null,
            'camera' => $anomaly->camera ? [
                'id' => $anomaly->camera->id,
                'name' => $anomaly->camera->name,
            ] : null,
            'lesson' => $anomaly->lessonSchedule ? [
                'id' => $anomaly->lessonSchedule->id,
                'subject_name' => $anomaly->lessonSchedule->subject_name,
                'employee_name' => $anomaly->lessonSchedule->employee_name,
                'group_name' => $anomaly->lessonSchedule->group_name,
                'start_time' => $anomaly->lessonSchedule->start_time,
                'end_time' => $anomaly->lessonSchedule->end_time,
            ] : null,
        ];
    }

    private function typeOptions(): array
    {
        return [
            ['value' => Anomaly::TYPE_LESSON_NO_PEOPLE, 'label' => "Dars bor, odam yo'q"],
            ['value' => Anomaly::TYPE_PEOPLE_NO_LESSON, 'label' => "Odam bor, dars yo'q"],
            ['value' => Anomaly::TYPE_CAMERA_OFFLINE_DURING_LESSON, 'label' => 'Dars paytida kamera uzilgan'],
        ];
    }
}
