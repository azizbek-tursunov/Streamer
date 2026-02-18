<?php

namespace App\Http\Controllers;

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
        $query = Auditorium::query();

        if ($request->search) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        return Inertia::render('Auditoriums/Index', [
            'auditoriums' => $query->orderBy('building_name')->orderBy('name')->get(),
            'filters' => $request->only(['search']),
            'lastSyncedAt' => Auditorium::max('updated_at'),
        ]);
    }

    public function sync(): RedirectResponse
    {
        $count = $this->hemisApi->syncAuditoriums();

        return back()->with('success', "$count ta auditoriya muvaffaqiyatli sinxronlashtirildi.");
    }
}
