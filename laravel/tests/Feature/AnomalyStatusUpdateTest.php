<?php

use App\Models\Anomaly;
use App\Models\Camera;
use App\Models\Hemis\Auditorium;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('authorized user can resolve anomaly and event is recorded', function () {
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

    $permission = Permission::firstOrCreate(['name' => 'view-auditoriums', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'auditorium-viewer', 'guard_name' => 'web']);
    $role->givePermissionTo($permission);

    $user = User::factory()->create();
    $user->assignRole($role);

    $camera = Camera::factory()->create();

    $auditorium = Auditorium::create([
        'code' => 601,
        'name' => 'A-601',
        'building_id' => 1,
        'building_name' => 'Main Building',
        'volume' => 40,
        'active' => true,
        'camera_id' => $camera->id,
    ]);

    $anomaly = Anomaly::create([
        'type' => Anomaly::TYPE_LESSON_NO_PEOPLE,
        'status' => Anomaly::STATUS_OPEN,
        'auditorium_id' => $auditorium->id,
        'camera_id' => $camera->id,
        'detected_at' => now(),
    ]);

    $response = $this
        ->actingAs($user)
        ->post("/anomalies/{$anomaly->id}/status", [
            'status' => 'resolved',
            'note' => 'Xona tekshirildi va muammo yopildi.',
        ]);

    $response->assertRedirect();

    $anomaly->refresh();

    expect($anomaly->status)->toBe(Anomaly::STATUS_RESOLVED)
        ->and($anomaly->events()->count())->toBe(1)
        ->and($anomaly->events()->first()->note)->toBe('Xona tekshirildi va muammo yopildi.');
});

test('authorized user can dismiss anomaly without note', function () {
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

    $permission = Permission::firstOrCreate(['name' => 'view-auditoriums', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'auditorium-viewer', 'guard_name' => 'web']);
    $role->givePermissionTo($permission);

    $user = User::factory()->create();
    $user->assignRole($role);

    $camera = Camera::factory()->create();

    $auditorium = Auditorium::create([
        'code' => 602,
        'name' => 'A-602',
        'building_id' => 1,
        'building_name' => 'Main Building',
        'volume' => 40,
        'active' => true,
        'camera_id' => $camera->id,
    ]);

    $anomaly = Anomaly::create([
        'type' => Anomaly::TYPE_LESSON_NO_PEOPLE,
        'status' => Anomaly::STATUS_OPEN,
        'auditorium_id' => $auditorium->id,
        'camera_id' => $camera->id,
        'detected_at' => now(),
    ]);

    $response = $this
        ->actingAs($user)
        ->post("/anomalies/{$anomaly->id}/status", [
            'status' => 'dismissed',
        ]);

    $response->assertRedirect();

    $anomaly->refresh();

    expect($anomaly->status)->toBe(Anomaly::STATUS_DISMISSED)
        ->and($anomaly->events()->count())->toBe(1)
        ->and($anomaly->events()->first()->note)->toBeNull();
});
