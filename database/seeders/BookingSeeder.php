<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Booking;
use Carbon\Carbon;

class BookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Booking::create([
            'group_id' => 1,
            'booker_id' => 1,
            'semester_id' => 1,
            'room_id' => 1,
            'status' => 'status_value',
            'title' => 'Meeting',
            'start' => Carbon::now()->addHours(1),
            'end' => Carbon::now()->addHours(2),
            'color' => 'color_value',
            'info' => 'Meeting with students',
            'participants' => 'participants_value',
            'type' => 'type_value',
        ]);
    }
}
