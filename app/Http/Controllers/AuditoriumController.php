<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use App\Models\Hemis\Auditorium;
use App\Services\HemisIntegrations\HemisApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditoriumController extends Controller
{
    public function __construct(
        private readonly HemisApiService $hemisApi,
    ) {}

    public function index(Request $request): Response
    {
        $query = Auditorium::query()->with('camera');

        if ($request->search) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        return Inertia::render('Auditoriums/Index', [
            'auditoriums' => $query->orderBy('building_name')->orderBy('name')->get(),
            'filters' => $request->only(['search']),
            'lastSyncedAt' => Auditorium::max('updated_at'),
            // Pass available cameras for linking
            'cameras' => Camera::select('id', 'name', 'ip_address')->get(),
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
        $auditorium->load('camera');

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

        return Inertia::render('Auditoriums/Show', [
            'auditorium' => $auditorium,
            'schedule' => $lessons,
            'now' => now()->timestamp,
        ]);
    }

    public function sync(): RedirectResponse
    {
        $count = $this->hemisApi->syncAuditoriums();

        return back()->with('success', "$count ta auditoriya muvaffaqiyatli sinxronlashtirildi.");
    }
}
