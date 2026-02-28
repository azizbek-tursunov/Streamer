<?php

use App\Models\Hemis\Auditorium;
use App\Models\User;
use App\Services\HemisIntegrations\HemisApiService;
use Inertia\Testing\AssertableInertia as Assert;

test('auditoriums page is displayed', function () {
    $user = User::factory()->create();

    Auditorium::create([
        'code' => 100,
        'name' => 'Test Auditorium',
        'auditorium_type_code' => '11',
        'auditorium_type_name' => "Ma'ruza",
        'building_id' => 1,
        'building_name' => 'Building A',
        'volume' => 50,
        'active' => true,
    ]);

    $response = $this
        ->actingAs($user)
        ->get('/auditoriums');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Auditoriums/Index')
        ->has('auditoriums', 1)
        ->where('auditoriums.0.name', 'Test Auditorium')
        ->where('auditoriums.0.code', 100)
    );
});

test('auditoriums page filters by search', function () {
    $user = User::factory()->create();

    Auditorium::create([
        'code' => 100,
        'name' => 'Alpha Room',
        'volume' => 30,
        'active' => true,
    ]);
    Auditorium::create([
        'code' => 200,
        'name' => 'Beta Room',
        'volume' => 50,
        'active' => true,
    ]);

    $response = $this
        ->actingAs($user)
        ->get('/auditoriums?search=Alpha');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Auditoriums/Index')
        ->has('auditoriums', 1)
        ->where('auditoriums.0.name', 'Alpha Room')
        ->where('filters.search', 'Alpha')
    );
});

test('sync fetches from api and saves to database', function () {
    $user = User::factory()->create();

    $this->mock(HemisApiService::class, function ($mock) {
        $mock->shouldReceive('syncAuditoriums')
            ->once()
            ->andReturn(5);
    });

    $response = $this
        ->actingAs($user)
        ->post('/auditoriums/sync');

    $response->assertRedirect();
    $response->assertSessionHas('success');
});

test('auditoriums page requires authentication', function () {
    $response = $this->get('/auditoriums');

    expect($response->status())->not->toBe(200);
});
