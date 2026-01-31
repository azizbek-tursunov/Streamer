<?php

namespace App\Http\Controllers;

use App\Models\Floor;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FloorController extends Controller
{
    public function index(Request $request)
    {
        $query = Floor::query();

        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        return Inertia::render('Floors/Index', [
            'floors' => $query->latest()->paginate(10)->withQueryString(),
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:floors,name',
        ]);

        Floor::create($request->only('name'));

        return back()->with('success', 'Qavat muvaffaqiyatli yaratildi.');
    }

    public function update(Request $request, Floor $floor)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:floors,name,' . $floor->id,
        ]);

        $floor->update($request->only('name'));

        return back()->with('success', 'Qavat muvaffaqiyatli yangilandi.');
    }

    public function destroy(Floor $floor)
    {
        if ($floor->cameras()->exists()) {
             return back()->with('error', 'Ushbu qavatda kameralar mavjud. Oldin ularni o\'chiring yoki boshqa qavatga o\'tkazing.');
        }

        $floor->delete();

        return back()->with('success', 'Qavat o\'chirildi.');
    }
}
