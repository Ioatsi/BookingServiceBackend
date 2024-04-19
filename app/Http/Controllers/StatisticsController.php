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
                        ->where('status', 1)
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
                        ->where('status', 1)
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
            for ($i = 1; $i <= 7; $i++) {
                $label = Carbon::create()->startOfWeek()->addDays($i-1)->format('l'); 
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
         * Calculate the frequency of bookings for each day of a given month for a list of rooms
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
        $month = $request->input('month');

        $result = []; // The return value


        // Get the total number of days in the given month
        $totalDays = Carbon::createFromDate($month)->daysInMonth;
        foreach ($roomIds as $roomId) {
            switch ($sample) {
                case 'last':
                    // Get the current date
                    $currentDate = Carbon::now();

                    // If the given month is the current month, subtract a year
                    if ($month == $currentDate->month) {
                        $currentDate->subYear();
                    } else if ($month > $currentDate->month) {
                        // If the given month is in the future of the current month, subtract a year
                        $currentDate->subYear();
                    }

                    // Set the month to the given month
                    $currentDate->month($month);

                    // Find the last occurrence of the given month
                    while ($currentDate->month == $month) {
                        $lastMonth = $currentDate->copy();
                        $currentDate->subMonth();
                    }
                    $totalBookings = Booking::where('room_id', $roomId)
                        ->whereMonth('start', '=', Carbon::parse($lastMonth)->month)
                        ->count();

                    // Query to get the bookings for the room on the last day of the last elapsed month
                    $frequency = Booking::select(DB::raw('DAY(start) as day_of_month'), DB::raw('count(*) as frequency'))
                        ->whereYear('start', '=', Carbon::parse($lastMonth)->year)
                        ->whereMonth('start', '=', Carbon::parse($lastMonth)->month)
                        ->where('room_id', $roomId)
                        ->where('status', 1)
                        ->groupBy(DB::raw('DAY(start)'))
                        ->orderBy('day_of_month', 'asc')
                        ->get();
                    break;
                case 'current':
                    // Get the current date
                    $currentDate = Carbon::now();

                    $totalBookings = Booking::where('room_id', $roomId)
                        ->whereMonth('start', '=', Carbon::parse($currentDate)->month)
                        ->count();

                    // Query to get the bookings for the room on the last day of the last elapsed month
                    $frequency = Booking::select(DB::raw('DAY(start) as day_of_month'), DB::raw('count(*) as frequency'))
                        ->whereYear('start', '=', Carbon::parse($currentDate)->year)
                        ->whereMonth('start', '=', Carbon::parse($currentDate)->month)
                        ->where('room_id', $roomId)
                        ->where('status', 1)
                        ->groupBy(DB::raw('DAY(start)'))
                        ->orderBy('day_of_month', 'asc')
                        ->get();
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
                    break;
            }

            // Create an associative array with day_of_month as keys and frequency as values
            $frequencyMap = [];
            foreach ($frequency as $item) {
                $frequencyMap[$item->day_of_month] = $item->frequency;
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

            $result[] = [
                'room_id' => $roomId,
                'frequency' => $fullFrequency
            ];
        }

        return $result;
    }
    public function roomMonthOfSemesterFrequency(Request $request)
    {
        /**
         * Calculate the frequency of bookings for each month of a given semester for a list of rooms
         *
         * @param Request $request
         * @param int $semesterId
         * @return array An array of room_id and frequency for each month of the semester.
         *               The frequency is the number of bookings in that month of the semester.
         *               The frequency is an array with month as the key and frequency as the value.
         */

        // Get the room IDs from the request
        $roomIds = $request->input('roomIds');

        // Get the sample size from the request
        $sample = $request->input('sample');

        // Get the semester ID from the request
        $semesterId = $request->input('semesterId');

        $percentage = $request->input('percentage', false);

        $result = []; // The return value
        $semester = Semester::where('is_current', true)->first();
        foreach ($roomIds as $roomId) {
            $frequencyMap = [];
            switch ($sample) {
                case 'current':
                    // Fetch semester start and end dates from the Semester model
                    $semesterStartDate = Carbon::parse($semester->start);
                    $semesterEndDate = Carbon::parse($semester->end);

                    // Handling for months of the current semester
                    $monthsInSemester = $this->getMonthsInRange($semesterStartDate, $semesterEndDate);
                    // Create an associative array with day_of_month as keys and frequency as values


                    // Get the total number of bookings for the room in the given semester
                    $totalBookings = Booking::where('room_id', $roomId)
                        ->where('semester_id', $semester->id)
                        ->where('status', 1)
                        ->count();
                    foreach ($monthsInSemester as $month) {
                        // Query to get the bookings for the room in the given month
                        $frequency = Booking::whereYear('start', '=', Carbon::parse($semester->start)->year)
                            ->whereMonth('start', $month)
                            ->where('room_id', $roomId)
                            ->where('status', 1)
                            ->count();

                        $label = Carbon::create()->month($month)->format('F');
                        $frequencyMap[] = ['label' => $label, 'frequency' => $frequency, 'percentage' => round(($frequency / $totalBookings) * 100)];
                    }
                    break;

                case 'last':
                    $lastSemester = Semester::where('id', $semester->id - 2)->first();
                    // Calculate the start and end dates of the last semester
                    $lastSemesterStartDate = Carbon::parse($lastSemester->start)->copy();
                    $lastSemesterEndDate = Carbon::parse($lastSemester->end)->copy();
                    // Handling for months of the last semester
                    $monthsInLastSemester = $this->getMonthsInRange($lastSemesterStartDate, $lastSemesterEndDate);

                    // Get the total number of bookings for the room in the given month
                    $totalBookings = Booking::where('room_id', $roomId)
                        ->where('semester_id', $semester->id)
                        ->where('status', 1)
                        ->count();
                    foreach ($monthsInLastSemester as $month) {
                        // Query to get the bookings for the room in the given month
                        $frequency = Booking::whereYear('start', '=', Carbon::parse($lastSemester->start)->year)
                            ->whereMonth('start', $month) // Remove quotes around $month
                            ->where('room_id', $roomId)
                            ->where('status', 1)
                            ->count();
                        $label = Carbon::create()->month($month)->format('F');
                        $frequencyMap[] = ['label' => $label, 'frequency' => $frequency, 'percentage' => round(($frequency / $totalBookings) * 100)];
                    }
                    break;
                case 'all':
                    // Handling for all months of the year of all years
                    $monthsInLastSemester = $this->getMonthsInRange();

                    // Get the total number of bookings for the room in the given month
                    $totalBookings = Booking::where('room_id', $roomId)
                        ->where('status', 1)
                        ->count();
                    foreach ($monthsInLastSemester as $month) {
                        // Query to get the bookings for the room in the given month
                        $frequency = Booking::whereMonth('start', $month)
                            ->where('room_id', $roomId)
                            ->where('status', 1)
                            ->count();

                        $label = Carbon::create()->month($month)->format('F');
                        $frequencyMap[] = ['label' => $label, 'frequency' => $frequency, 'percentage' => round(($frequency / $totalBookings) * 100)];
                    }
                    break;
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
}
