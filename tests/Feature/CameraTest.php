<?php

use App\Models\User;
use App\Models\Camera;
use App\Services\MediaMtxService;
use Inertia\Testing\AssertableInertia as Assert;

test('cameras page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/cameras');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Cameras/Index')
    );
});

test('can create camera', function () {
    $user = User::factory()->create();
    
    // Mock the service
    $this->mock(MediaMtxService::class, function ($mock) {
        $mock->shouldReceive('addPath')->once();
    });

    $response = $this
        ->actingAs($user)
        ->post('/cameras', [
            'name' => 'New Camera',
            'ip_address' => '192.168.1.50',
            'port' => 554,
            'username' => 'admin',
            'password' => '12345',
            'is_active' => true,
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('cameras', ['name' => 'New Camera', 'ip_address' => '192.168.1.50']);
});

test('can update camera', function () {
    $user = User::factory()->create();
    $camera = Camera::factory()->create();

    $this->mock(MediaMtxService::class, function ($mock) {
        $mock->shouldReceive('updatePath')->once();
    });

    $response = $this
        ->actingAs($user)
        ->put("/cameras/{$camera->id}", [
            'name' => 'Updated Camera',
            'ip_address' => '192.168.1.51',
            'port' => 554,
            'is_active' => true,
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('cameras', ['id' => $camera->id, 'name' => 'Updated Camera']);
});

test('can search cameras', function () {
    $user = User::factory()->create();
    Camera::factory()->create(['name' => 'Alpha Cam']);
    Camera::factory()->create(['name' => 'Beta Cam']);

    $response = $this
        ->actingAs($user)
        ->get('/cameras?search=Alpha');

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Cameras/Index')
        ->has('cameras.data', 1)
        ->where('cameras.data.0.name', 'Alpha Cam')
    );
});
