<?php

use App\Models\Faculty;
use App\Models\Hemis\Auditorium;
use App\Models\LessonFeedback;
use App\Models\User;
use App\Notifications\DeanFeedbackSubmitted;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(ValidateCsrfToken::class);
    $this->seed(RolesAndPermissionsSeeder::class);
});

function createAuditorium(array $attributes = []): Auditorium
{
    return Auditorium::create(array_merge([
        'code' => fake()->unique()->numberBetween(100, 999),
        'name' => fake()->words(2, true),
        'building_id' => fake()->numberBetween(1, 9),
        'building_name' => fake()->randomElement(['A bino', 'B bino']),
        'volume' => 50,
        'active' => true,
    ], $attributes));
}

it('requires authentication to view feedbacks', function () {
    $response = $this->get('/feedbacks');

    $response->assertRedirect('/login');
});

it('shows only the dean faculty feedbacks on the feedback index', function () {
    $faculty = Faculty::create(['name' => 'Axborot texnologiyalari']);
    $otherFaculty = Faculty::create(['name' => 'Iqtisodiyot']);

    $dean = User::factory()->create(['faculty_id' => $faculty->id]);
    $dean->assignRole('deans');

    $author = User::factory()->create();
    $author->assignRole('department');

    LessonFeedback::factory()->create([
        'user_id' => $author->id,
        'faculty_id' => $faculty->id,
        'lesson_name' => 'Algorithms',
    ]);

    LessonFeedback::factory()->create([
        'user_id' => $author->id,
        'faculty_id' => $otherFaculty->id,
        'lesson_name' => 'Macroeconomics',
    ]);

    $response = $this->actingAs($dean)->get('/feedbacks');

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('LessonFeedbacks/Index')
            ->has('feedbacks.data', 1)
            ->where('feedbacks.data.0.lesson_name', 'Algorithms')
        );
});

it('stores feedback for a single-faculty auditorium and notifies the assigned dean', function () {
    Notification::fake();

    $faculty = Faculty::create(['name' => 'Matematika']);
    $auditorium = createAuditorium();
    $auditorium->faculties()->attach($faculty->id);

    $dean = User::factory()->create(['faculty_id' => $faculty->id]);
    $dean->assignRole('deans');

    $author = User::factory()->create();
    $author->assignRole('department');

    $response = $this->actingAs($author)->post('/feedbacks', [
        'auditorium_id' => $auditorium->id,
        'lesson_name' => 'Math 101',
        'employee_name' => 'John Doe',
        'group_name' => 'Group A',
        'start_time' => '09:00',
        'end_time' => '10:30',
        'type' => 'good',
        'message' => 'Very engaging teacher.',
    ]);

    $response->assertRedirect()
        ->assertSessionHas('success', 'Fikr-mulohaza muvaffaqiyatli saqlandi!');

    $this->assertDatabaseHas('lesson_feedbacks', [
        'user_id' => $author->id,
        'auditorium_id' => $auditorium->id,
        'faculty_id' => $faculty->id,
        'lesson_name' => 'Math 101',
    ]);

    Notification::assertSentTo($dean, DeanFeedbackSubmitted::class);
});

it('requires faculty selection for a multi-faculty auditorium and notifies only the selected dean', function () {
    Notification::fake();

    $faculty = Faculty::create(['name' => 'Filologiya']);
    $otherFaculty = Faculty::create(['name' => 'Tarix']);
    $auditorium = createAuditorium();
    $auditorium->faculties()->attach([$faculty->id, $otherFaculty->id]);

    $selectedDean = User::factory()->create(['faculty_id' => $faculty->id]);
    $selectedDean->assignRole('deans');

    $otherDean = User::factory()->create(['faculty_id' => $otherFaculty->id]);
    $otherDean->assignRole('deans');

    $author = User::factory()->create();
    $author->assignRole('department');

    $invalidResponse = $this->actingAs($author)->postJson('/feedbacks', [
        'auditorium_id' => $auditorium->id,
        'type' => 'bad',
        'message' => 'Needs improvement.',
    ]);

    $invalidResponse->assertStatus(422)->assertJsonValidationErrors(['faculty_id']);

    $response = $this->actingAs($author)->post('/feedbacks', [
        'auditorium_id' => $auditorium->id,
        'faculty_id' => $faculty->id,
        'lesson_name' => 'Literature',
        'employee_name' => 'Jane Doe',
        'group_name' => 'Group B',
        'start_time' => '11:00',
        'end_time' => '12:20',
        'type' => 'bad',
        'message' => 'Needs improvement.',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('lesson_feedbacks', [
        'auditorium_id' => $auditorium->id,
        'faculty_id' => $faculty->id,
        'lesson_name' => 'Literature',
    ]);

    Notification::assertSentTo($selectedDean, DeanFeedbackSubmitted::class);
    Notification::assertNotSentTo($otherDean, DeanFeedbackSubmitted::class);
});

it('rejects feedback for an auditorium with no faculty assignment', function () {
    $author = User::factory()->create();
    $author->assignRole('department');

    $auditorium = createAuditorium();

    $response = $this->actingAs($author)->postJson('/feedbacks', [
        'auditorium_id' => $auditorium->id,
        'type' => 'good',
        'message' => 'No faculty linked.',
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors(['auditorium_id']);
});

it('marks notifications as read only for the authenticated user', function () {
    $user = User::factory()->create();
    $user->assignRole('deans');

    $otherUser = User::factory()->create();
    $otherUser->assignRole('deans');

    $notification = $user->notifications()->create([
        'id' => (string) str()->uuid(),
        'type' => DeanFeedbackSubmitted::class,
        'data' => ['title' => 'Feedback'],
    ]);

    $otherNotification = $otherUser->notifications()->create([
        'id' => (string) str()->uuid(),
        'type' => DeanFeedbackSubmitted::class,
        'data' => ['title' => 'Feedback'],
    ]);

    $response = $this->actingAs($user)->post("/notifications/{$notification->id}/read");

    $response->assertRedirect();

    expect(DatabaseNotification::find($notification->id)?->read_at)->not->toBeNull();
    expect(DatabaseNotification::find($otherNotification->id)?->read_at)->toBeNull();
});
