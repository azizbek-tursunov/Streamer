<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $query = Branch::query();

        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        return Inertia::render('Branches/Index', [
            'branches' => $query->latest()->paginate(10)->withQueryString(),
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:branches,name',
        ]);

        Branch::create($request->only('name'));

        return back()->with('success', 'Filial muvaffaqiyatli yaratildi.');
    }

    public function update(Request $request, Branch $branch)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:branches,name,' . $branch->id,
        ]);

        $branch->update($request->only('name'));

        return back()->with('success', 'Filial muvaffaqiyatli yangilandi.');
    }

    public function destroy(Branch $branch)
    {
        if ($branch->cameras()->exists()) {
             return back()->with('error', 'Ushbu filialda kameralar mavjud. Oldin ularni o\'chiring yoki boshqa filialga o\'tkazing.');
        }

        $branch->delete();

        return back()->with('success', 'Filial o\'chirildi.');
    }
}
