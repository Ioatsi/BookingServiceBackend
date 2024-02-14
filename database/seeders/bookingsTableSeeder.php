<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class bookingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bookings = [
            [
                'title' => 'Meeting',
                'color' => 'red',
                'start' => now(),
                'end' => now()->addHours(2),
                'info' => 'Meeting with students'
            ],
            [
                'title' => 'Conference',
                'color' => 'blue',
                'start' => now()->addDays(1),
                'end' => now()->addDays(2),
                'info' => 'Tech conference'
            ],
            // Add more sample bookings as needed
        ];

        // Insert sample data into the bookings table
        foreach ($bookings as $booking) {
            DB::table('bookings')->insert($booking);
        }
    }
}
