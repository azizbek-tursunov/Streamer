<?php

namespace Database\Seeders;

use App\Models\Floor;
use Illuminate\Database\Seeder;

class FloorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $floors = [
            '1-qavat',
            '2-qavat',
            '3-qavat',
            '4-qavat',
        ];

        foreach ($floors as $name) {
            Floor::firstOrCreate(['name' => $name]);
        }
    }
}
