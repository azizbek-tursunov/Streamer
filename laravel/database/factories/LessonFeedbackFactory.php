<?php

namespace Database\Factories;

use App\Models\LessonFeedback;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LessonFeedbackFactory extends Factory
{
    protected $model = LessonFeedback::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'lesson_name' => $this->faker->words(2, true),
            'employee_name' => $this->faker->name(),
            'group_name' => $this->faker->bothify('Group-##'),
            'type' => $this->faker->randomElement(['good', 'bad']),
            'message' => $this->faker->sentence(),
        ];
    }
}
