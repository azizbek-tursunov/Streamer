<?php

namespace App\Http\Controllers;

use App\Models\Faculty;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FacultyController extends Controller
{
    public function index(Request $request)
    {
        $query = Faculty::query();

        if ($request->search) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        return Inertia::render('Faculties/Index', [
            'faculties' => $query->latest()->paginate(10)->withQueryString(),
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:faculties,name',
        ]);

        Faculty::create($request->only('name'));

        return back()->with('success', 'Fakultet muvaffaqiyatli yaratildi.');
    }

    public function update(Request $request, Faculty $faculty)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:faculties,name,'.$faculty->id,
        ]);

        $faculty->update($request->only('name'));

        return back()->with('success', 'Fakultet muvaffaqiyatli yangilandi.');
    }

    public function destroy(Faculty $faculty)
    {
        if ($faculty->cameras()->exists()) {
            return back()->with('error', 'Ushbu fakultetda kameralar mavjud. Oldin ularni o\'chiring yoki boshqa fakultetga o\'tkazing.');
        }

        $faculty->delete();

        return back()->with('success', 'Fakultet o\'chirildi.');
    }
}
