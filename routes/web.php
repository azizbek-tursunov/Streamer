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
    Route::resource('cameras', \App\Http\Controllers\CameraController::class);
    Route::post('cameras/{camera}/stream', [\App\Http\Controllers\CameraController::class, 'startStream'])->name('cameras.stream.start');
    Route::post('cameras/{camera}/stop-stream', [\App\Http\Controllers\CameraController::class, 'stopStream'])->name('cameras.stream.stop');
    Route::post('cameras/{camera}/toggle-active', [\App\Http\Controllers\CameraController::class, 'toggleActive'])->name('cameras.toggle-active');
    Route::put('cameras/{camera}/youtube', [\App\Http\Controllers\CameraController::class, 'updateYoutube'])->name('cameras.youtube.update');
    
    Route::resource('branches', \App\Http\Controllers\BranchController::class);
    Route::resource('floors', \App\Http\Controllers\FloorController::class);
    Route::resource('faculties', \App\Http\Controllers\FacultyController::class);
});

require __DIR__.'/settings.php';
