<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/streams', [\App\Http\Controllers\PublicStreamController::class, 'index'])->name('public.streams');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('cameras/grid', [\App\Http\Controllers\CameraController::class, 'grid'])->name('cameras.grid');
    Route::get('cameras/snapshots', [\App\Http\Controllers\CameraController::class, 'snapshots'])->name('cameras.snapshots');
    Route::resource('cameras', \App\Http\Controllers\CameraController::class)->except(['create', 'edit']);
    Route::post('cameras/{camera}/stream', [\App\Http\Controllers\CameraController::class, 'startStream'])->name('cameras.stream.start');
    Route::post('cameras/{camera}/stop-stream', [\App\Http\Controllers\CameraController::class, 'stopStream'])->name('cameras.stream.stop');
    Route::post('cameras/{camera}/toggle-active', [\App\Http\Controllers\CameraController::class, 'toggleActive'])->name('cameras.toggle-active');
    Route::put('cameras/{camera}/youtube', [\App\Http\Controllers\CameraController::class, 'updateYoutube'])->name('cameras.youtube.update');

    Route::get('faculties', [\App\Http\Controllers\FacultyController::class, 'index'])->name('faculties.index');
    Route::post('faculties/sync', [\App\Http\Controllers\FacultyController::class, 'sync'])->name('faculties.sync');

    Route::get('auditoriums', [\App\Http\Controllers\AuditoriumController::class, 'index'])->name('auditoriums.index');
    Route::post('auditoriums/sync', [\App\Http\Controllers\AuditoriumController::class, 'sync'])->name('auditoriums.sync');
    Route::put('auditoriums/{auditorium}', [\App\Http\Controllers\AuditoriumController::class, 'update'])->name('auditoriums.update');
    Route::get('auditoriums/{auditorium}', [\App\Http\Controllers\AuditoriumController::class, 'show'])->name('auditoriums.show');

    Route::middleware(['role:super-admin|admin'])->group(function () {
        Route::resource('users', \App\Http\Controllers\UserController::class);
        Route::resource('roles', \App\Http\Controllers\RoleController::class);
        Route::resource('permissions', \App\Http\Controllers\PermissionController::class);
    });
});

require __DIR__.'/settings.php';
