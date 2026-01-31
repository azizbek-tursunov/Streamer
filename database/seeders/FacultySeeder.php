<?php

namespace Database\Seeders;

use App\Models\Faculty;
use Illuminate\Database\Seeder;

class FacultySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faculties = [
            'Matematika',
            'Fizika',
            'Kimyo',
            'Biologiya',
            'Tarix',
            'Informatika',
        ];

        foreach ($faculties as $name) {
            Faculty::firstOrCreate(['name' => $name]);
        }
    }
}
