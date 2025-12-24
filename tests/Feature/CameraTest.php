<?php

use App\Models\User;
use App\Models\Camera;
use App\Services\MediaMtxService;

test('cameras page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/cameras');

    $response->assertOk();
});

test('can create camera', function () {
    $user = User::factory()->create();
    
    // Mock the service to avoid actual API calls during test
    $this->mock(MediaMtxService::class, function ($mock) {
        $mock->shouldReceive('addPath')->once();
    });

    $response = $this
        ->actingAs($user)
        ->post('/cameras', [
            'name' => 'Test Camera',
            'rtsp_url' => 'rtsp://test',
            'youtube_url' => 'rtmp://youtube',
            'is_active' => true,
        ]);

    $response->assertRedirect('/cameras');
    $this->assertDatabaseHas('cameras', ['name' => 'Test Camera']);
});
