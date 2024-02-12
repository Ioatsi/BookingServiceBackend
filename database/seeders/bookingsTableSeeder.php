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
                'start' => '2024-02-11',
                'info' => 'Meeting with clients'
            ],
            [
                'title' => 'Conference',
                'color' => 'blue',
                'start' => '2024-02-12',
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
