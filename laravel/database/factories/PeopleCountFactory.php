<?php

namespace Database\Factories;

use App\Models\Camera;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PeopleCount>
 */
class PeopleCountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'camera_id' => Camera::factory(),
            'people_count' => fake()->numberBetween(0, 50),
            'snapshot_path' => 'snapshots/camera_' . fake()->randomNumber(3) . '_' . now()->format('Y-m-d_H-i-s') . '.jpg',
            'counted_at' => fake()->dateTimeBetween('-1 day', 'now'),
        ];
    }
}
