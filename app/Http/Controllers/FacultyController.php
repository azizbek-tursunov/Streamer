<?php

namespace App\Http\Controllers;

use App\Models\Faculty;
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
        $query = Faculty::query()->withCount('auditoriums');

        if ($request->search) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        return Inertia::render('Faculties/Index', [
            'faculties' => $query->orderBy('name')->get(),
            'filters' => $request->only(['search']),
            'lastSyncedAt' => Faculty::whereNotNull('hemis_id')->max('updated_at'),
        ]);
    }

    public function sync(): RedirectResponse
    {
        $count = $this->hemisApi->syncFaculties();

        return back()->with('success', "$count ta fakultet muvaffaqiyatli sinxronlashtirildi.");
    }
}
