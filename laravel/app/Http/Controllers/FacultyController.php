<?php

namespace App\Http\Controllers;

use App\Models\Faculty;
use App\Models\Hemis\Auditorium;
use App\Models\User;
use App\Services\HemisIntegrations\HemisApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FacultyController extends Controller
{
    public function __construct(
        private readonly HemisApiService $hemisApi,
    ) {}

    public function index(Request $request): Response
    {
        $query = Faculty::query()
            ->withCount('auditoriums')
            ->with('dean:id,name,email');

        if ($request->search) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        return Inertia::render('Faculties/Index', [
            'faculties' => $query->orderBy('name')->get(),
            'filters' => $request->only(['search']),
            'lastSyncedAt' => Faculty::whereNotNull('hemis_id')->max('updated_at'),
        ]);
    }

    public function show(Faculty $faculty): Response
    {
        $faculty->load('auditoriums');
        $faculty->loadCount('auditoriums');

        // Get the dean (user with faculty_id = this faculty)
        $dean = User::where('faculty_id', $faculty->id)->first();

        // Get users with "deans" role for the dropdown
        $deanCandidates = User::role('deans')->select('id', 'name', 'email')->orderBy('name')->get();

        return Inertia::render('Faculties/Show', [
            'faculty' => $faculty,
            'dean' => $dean ? ['id' => $dean->id, 'name' => $dean->name, 'email' => $dean->email] : null,
            'deanCandidates' => $deanCandidates,
        ]);
    }

    public function assignDean(Request $request, Faculty $faculty): RedirectResponse
    {
        $validated = $request->validate([
            'dean_id' => 'nullable|exists:users,id',
        ]);

        // Remove current dean assignment
        User::where('faculty_id', $faculty->id)->update(['faculty_id' => null]);

        // Assign new dean
        if ($validated['dean_id']) {
            // Also clear this user's previous faculty assignment (if any)
            User::where('id', $validated['dean_id'])->update(['faculty_id' => $faculty->id]);
        }

        return back()->with('success', $validated['dean_id']
            ? 'Dekan muvaffaqiyatli tayinlandi.'
            : 'Dekan olib tashlandi.');
    }

    public function removeAuditorium(Faculty $faculty, Auditorium $auditorium): RedirectResponse
    {
        $faculty->auditoriums()->detach($auditorium->id);

        return back()->with('success', "{$auditorium->name} fakultetdan olib tashlandi.");
    }

    public function sync(): RedirectResponse
    {
        $count = $this->hemisApi->syncFaculties();

        return back()->with('success', "$count ta fakultet muvaffaqiyatli sinxronlashtirildi.");
    }
}
