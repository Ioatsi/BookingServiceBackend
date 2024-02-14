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
            [   'calendar_event_id' => 5,
                'info' => 'Meeting with students'
            ],
            [
                'calendar_event_id' => 6,
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
