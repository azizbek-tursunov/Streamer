<?php

use App\Models\Anomaly;
use App\Models\Camera;
use App\Models\Hemis\Auditorium;
use App\Models\LessonSchedule;
use App\Models\PeopleCount;
use App\Services\AnomalyDetectionService;

test('lesson with zero people creates lesson no people anomaly', function () {
    $camera = Camera::factory()->create(['is_active' => true]);

    $auditorium = Auditorium::create([
        'code' => 501,
        'name' => 'A-501',
        'auditorium_type_code' => '11',
        'auditorium_type_name' => "Ma'ruza",
        'building_id' => 1,
        'building_name' => 'Main Building',
        'volume' => 60,
        'active' => true,
        'camera_id' => $camera->id,
    ]);

    LessonSchedule::create([
        'hemis_id' => 9001,
        'auditorium_code' => $auditorium->code,
        'lesson_date' => now(config('app.timezone'))->toDateString(),
        'subject_name' => 'Matematika',
        'employee_name' => 'Teacher',
        'group_name' => '101',
        'training_type_name' => "Ma'ruza",
        'lesson_pair_name' => '1-juftlik',
        'start_time' => now(config('app.timezone'))->subMinutes(10)->format('H:i'),
        'end_time' => now(config('app.timezone'))->addMinutes(40)->format('H:i'),
        'start_timestamp' => now(config('app.timezone'))->subMinutes(10),
        'end_timestamp' => now(config('app.timezone'))->addMinutes(40),
    ]);

    PeopleCount::create([
        'camera_id' => $camera->id,
        'people_count' => 0,
        'snapshot_path' => 'snapshots/test.jpg',
        'counted_at' => now(config('app.timezone')),
    ]);

    app(AnomalyDetectionService::class)->syncCurrentAnomalies();

    expect(Anomaly::query()
        ->where('auditorium_id', $auditorium->id)
        ->where('type', Anomaly::TYPE_LESSON_NO_PEOPLE)
        ->where('status', Anomaly::STATUS_OPEN)
        ->exists())->toBeTrue();
});

test('lesson no people anomaly resolves when people appear', function () {
    $camera = Camera::factory()->create(['is_active' => true]);

    $auditorium = Auditorium::create([
        'code' => 502,
        'name' => 'A-502',
        'auditorium_type_code' => '11',
        'auditorium_type_name' => "Ma'ruza",
        'building_id' => 1,
        'building_name' => 'Main Building',
        'volume' => 50,
        'active' => true,
        'camera_id' => $camera->id,
    ]);

    LessonSchedule::create([
        'hemis_id' => 9002,
        'auditorium_code' => $auditorium->code,
        'lesson_date' => now(config('app.timezone'))->toDateString(),
        'subject_name' => 'Fizika',
        'employee_name' => 'Teacher',
        'group_name' => '102',
        'training_type_name' => "Ma'ruza",
        'lesson_pair_name' => '2-juftlik',
        'start_time' => now(config('app.timezone'))->subMinutes(5)->format('H:i'),
        'end_time' => now(config('app.timezone'))->addMinutes(45)->format('H:i'),
        'start_timestamp' => now(config('app.timezone'))->subMinutes(5),
        'end_timestamp' => now(config('app.timezone'))->addMinutes(45),
    ]);

    PeopleCount::create([
        'camera_id' => $camera->id,
        'people_count' => 0,
        'snapshot_path' => 'snapshots/test-zero.jpg',
        'counted_at' => now(config('app.timezone'))->subMinute(),
    ]);

    $service = app(AnomalyDetectionService::class);
    $service->syncCurrentAnomalies();

    PeopleCount::create([
        'camera_id' => $camera->id,
        'people_count' => 7,
        'snapshot_path' => 'snapshots/test-seven.jpg',
        'counted_at' => now(config('app.timezone')),
    ]);

    $service->syncCurrentAnomalies();

    expect(Anomaly::query()
        ->where('auditorium_id', $auditorium->id)
        ->where('type', Anomaly::TYPE_LESSON_NO_PEOPLE)
        ->where('status', Anomaly::STATUS_RESOLVED)
        ->exists())->toBeTrue();
});

test('people without lesson creates people no lesson anomaly', function () {
    $camera = Camera::factory()->create(['is_active' => true]);

    $auditorium = Auditorium::create([
        'code' => 503,
        'name' => 'A-503',
        'auditorium_type_code' => '11',
        'auditorium_type_name' => "Ma'ruza",
        'building_id' => 1,
        'building_name' => 'Main Building',
        'volume' => 45,
        'active' => true,
        'camera_id' => $camera->id,
    ]);

    PeopleCount::create([
        'camera_id' => $camera->id,
        'people_count' => 9,
        'snapshot_path' => 'snapshots/test-nine.jpg',
        'counted_at' => now(config('app.timezone')),
    ]);

    app(AnomalyDetectionService::class)->syncCurrentAnomalies();

    expect(Anomaly::query()
        ->where('auditorium_id', $auditorium->id)
        ->where('type', Anomaly::TYPE_PEOPLE_NO_LESSON)
        ->where('status', Anomaly::STATUS_OPEN)
        ->exists())->toBeTrue();
});

test('stale snapshot anomalies are resolved and no longer tracked', function () {
    $camera = Camera::factory()->create(['is_active' => true]);

    $auditorium = Auditorium::create([
        'code' => 504,
        'name' => 'A-504',
        'auditorium_type_code' => '11',
        'auditorium_type_name' => "Ma'ruza",
        'building_id' => 1,
        'building_name' => 'Main Building',
        'volume' => 45,
        'active' => true,
        'camera_id' => $camera->id,
    ]);

    Anomaly::create([
        'type' => Anomaly::TYPE_STALE_SNAPSHOT,
        'status' => Anomaly::STATUS_OPEN,
        'auditorium_id' => $auditorium->id,
        'camera_id' => $camera->id,
        'detected_at' => now(config('app.timezone'))->subMinutes(30),
        'last_seen_at' => now(config('app.timezone'))->subMinutes(20),
    ]);

    app(AnomalyDetectionService::class)->syncCurrentAnomalies();

    expect(Anomaly::query()
        ->where('auditorium_id', $auditorium->id)
        ->where('type', Anomaly::TYPE_STALE_SNAPSHOT)
        ->where('status', Anomaly::STATUS_RESOLVED)
        ->exists())->toBeTrue();
});
