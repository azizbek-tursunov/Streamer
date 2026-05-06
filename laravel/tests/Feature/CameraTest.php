<?php

use App\Models\Camera;
use App\Models\User;
use App\Services\MediaMtxService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->withoutMiddleware(ValidateCsrfToken::class);
});

function cameraUser(array $permissions): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission);
    }

    $user->givePermissionTo($permissions);

    return $user;
}

test('cameras page is displayed', function () {
    $user = cameraUser(['view-cameras']);

    $response = $this
        ->actingAs($user)
        ->get('/cameras');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Cameras/Index')
    );
});

test('can create camera', function () {
    $user = cameraUser(['manage-cameras']);

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
    $user = cameraUser(['manage-cameras']);
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

test('can save youtube stream key url without starting stream', function () {
    $user = cameraUser(['manage-cameras']);
    $camera = Camera::factory()->create([
        'is_streaming_to_youtube' => false,
        'youtube_url' => null,
    ]);

    $this->mock(MediaMtxService::class, function ($mock) {
        $mock->shouldNotReceive('updatePath');
    });

    $response = $this
        ->actingAs($user)
        ->put("/cameras/{$camera->id}/youtube", [
            'youtube_url' => 'rtmp://a.rtmp.youtube.com/live2/abcd-1234-efgh-5678',
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('cameras', [
        'id' => $camera->id,
        'youtube_url' => 'rtmp://a.rtmp.youtube.com/live2/abcd-1234-efgh-5678',
        'is_streaming_to_youtube' => false,
    ]);
});

test('saving youtube url resyncs mediamtx while already streaming', function () {
    $user = cameraUser(['manage-cameras']);
    $camera = Camera::factory()->create([
        'is_streaming_to_youtube' => true,
        'youtube_url' => 'rtmp://a.rtmp.youtube.com/live2/old-key',
    ]);

    $this->mock(MediaMtxService::class, function ($mock) {
        $mock->shouldReceive('updatePath')->once();
    });

    $response = $this
        ->actingAs($user)
        ->put("/cameras/{$camera->id}/youtube", [
            'youtube_url' => 'rtmps://a.rtmps.youtube.com/live2/new-key',
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('cameras', [
        'id' => $camera->id,
        'youtube_url' => 'rtmps://a.rtmps.youtube.com/live2/new-key',
        'is_streaming_to_youtube' => true,
    ]);
});

test('can search cameras', function () {
    $user = cameraUser(['view-cameras']);
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
