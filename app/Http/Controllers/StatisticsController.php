<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking;

use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    //simple count not percentage 
    public function roomDayFrequency(Request $request)
{
    // Get the room ID from the request
    $roomId = $request->input('id');

    // Query to get the frequency
    $frequency = Booking::select(DB::raw('DAYOFWEEK(start) as day_of_week'), DB::raw('count(*) as frequency'))
        ->where('room_id', $roomId)
        ->groupBy(DB::raw('DAYOFWEEK(start)'))
        ->orderBy('day_of_week', 'asc')
        ->get();

    // Create an associative array with day_of_week as keys and frequency as values
    $frequencyMap = [];
    foreach ($frequency as $item) {
        $frequencyMap[$item->day_of_week] = $item->frequency;
    }

    // Fill in missing days with a frequency of 0
    $fullFrequency = [];
    for ($i = 1; $i <= 7; $i++) { // Assuming day_of_week ranges from 1 (Sunday) to 7 (Saturday)
        if (isset($frequencyMap[$i])) {
            $fullFrequency[] = ['day_of_week' => $i, 'frequency' => $frequencyMap[$i]];
        } else {
            $fullFrequency[] = ['day_of_week' => $i, 'frequency' => 0];
        }
    }

    return $fullFrequency;
}
public function roomDayFrequencyPercentage(Request $request)
{
    // Get the room ID from the request
    $roomId = $request->input('id');

    // Query to get the total bookings for the room
    $totalBookings = Booking::where('room_id', $roomId)->count();

    // Query to get the frequency
    $frequency = Booking::select(DB::raw('DAYOFWEEK(start) as day_of_week'), 
        DB::raw('count(*) as frequency'))
        ->where('room_id', $roomId)
        ->groupBy(DB::raw('DAYOFWEEK(start)'))
        ->orderBy('day_of_week', 'asc')
        ->get();

    // Create an associative array with day_of_week as keys and frequency as values
    $frequencyMap = [];
    foreach ($frequency as $item) {
        $frequencyMap[$item->day_of_week] = $item->frequency;
    }

    // Fill in missing days with a frequency of 0
    $fullFrequency = [];
    for ($i = 1; $i <= 7; $i++) { // Assuming day_of_week ranges from 1 (Sunday) to 7 (Saturday)
        if (isset($frequencyMap[$i])) {
            // Calculate the frequency as a percentage of total bookings and round to remove decimals
            $percentage = round(($frequencyMap[$i] / $totalBookings) * 100);
            $fullFrequency[] = ['day_of_week' => $i, 'frequency' => $percentage];
        } else {
            $fullFrequency[] = ['day_of_week' => $i, 'frequency' => 0];
        }
    }

    return $fullFrequency;
}




}
