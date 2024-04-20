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
    //Room Booking Frequency by Range with percentage and dynamic samples(to be implemented) 
    public function roomDayOfWeekFrequency(Request $request)
    {
        /**
         * Calculate the frequency of bookings for each day of the week for a list of rooms
         * 
         * Sample unit is the semester
         *
         * @param Request $request
         * @return array An array of room_id and frequency for each day of the week.
         *               The frequency is the number of bookings on that day of the week.
         *               The frequency is an array with day_of_week as the key and frequency as the value.
         */

        // Get the room IDs from the request
        $roomIds = $request->input('roomIds');


        $semesterIds = $request->input('semesterIds');

        $result = []; // The return value

        foreach ($roomIds as $roomId) {
            // Get the total number of bookings for the room in the current semester
            $totalBookings = Booking::where('room_id', $roomId)->whereIn('semester_id', $semesterIds)->count();

            // Query to get the bookings for the room in the current semester
            $frequency = Booking::select(DB::raw('DAYOFWEEK(start) as day_of_week'), DB::raw('count(*) as frequency'))
                ->whereIn('semester_id', $semesterIds)
                ->where('room_id', $roomId)
                ->where('status', 1)
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
            for ($i = 1; $i <= 7; $i++) {
                $label = Carbon::create()->startOfWeek()->addDays($i - 1)->format('l');
                if (isset($frequencyMap[$i])) {
                    $percentage = round(($frequencyMap[$i] / $totalBookings) * 100);
                    $fullFrequency[] = ['label' => $label, 'frequency' => $percentage, 'percentage' => $percentage];
                } else {
                    $fullFrequency[] = ['label' => $label, 'frequency' => 0];
                }
            }

            $result[] = [
                'room_id' => $roomId,
                'frequency' => $fullFrequency
            ];
        }


        return $result;
    }
    public function roomDayOfMonthFrequency(Request $request)
    {
        /**
         * Calculate the frequency of bookings for each day of given months for a list of rooms
         * 
         * Sample is either some years or all years
         *
         * @param Request $request
         * @return array An array of room_id and frequency for each day of the month.
         *               The frequency is the number of bookings on that day of the month.
         *               The frequency is an array with day_of_month as the key and frequency as the value.
         */

        // Get the room IDs from the request
        $roomIds = $request->input('roomIds');

        // Get the sample size from the request
        $sample = $request->input('sample');

        // Get the month and year from the request
        $months = $request->input('months');

        $years = $request->input('years');

        $result = []; // The return value


        foreach ($roomIds as $roomId) {
            foreach ($months as $month) {
                // Get the total number of days in the given month
                $totalDays = Carbon::createFromDate($month)->daysInMonth;
                switch ($sample) {
                    case 'some':
                        $totalBookings = Booking::where('room_id', $roomId)
                            ->whereYear('start', '=', $years)
                            ->whereMonth('start', '=', $month)
                            ->count();

                        // Query to get the bookings for the room on the last day of the last elapsed month
                        $frequency = Booking::select(DB::raw('DAY(start) as day_of_month'), DB::raw('count(*) as frequency'))
                            ->whereYear('start', '=', $years)
                            ->whereMonth('start', '=', $month)
                            ->where('room_id', $roomId)
                            ->where('status', 1)
                            ->groupBy(DB::raw('DAY(start)'))
                            ->orderBy('day_of_month', 'asc')
                            ->get();
                        // Create an associative array with day_of_month as keys and frequency as values
                        $frequencyMap = [];
                        foreach ($frequency as $item) {
                            $frequencyMap[$item->day_of_month] = $item->frequency;
                        }
                        break;

                    case 'all':
                        // Get the total number of bookings for the room in the given month
                        $totalBookings = Booking::where('room_id', $roomId)
                            ->whereMonth('start', $month)
                            ->count();

                        // Query to get the bookings for the room in the given month
                        $frequency = Booking::select(DB::raw('DAY(start) as day_of_month'), DB::raw('count(*) as frequency'))
                            ->whereMonth('start', $month)
                            ->where('room_id', $roomId)
                            ->where('status', 1)
                            ->groupBy(DB::raw('DAY(start)'))
                            ->orderBy('day_of_month', 'asc')
                            ->get();
                        // Create an associative array with day_of_month as keys and frequency as values
                        $frequencyMap = [];
                        foreach ($frequency as $item) {
                            $frequencyMap[$item->day_of_month] = $item->frequency;
                        }
                        break;
                }
                // Fill in missing days with a frequency of 0
                $fullFrequency = [];
                for ($i = 1; $i <= $totalDays; $i++) {
                    if (isset($frequencyMap[$i])) {
                        $percentage = round(($frequencyMap[$i] / $totalBookings) * 100);
                        $fullFrequency[] = ['label' => $i, 'frequency' => $frequencyMap[$i], 'percentage' => $percentage];
                    } else {
                        $fullFrequency[] = ['label' => $i, 'frequency' => 0, 'percentage' => 0];
                    }
                }
                $frequencyMonthMap[] = ['month' => $month, 'frequency' => $fullFrequency];
            }
            $result[] = [
                'room_id' => $roomId,
                'frequency' => $frequencyMonthMap
            ];
        }

        return $result;
    }
    public function roomMonthOfSemesterFrequency(Request $request)
    {
        /**
         * Calculate the frequency of bookings for each month of given semesters for a list of rooms
         *
         * @param Request $request
         * @param int $semesterId
         * @return array An array of room_id and frequency for each month of the semester.
         *               The frequency is the number of bookings in that month of the semester.
         *               The frequency is an array with month as the key and frequency as the value.
         */

        // Get the room IDs from the request
        $roomIds = $request->input('roomIds');

        $semesterIds = $request->input('semesterIds');

        $result = []; // The return value
        foreach ($roomIds as $roomId) {
            $frequencyMap = [];
            $semester = Semester::whereIn('id', $semesterIds)->first();

            // Fetch semester start and end dates from the Semester model
            $semesterStartDate = Carbon::parse($semester->start);
            $semesterEndDate = Carbon::parse($semester->end);

            // Handling for months of the current semester
            $monthsInSemester = $this->getMonthsInRange($semesterStartDate, $semesterEndDate);
            // Create an associative array with day_of_month as keys and frequency as values


            // Get the total number of bookings for the room in the given semester
            $totalBookings = Booking::where('room_id', $roomId)
                ->whereIn('semester_id', $semesterIds)
                ->where('status', 1)
                ->count();
            foreach ($monthsInSemester as $month) {
                // Query to get the bookings for the room in the given month
                $frequency = Booking::whereMonth('start', $month)
                    ->where('room_id', $roomId)
                    ->where('status', 1)
                    ->count();

                $label = Carbon::create()->month($month)->format('F');
                $frequencyMap[] = ['label' => $label, 'frequency' => $frequency, 'percentage' => round(($frequency / $totalBookings) * 100)];
            }
            
            $result[] = [
                'room_id' => $roomId,
                'frequency' => $frequencyMap
            ];
        }

        return $result;
    }
    private function getMonthsInRange($startDate = null, $endDate = null)
    {
        $months = [];

        if ($startDate === null) {
            $startDate = Carbon::now()->startOfYear();
        }

        if ($endDate === null) {
            $endDate = Carbon::now()->endOfYear();
        }

        $currentMonth = Carbon::parse($startDate)->copy();
        $endDateCopy = Carbon::parse($endDate);
        while ($currentMonth->lte($endDateCopy)) {
            $months[] = $currentMonth->month;
            $currentMonth->addMonth();
        }
        return $months;
    }
    //Room Booking Duration Frequency by Range with percentage and dynamic samples(to be implemented) 
    public function roomDayOfWeekDurationFrequency(Request $request)
    {
        $roomIds = $request->input('roomIds');

        $days = $request->input('days');

        // Get the sample size from the request
        $semesterIds = $request->input('semesterIds');
        
        foreach ($roomIds as $roomId) {
            $frequencyMap = [];
            $frequencyDayMap = [];
            foreach ($days as $day) {
                    $totalBookings = Booking::where('room_id', $roomId)
                        ->where('status', 1)
                        ->whereRaw('DAYOFWEEK(start) = ?', [$day])
                        ->whereIn('semester_id', $semesterIds)
                        ->count();
                    $frequency = Booking::select(
                        DB::raw('TIMESTAMPDIFF(HOUR, start, end) as duration'),
                        DB::raw('COUNT(*) as frequency')
                    )
                        ->whereRaw('DAYOFWEEK(start) = ?', [$day])
                        ->where('room_id', $roomId)                        
                        ->whereIn('semester_id', $semesterIds)
                        ->where('status', 1)
                        ->groupBy('duration')
                        ->orderBy('duration')
                        ->get();

                    foreach ($frequency as $key => $value) {
                        $frequencyMap[] = ['label' => $value->duration, 'frequency' => $value->frequency, 'percentage' => round(($value->frequency / $totalBookings) * 100)];
                    }
                    
                $frequencyDayMap[] = ['day' => $day, 'frequency' => $frequencyMap];
            }
            $result[] = [
                'room_id' => $roomId,
                'frequency' => $frequencyDayMap
            ];
        }

        return $result;
    }
    public function roomMonthOfYearDurationFrequency(Request $request)
    {
        $roomIds = $request->input('roomIds');

        $months = $request->input('months');

        // Get the sample size from the request in this context is years
        $year = $request->input('year');

        $semester = Semester::where('is_current', 1)->first();
        foreach ($roomIds as $roomId) {
            $frequencyMap = [];
            $frequencyDayMap = [];
            foreach ($months as $month) {
                $totalBookings = Booking::where('room_id', $roomId)
                    ->where('status', 1)
                    ->whereYear('start', $year)
                    ->whereMonth('start', '=', $month)
                    ->where('semester_id', $semester->id)
                    ->count();
                $frequency = Booking::select(
                    DB::raw('TIMESTAMPDIFF(HOUR, start, end) as duration'),
                    DB::raw('COUNT(*) as frequency')
                )
                    ->whereYear('start', $year)
                    ->whereMonth('start', '=', $month)
                    ->where('room_id', $roomId)
                    ->where('semester_id', $semester->id)
                    ->where('status', 1)
                    ->groupBy('duration')
                    ->orderBy('duration')
                    ->get();

                foreach ($frequency as $key => $value) {
                    $frequencyMap[] = ['label' => $value->duration, 'frequency' => $value->frequency, 'percentage' => round(($value->frequency / $totalBookings) * 100)];
                }
                $frequencyDayMap[] = ['month' => $month, 'frequency' => $frequencyMap];
            }
            $result[] = [
                'room_id' => $roomId,
                'frequency' => $frequencyDayMap
            ];
        }

        return $result;
    }
    public function roomOccupancyByYearMonthPercentage(Request $request)
    {
        $roomIds = $request->input('roomIds');
        foreach ($roomIds as $roomId) {
            $year = $request->input('year');
            $month = $request->input('month');
            // Get the first and last day of the specified month
            $firstDayOfMonth = "{$year}-{$month}-01";
            $lastDayOfMonth = date('Y-m-t', strtotime($firstDayOfMonth));

            // Calculate the total number of days in the month
            $totalDaysInMonth = (int)date('t', strtotime($firstDayOfMonth));

            // Retrieve all bookings for the specified room and month
            $bookings = Booking::where('room_id', $roomId)
                ->whereBetween('start', [$firstDayOfMonth, $lastDayOfMonth])
                ->get();

            // Calculate the total booked hours in the month
            $totalBookedHours = 0;
            foreach ($bookings as $booking) {
                $startTime = strtotime($booking->start);
                $endTime = strtotime($booking->end);
                $totalBookedHours += ($endTime - $startTime) / (60 * 60); // Convert seconds to hours
            }

            // Calculate the total available hours in the month (assuming 8 hours per day)
            $totalAvailableHours = $totalDaysInMonth * 8;

            // Calculate the occupancy percentage
            if ($totalAvailableHours > 0) {
                $percentage = round(($totalBookedHours / $totalAvailableHours) * 100);
            } else {
                $percentage = 0; // No available hours, so occupancy is 0%
            }

            $result[] = [
                'room_id' => $roomId,
                'total' => $totalBookedHours,
                'percentage' => $percentage
            ];
        }

        return $result;
    }

    //ToDo: Think about thses again is there a way to merge them sto ranges and samples are dynamic and avoid multiple functs and switches
    //Also do these ranges make sense? Can only the samples be dynamic? Does it make sense for them to be? Figure it out, make a list and a choice
    //Adjust after feedback 
}
