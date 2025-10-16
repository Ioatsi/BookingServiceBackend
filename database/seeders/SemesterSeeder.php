<?php

namespace Database\Seeders;

use App\Models\Semester;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class SemesterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        $year = $now->year;

        // Define semesters dynamically
        $semesters = [
            [
                'type' => 'Spring',
                'start' => Carbon::create($year, 2, 1),
                'end'   => Carbon::create($year, 6, 30),
            ],
            [
                'type' => 'Summer',
                'start' => Carbon::create($year, 7, 1),
                'end'   => Carbon::create($year, 8, 31),
            ],
            [
                'type' => 'Winter',
                'start' => Carbon::create($year, 9, 1),
                'end'   => Carbon::create($year + 1, 1, 31),
            ],
        ];

        foreach ($semesters as $semester) {
            Semester::create([
                'type' => $semester['type'],
                'start' => $semester['start'],
                'end' => $semester['end'],
                'is_current' => $now->between($semester['start'], $semester['end']),
            ]);
        }
    }
}
