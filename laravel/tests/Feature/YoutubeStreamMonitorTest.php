<?php

use App\Models\Camera;
use App\Models\User;
use App\Services\MediaMtxService;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

test('admin can view youtube stream monitor', function () {
    $user = User::factory()->create();
    Role::findOrCreate('admin');
    $user->assignRole('admin');

    $camera = Camera::factory()->create([
        'name' => 'YouTube Camera',
        'youtube_url' => 'rtmp://a.rtmp.youtube.com/live2/test-key',
        'is_active' => true,
        'is_streaming_to_youtube' => true,
    ]);

    $this->mock(MediaMtxService::class, function ($mock) use ($camera) {
        $mock->shouldReceive('listPathStates')->once()->andReturn([
            "cam_{$camera->id}" => [
                'name' => "cam_{$camera->id}",
                'ready' => true,
                'readyTime' => '2026-05-06T11:43:00Z',
                'readers' => [
                    ['type' => 'rtspSession'],
                ],
                'tracks' => ['H264', 'Opus'],
                'bytesReceived' => 1024,
                'bytesSent' => 2048,
            ],
        ]);
        $mock->shouldReceive('getPathName')->andReturnUsing(fn (Camera $camera) => "cam_{$camera->id}");
    });

    $response = $this
        ->actingAs($user)
        ->get('/system/youtube-streams');

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('System/YoutubeStreams')
            ->where('stats.pushing', 1)
        );

    $streams = collect($response->viewData('page')['props']['streams']);
    $stream = $streams->firstWhere('id', $camera->id);

    expect($stream)->not->toBeNull()
        ->and($stream['status'])->toBe('pushing');
});
