<?php

use App\Models\Anomaly;
use App\Models\Camera;
use App\Models\Hemis\Auditorium;
use App\Models\User;
use App\Models\Faculty;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->has('stats')
        ->where('deanDashboard', null)
    );
});

test('dean sees scoped anomaly dashboard widgets', function () {
    $permission = Permission::firstOrCreate(['name' => 'view-auditoriums', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'deans', 'guard_name' => 'web']);
    $role->givePermissionTo($permission);

    $faculty = Faculty::create([
        'name' => 'Biologiya',
        'active' => true,
    ]);

    $user = User::factory()->create([
        'faculty_id' => $faculty->id,
    ]);
    $user->assignRole($role);

    $camera = Camera::factory()->create();

    $auditorium = Auditorium::create([
        'code' => 701,
        'name' => 'Bio 701',
        'building_id' => 1,
        'building_name' => 'Main Building',
        'volume' => 35,
        'active' => true,
        'camera_id' => $camera->id,
    ]);
    $auditorium->faculties()->attach($faculty->id);

    Anomaly::create([
        'type' => Anomaly::TYPE_LESSON_NO_PEOPLE,
        'status' => Anomaly::STATUS_OPEN,
        'auditorium_id' => $auditorium->id,
        'camera_id' => $camera->id,
        'detected_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->where('deanDashboard.scope.auditoriums', 1)
        ->where('deanDashboard.today.open_anomalies', 1)
        ->where('deanDashboard.today.lesson_no_people', 1)
        ->has('deanDashboard.recent_anomalies', 1)
    );
});
