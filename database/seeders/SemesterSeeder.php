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
            'type' => 'Spring',
            'start' => '2023-02-02',
            'end' => '2023-06-06',
            'is_current' => false
        ]);
        Semester::create([
            'type' => 'Winter',
            'start' => '2023-09-09',
            'end' => '2024-02-01',
            'is_current' => false
        ]);
        Semester::create([
            'type' => 'Spring',
            'start' => '2024-02-02',
            'end' => '2024-06-06',
            'is_current' => true
        ]);
    }
}
