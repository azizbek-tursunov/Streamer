<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/streams', [\App\Http\Controllers\PublicStreamController::class, 'index'])->name('public.streams');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('cameras', \App\Http\Controllers\CameraController::class);
    Route::post('cameras/{camera}/stream', [\App\Http\Controllers\CameraController::class, 'startStream'])->name('cameras.stream.start');
    Route::post('cameras/{camera}/stop-stream', [\App\Http\Controllers\CameraController::class, 'stopStream'])->name('cameras.stream.stop');
    Route::post('cameras/{camera}/toggle-active', [\App\Http\Controllers\CameraController::class, 'toggleActive'])->name('cameras.toggle-active');
});

require __DIR__.'/settings.php';
