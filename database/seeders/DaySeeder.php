<?php

namespace Database\Seeders;

use App\Models\Day;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        Day::create([
            'day' => 'Monday',
            'recurring_id' => 1,
            'start' => '10:00:00',
            'end' => '12:00:00',
        ]);
        Day::create([
            'day' => 'Thursday',
            'recurring_id' => 1,
            'start' => '10:00:00',
            'end' => '12:00:00',
        ]);
    }
}
