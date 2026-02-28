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
    Route::get('cameras/snapshots', [\App\Http\Controllers\CameraController::class, 'snapshots'])->name('cameras.snapshots');
    Route::get('cameras/grid', [\App\Http\Controllers\CameraController::class, 'grid'])->name('cameras.grid');
    Route::post('cameras/import', [\App\Http\Controllers\CameraController::class, 'import'])->name('cameras.import');
    Route::resource('cameras', \App\Http\Controllers\CameraController::class)->except(['create', 'edit']);
    Route::post('cameras/{camera}/stream', [\App\Http\Controllers\CameraController::class, 'startStream'])->name('cameras.stream.start');
    Route::post('cameras/{camera}/stop-stream', [\App\Http\Controllers\CameraController::class, 'stopStream'])->name('cameras.stream.stop');
    Route::post('cameras/{camera}/toggle-active', [\App\Http\Controllers\CameraController::class, 'toggleActive'])->name('cameras.toggle-active');
    Route::post('cameras/{camera}/toggle-public', [\App\Http\Controllers\CameraController::class, 'togglePublic'])->name('cameras.toggle-public');
    Route::put('cameras/{camera}/youtube', [\App\Http\Controllers\CameraController::class, 'updateYoutube'])->name('cameras.youtube.update');

    Route::get('faculties', [\App\Http\Controllers\FacultyController::class, 'index'])->name('faculties.index');
    Route::post('faculties/sync', [\App\Http\Controllers\FacultyController::class, 'sync'])->name('faculties.sync');

    Route::get('auditoriums', [\App\Http\Controllers\AuditoriumController::class, 'index'])->name('auditoriums.index');
    Route::get('auditoriums/active-lessons', [\App\Http\Controllers\AuditoriumController::class, 'activeLessons'])->name('auditoriums.active-lessons');
    Route::get('auditoriums/people-counts', [\App\Http\Controllers\AuditoriumController::class, 'peopleCounts'])->name('auditoriums.people-counts');
    Route::post('auditoriums/sync', [\App\Http\Controllers\AuditoriumController::class, 'sync'])->name('auditoriums.sync');
    Route::put('auditoriums/reorder', [\App\Http\Controllers\AuditoriumController::class, 'reorder'])->name('auditoriums.reorder');
    Route::put('auditoriums/reorder-buildings', [\App\Http\Controllers\AuditoriumController::class, 'reorderBuildings'])->name('auditoriums.reorder-buildings');
    Route::put('auditoriums/bulk-assign-faculty', [\App\Http\Controllers\AuditoriumController::class, 'bulkAssignFaculty'])->name('auditoriums.bulk-assign-faculty');
    Route::put('auditoriums/{auditorium}', [\App\Http\Controllers\AuditoriumController::class, 'update'])->name('auditoriums.update');
    Route::get('auditoriums/{auditorium}', [\App\Http\Controllers\AuditoriumController::class, 'show'])->name('auditoriums.show');

    Route::get('feedbacks', [\App\Http\Controllers\LessonFeedbackController::class, 'index'])->name('feedbacks.index');
    Route::post('feedbacks', [\App\Http\Controllers\LessonFeedbackController::class, 'store'])->name('feedbacks.store');

    Route::middleware(['role:super-admin|admin'])->group(function () {
        Route::post('users/sync', [\App\Http\Controllers\UserController::class, 'syncFromHemis'])->name('users.sync');
        Route::resource('users', \App\Http\Controllers\UserController::class);
        Route::resource('roles', \App\Http\Controllers\RoleController::class);
        Route::resource('permissions', \App\Http\Controllers\PermissionController::class);
    });
    Route::middleware(['role:super-admin'])->group(function () {
        Route::get('hemis', [\App\Http\Controllers\SettingController::class, 'hemis'])->name('hemis.settings');
        Route::put('hemis', [\App\Http\Controllers\SettingController::class, 'updateHemis'])->name('hemis.settings.update');
        Route::post('hemis/test', [\App\Http\Controllers\SettingController::class, 'testHemis'])->name('hemis.settings.test');

        Route::get('hemis-auth', [\App\Http\Controllers\SettingController::class, 'hemisAuth'])->name('hemis-auth.settings');
        Route::put('hemis-auth', [\App\Http\Controllers\SettingController::class, 'updateHemisAuth'])->name('hemis-auth.settings.update');
    });
});

// HEMIS OAuth Routes
Route::get('hemis/redirect/employee', [\App\Http\Controllers\Auth\HemisOAuthController::class, 'redirect'])->name('hemis.redirect.employee');
Route::get('hemis/callback/employee', [\App\Http\Controllers\Auth\HemisOAuthController::class, 'callback'])->name('hemis.callback.employee');

require __DIR__.'/settings.php';
