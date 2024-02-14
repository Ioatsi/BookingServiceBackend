<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class calendareventstableseeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $events = [
            [
                'start' => now(),
                'end' => now()->addHours(2),
                'title' => 'Sample Event 1',
                'color' => 'red'
            ],
            [
                'start' => now()->addDays(1),
                'end' => now()->addDays(2),
                'title' => 'Sample Event 2',
                'color' => 'blue'
            ],
        ];
        DB::table('calendar_events')->insert($events);
    }
}
