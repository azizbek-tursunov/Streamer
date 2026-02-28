<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Camera>
 */
class CameraFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'username' => $this->faker->userName(),
            'password' => $this->faker->password(),
            'ip_address' => $this->faker->ipv4(),
            'port' => 554,
            'stream_path' => '/stream',
            'is_active' => $this->faker->boolean(),
            'is_streaming_to_youtube' => false,
            'youtube_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
