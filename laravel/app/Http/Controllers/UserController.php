<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use App\Jobs\SyncHemisEmployees;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('roles')
            ->when($request->search, function ($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->role, function ($query, $role) {
                $query->whereHas('roles', function($q) use ($role) {
                    $q->where('name', $role);
                });
            })
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Security/Users/Index', [
            'users' => $users,
            'roles' => Role::select('id', 'name')->get(),
            'filters' => $request->only(['search', 'role']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Security/Users/Form', [
            'roles' => Role::all(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'roles' => ['array'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $roles = Role::whereIn('id', $request->input('roles', []))->get();
        $user->syncRoles($roles);

        return redirect()->route('users.index')->with('success', 'Foydalanuvchi yaratildi.');
    }

    public function edit(User $user)
    {
        $user->load('roles');

        return Inertia::render('Security/Users/Form', [
            'user' => $user,
            'roles' => Role::all(),
            'userRoles' => $user->roles->pluck('id')->toArray(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'roles' => ['array'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        $roles = Role::whereIn('id', $request->input('roles', []))->get();
        $user->syncRoles($roles);

        return redirect()->route('users.index')->with('success', 'Foydalanuvchi yangilandi.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'O\'zingizni o\'chira olmaysiz.');
        }

        $user->delete();

        return redirect()->back()->with('success', 'Foydalanuvchi o\'chirildi.');
    }

    public function syncFromHemis()
    {
        $baseUrl = rtrim(Setting::get('hemis.base_url', 'https://student.hemis.uz/rest/v1'), '/');
        $token = Setting::get('hemis.token');

        if (!$baseUrl || !$token) {
            return redirect()->back()->with('error', 'HEMIS API sozlamalari topilmadi. Avval API kaliti va manzilni kiriting.');
        }

        SyncHemisEmployees::dispatch();

        return redirect()->back()->with('success', "HEMIS xodimlari orqa fonda (Queue) sinxronizatsiya qilinmoqda. Birozdan so'ng sahifani yangilang.");
    }
}
