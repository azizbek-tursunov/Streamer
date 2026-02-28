<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = [
            'Yuksalish',
            'Lola',
        ];

        foreach ($branches as $name) {
            Branch::firstOrCreate(['name' => $name]);
        }
    }
}
