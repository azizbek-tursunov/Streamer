<?php

use App\Models\Hemis\Auditorium;
use App\Models\LessonFeedback;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(ValidateCsrfToken::class);
});

it('requires authentication to view feedbacks', function () {
    $response = $this->get('/feedbacks');

    $response->assertRedirect('/login');
});

it('can list feedbacks on index page', function () {
    $user = User::factory()->create();
    LessonFeedback::factory()->count(3)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get('/feedbacks');

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('LessonFeedbacks/Index')
            ->has('feedbacks.data', 3)
        );
});

it('requires authentication to submit feedback', function () {
    $response = $this->postJson('/feedbacks', [
        'type' => 'good',
        'message' => 'Great lesson!',
    ]);

    $response->assertStatus(401);
});

it('can store a new text feedback', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/feedbacks', [
        'auditorium_id' => null,
        'lesson_name' => 'Math 101',
        'employee_name' => 'John Doe',
        'group_name' => 'Group A',
        'start_time' => '09:00',
        'end_time' => '10:30',
        'type' => 'good',
        'message' => 'Very engaging teacher.',
    ]);

    // Inertia back() redirects usually return 302
    $response->assertStatus(302)
        ->assertSessionHas('success', 'Fikr-mulohaza muvaffaqiyatli saqlandi!');

    $this->assertDatabaseHas('lesson_feedbacks', [
        'user_id' => $user->id,
        'lesson_name' => 'Math 101',
        'type' => 'good',
        'message' => 'Very engaging teacher.',
    ]);
});

it('fails validation when type is missing', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/feedbacks', [
        'message' => 'No type selected.',
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors(['type']);
});
