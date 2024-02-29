<?php

namespace Database\Seeders;

use App\Models\Semester;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SemesterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Semester::create([
            'type' => 'Winter',
            'start' => date('2024-01-01'),
            'end' => date('2024-06-06'),
            'is_current' => true
        ]);
    }
}
