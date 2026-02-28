<?php

use App\Models\Camera;
use Inertia\Testing\AssertableInertia as Assert;

test('public streams page is accessible', function () {
    $response = $this->get(route('public.streams'));

    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Public/Stream')
        ->has('cameras')
    );
});

test('public streams page lists active cameras', function () {
    $activeCamera = Camera::factory()->create(['is_active' => true]);
    $inactiveCamera = Camera::factory()->create(['is_active' => false]);

    $response = $this->get(route('public.streams'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('cameras.0.id', $activeCamera->id)
        ->has('cameras', 1)
    );
});
