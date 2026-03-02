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
    // --- Cameras ---
    // View routes: require view-cameras OR view-camera-grid (for snapshots used by both)
    Route::middleware(['permission:view-cameras|view-camera-grid'])->group(function () {
        Route::get('cameras/snapshots', [\App\Http\Controllers\CameraController::class, 'snapshots'])->name('cameras.snapshots');
    });
    Route::get('cameras/grid', [\App\Http\Controllers\CameraController::class, 'grid'])->middleware('permission:view-camera-grid')->name('cameras.grid');
    Route::get('cameras', [\App\Http\Controllers\CameraController::class, 'index'])->middleware('permission:view-cameras')->name('cameras.index');
    Route::get('cameras/{camera}', [\App\Http\Controllers\CameraController::class, 'show'])->middleware('permission:view-cameras')->name('cameras.show');

    // Camera CRUD (create, update, delete, import, stream, toggle)
    Route::middleware(['permission:manage-cameras'])->group(function () {
        Route::post('cameras/import', [\App\Http\Controllers\CameraController::class, 'import'])->name('cameras.import');
        Route::post('cameras', [\App\Http\Controllers\CameraController::class, 'store'])->name('cameras.store');
        Route::put('cameras/{camera}', [\App\Http\Controllers\CameraController::class, 'update'])->name('cameras.update');
        Route::delete('cameras/{camera}', [\App\Http\Controllers\CameraController::class, 'destroy'])->name('cameras.destroy');
        Route::post('cameras/{camera}/stream', [\App\Http\Controllers\CameraController::class, 'startStream'])->name('cameras.stream.start');
        Route::post('cameras/{camera}/stop-stream', [\App\Http\Controllers\CameraController::class, 'stopStream'])->name('cameras.stream.stop');
        Route::post('cameras/{camera}/toggle-active', [\App\Http\Controllers\CameraController::class, 'toggleActive'])->name('cameras.toggle-active');
        Route::post('cameras/{camera}/toggle-public', [\App\Http\Controllers\CameraController::class, 'togglePublic'])->name('cameras.toggle-public');
        Route::put('cameras/{camera}/youtube', [\App\Http\Controllers\CameraController::class, 'updateYoutube'])->name('cameras.youtube.update');
    });

    // --- Faculties ---
    Route::middleware(['permission:manage-users'])->group(function () {
        Route::get('faculties', [\App\Http\Controllers\FacultyController::class, 'index'])->name('faculties.index');
        Route::post('faculties/sync', [\App\Http\Controllers\FacultyController::class, 'sync'])->name('faculties.sync');
    });

    // --- Auditoriums ---
    Route::middleware(['permission:view-auditoriums'])->group(function () {
        Route::get('auditoriums', [\App\Http\Controllers\AuditoriumController::class, 'index'])->name('auditoriums.index');
        Route::get('auditoriums/active-lessons', [\App\Http\Controllers\AuditoriumController::class, 'activeLessons'])->name('auditoriums.active-lessons');
        Route::get('auditoriums/people-counts', [\App\Http\Controllers\AuditoriumController::class, 'peopleCounts'])->name('auditoriums.people-counts');
        Route::get('auditoriums/{auditorium}', [\App\Http\Controllers\AuditoriumController::class, 'show'])->name('auditoriums.show');
    });
    Route::post('auditoriums/sync', [\App\Http\Controllers\AuditoriumController::class, 'sync'])->middleware('permission:sync-auditoriums')->name('auditoriums.sync');
    Route::put('auditoriums/reorder', [\App\Http\Controllers\AuditoriumController::class, 'reorder'])->middleware('permission:view-auditoriums')->name('auditoriums.reorder');
    Route::put('auditoriums/reorder-buildings', [\App\Http\Controllers\AuditoriumController::class, 'reorderBuildings'])->middleware('permission:view-auditoriums')->name('auditoriums.reorder-buildings');
    Route::put('auditoriums/bulk-assign-faculty', [\App\Http\Controllers\AuditoriumController::class, 'bulkAssignFaculty'])->middleware('permission:manage-auditorium-faculty')->name('auditoriums.bulk-assign-faculty');
    Route::put('auditoriums/{auditorium}', [\App\Http\Controllers\AuditoriumController::class, 'update'])->middleware('permission:manage-auditorium-cameras')->name('auditoriums.update');

    // --- Feedbacks ---
    Route::get('feedbacks', [\App\Http\Controllers\LessonFeedbackController::class, 'index'])->middleware('permission:view-feedbacks')->name('feedbacks.index');
    Route::post('feedbacks', [\App\Http\Controllers\LessonFeedbackController::class, 'store'])->middleware('permission:add-feedbacks')->name('feedbacks.store');

    // --- Users, Roles, Permissions (admin-level) ---
    Route::middleware(['role:super-admin|admin'])->group(function () {
        Route::post('users/sync', [\App\Http\Controllers\UserController::class, 'syncFromHemis'])->name('users.sync');
        Route::resource('users', \App\Http\Controllers\UserController::class);
        Route::resource('roles', \App\Http\Controllers\RoleController::class);
        Route::resource('permissions', \App\Http\Controllers\PermissionController::class);
    });

    // --- Hemis Settings (super-admin only) ---
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
