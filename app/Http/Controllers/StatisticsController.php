<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Semester;
use App\Models\Booking;
use Carbon\Carbon;

use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    //simple count not percentage 
    public function roomDayOfWeekFrequency(Request $request)
    {
        /**
         * Calculate the frequency of bookings for each day of the week for a list of rooms
         *
         * @param Request $request
         * @return array An array of room_id and frequency for each day of the week.
         *               The frequency is the number of bookings on that day of the week.
         *               The frequency is an array with day_of_week as the key and frequency as the value.
         */
        
        // Get the room IDs from the request
        $roomIds = $request->input('roomIds');

        // Get the sample size from the request
        $sample = $request->input('sample');

        $result = [];
        // Get the room IDs from the request
        $roomIds = $request->input('roomIds');

        // Get the sample size from the request
        $sample = $request->input('sample');

        $result = []; // The return value

        foreach ($roomIds as $roomId) {
            switch ($sample) {
                case 'semester':
                    // Get the current semester
                    $semester = Semester::where('is_current', true)->first();

                    // Get the total number of bookings for the room in the current semester
                    $totalBookings = Booking::where('room_id', $roomId)->where('semester_id', $semester->id)->count();

                    // Query to get the bookings for the room in the current semester
                    $frequency = Booking::select(DB::raw('DAYOFWEEK(start) as day_of_week'), DB::raw('count(*) as frequency'))
                        ->where('semester_id', $semester->id)
                        ->where('room_id', $roomId)
                        ->groupBy(DB::raw('DAYOFWEEK(start)'))
                        ->orderBy('day_of_week', 'asc')
                        ->get();
                    break;
                case 'year':
                    // Get the start and end of the current year
                    $startOfYear = Carbon::now()->subYear();
                    $endOfYear = Carbon::now();

                    // Get the total number of bookings for the room in the current year
                    $totalBookings = Booking::where('room_id', $roomId)->where('start', '>=', $startOfYear->format('Y-m-d'))
                        ->where('start', '<=', $endOfYear->format('Y-m-d'))->count();

                    // Query to get the bookings for the room in the current year
                    $frequency = Booking::select(DB::raw('DAYOFWEEK(start) as day_of_week'), DB::raw('count(*) as frequency'))
                        ->where('start', '>=', $startOfYear)
                        ->where('start', '<=', $endOfYear)
                        ->where('room_id', $roomId)
                        ->groupBy(DB::raw('DAYOFWEEK(start)'))
                        ->orderBy('day_of_week', 'asc')
                        ->get();
                    break;
                case 'all':
                    // Get the total number of bookings for the room
                    $totalBookings = Booking::where('room_id', $roomId)->count();

                    // Query to get the bookings for the room
                    $frequency = Booking::select(DB::raw('DAYOFWEEK(start) as day_of_week'), DB::raw('count(*) as frequency'))
                        ->where('room_id', $roomId)
                        ->groupBy(DB::raw('DAYOFWEEK(start)'))
                        ->orderBy('day_of_week', 'asc')
                        ->get();
                    break;
            }

            // Create an associative array with day_of_week as keys and frequency as values
            $frequencyMap = [];
            foreach ($frequency as $item) {
                $frequencyMap[$item->day_of_week] = $item->frequency;
            }

            // Fill in missing days with a frequency of 0
            $fullFrequency = [];
            for ($i = 1; $i <= 7; $i++) { // Assuming day_of_week ranges from 1 (Monday) to 7 (Sunday)
                if (isset($frequencyMap[$i])) {
                    if ($request->input('percentage') == $i) {
                        $percentage = round(($frequencyMap[$i] / $totalBookings) * 100);
                        $fullFrequency[] = ['day_of_week' => $i, 'frequency' => $percentage];
                    } else {
                        $fullFrequency[] = ['day_of_week' => $i, 'frequency' => $frequencyMap[$i]];
                    }
                } else {
                    $fullFrequency[] = ['day_of_week' => $i, 'frequency' => 0];
                }
            }

            $result[] = [
                'room_id' => $roomId,
                'frequency' => $fullFrequency
            ];
        }


        return $result;
    }

    /* public function roomDayFrequencyPercentage(Request $request)
    {
        // Get the room ID from the request
        $roomId = $request->input('id');

        // Query to get the total bookings for the room
        $totalBookings = Booking::where('room_id', $roomId)->count();

        // Query to get the frequency
        $frequency = Booking::select(
            DB::raw('DAYOFWEEK(start) as day_of_week'),
            DB::raw('count(*) as frequency')
        )
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
    } */

    public function roomMonthFrequency(Request $request)
    {
        // Get the room ID from the request
        $roomId = $request->input('id');

        // Query to get the frequency
        $frequency = Booking::select(DB::raw('MONTH(start) as month'), DB::raw('count(*) as frequency'))
            ->where('room_id', $roomId)
            ->groupBy(DB::raw('MONTH(start)'))
            ->orderBy('month', 'asc')
            ->get();

        // Create an associative array with month as keys and frequency as values
        $frequencyMap = [];
        foreach ($frequency as $item) {
            $frequencyMap[$item->month] = $item->frequency;
        }

        // Fill in missing months with a frequency of 0
        $fullFrequency = [];
        for ($i = 1; $i <= 12; $i++) { // Assuming month ranges from 1 (January) to 12 (December)
            if (isset($frequencyMap[$i])) {
                $fullFrequency[] = ['month' => $i, 'frequency' => $frequencyMap[$i]];
            } else {
                $fullFrequency[] = ['month' => $i, 'frequency' => 0];
            }
        }

        return $fullFrequency;
    }
}
