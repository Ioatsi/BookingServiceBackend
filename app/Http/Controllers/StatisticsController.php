<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Semester;
use App\Models\Booking;
use App\Models\Room;
use Carbon\Carbon;

use Illuminate\Support\Facades\DB;
use PhpParser\Node\Expr\FuncCall;

class StatisticsController extends Controller
{
    public function roomHourOfDayOfWeekFrequency(Request $request)
    {
        $roomIds = $request->input('roomIds', [1]);
        $days = $request->input('days', [1]);
        $currentSemesterId = Semester::where('is_current', true)->first()->id;
        $semesterIds = $request->input('semesterIds', [$currentSemesterId]);
        $result = [];

        $roomIdsLength = count($roomIds);
        $daysLength = count($days);
        $semesterIdsLength = count($semesterIds);
        if ($roomIdsLength === 0) {
            $roomIds = [1]; // Default value
        }
        if ($daysLength === 0) {
            $days = [1]; // Default value
        }

        if ($semesterIdsLength === 0) {
            $semesterIds = [$currentSemesterId]; // Default value
        }

        foreach ($roomIds as $roomId) {
            $label = '';
            foreach ($days as $day) {
                $totalBookings = Booking::select(
                    DB::raw('DAYOFWEEK(start) as day_of_week'),
                    DB::raw('COUNT(*) as frequency')
                )->where('room_id', $roomId)
                    ->whereRaw('DAYOFWEEK(start) = ?', [$day])
                    ->whereIn('semester_id', $semesterIds)
                    ->where('status', 1)
                    ->count();
                $frequency = Booking::select(
                    DB::raw('DAYOFWEEK(start) as day_of_week'),
                    DB::raw('HOUR(start) as hour_of_day'),
                    DB::raw('COUNT(*) as frequency')
                )
                    ->whereIn('semester_id', $semesterIds)
                    ->where('room_id', $roomId)
                    ->where('status', 1)
                    ->whereRaw('DAYOFWEEK(start) = ?', [$day]) // Filter by the given day of the week
                    ->groupBy('day_of_week', 'hour_of_day')
                    ->orderBy('day_of_week')
                    ->orderBy('hour_of_day')
                    ->get();
                $frequencyMap = [];
                $percentageMap = [];
                $fullFrequency = [];
                // Initialize the frequency map with all hours between 8 and 20 and set the frequency to 0
                for ($i = 8; $i <= 20; $i++) {
                    $labels[$i - 8] = $i;
                    //$frequencyMap[] = ['label' => $i, 'datasetFrequency' => 0, 'datasetPercentage' => 0];
                    $frequencyMap[] = 0;
                    $percentageMap[] = 0;
                }
                $frequencyMax = 0;
                $percentageMax = 0;
                // Iterate over the frequencies and update the corresponding hour in the frequency map
                foreach ($frequency as $item) {
                    if ($item->frequency > $frequencyMax) {
                        $frequencyMax = $item->frequency;
                        $percentageMax = round(($item->frequency / $totalBookings) * 100);
                    }
                    $hourOfDay = $item->hour_of_day;
                    $frequencyMap[$hourOfDay - 8] = $item->frequency;
                    $percentageMap[$hourOfDay - 8] = round(($item->frequency / $totalBookings) * 100);
                    /* $frequencyMap[$hourOfDay-8] = [
                        //'label' => $hourOfDay, //Use this to check for data integrity
                        'datasetFrequency' => $item->frequency,
                        //'datasetPercentage' => round(($item->frequency / $totalBookings) * 100)
                    ]; */
                }
                $label = $label . ' - ' . Carbon::create()->startOfWeek()->addDays($day - 1)->format('l');
                $fullFrequency = ['labels' => $labels, 'frequency' => $frequencyMap, 'percentage' => $percentageMap, 'totalBookings' => $totalBookings];
            }

            $room = Room::where('id', $roomId)->first();
            $label = $room->name . ' ' . $label;
            $result[] = [
                'room_id' => $roomId,
                'data' => $fullFrequency,
                'options' => ['label' => $label . ' Booking Hours Frequency', 'frequencyMax' => round($frequencyMax * 1.1), 'percentageMax' => round($percentageMax * 1.1), 'chartType' => 'bar'],
            ];
        }

        return $result;
    }
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
        $roomIds = $request->input('roomIds', [1]);

        $currentSemesterId = Semester::where('is_current', true)->first()->id;
        $semesterIds = $request->input('semesterIds', [$currentSemesterId]);

        $result = []; // The return value

        $roomIdsLength = count($roomIds);
        $semesterIdsLength = count($semesterIds);
        if ($roomIdsLength === 0) {
            $roomIds = [1]; // Default value
        }

        if ($semesterIdsLength === 0) {
            $semesterIds = [$currentSemesterId]; // Default value
        }

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
            $percentageMap = [];
            $frequencyMax = 0;
            $percentageMax = 0;
            foreach ($frequency as $item) {
                $labels[] = Carbon::create()->startOfWeek()->addDays($item->day_of_week - 1)->format('l');
                $frequencyMap[] = $item->frequency;
                $percentageMap[] = round(($item->frequency / $totalBookings) * 100);
                if ($item->frequency > $frequencyMax) {
                    $frequencyMax = $item->frequency;
                    $percentageMax = round(($item->frequency / $totalBookings) * 100);
                }
            }
            // Fill in missing days with a frequency of 0
            for ($i = 0; $i <= 6; $i++) {
                if (!isset($frequencyMap[$i])) {
                    $frequencyMap[$i] = 0;
                    $percentageMap[$i] = 0;
                }
            }

            $room = Room::where('id', $roomId)->first();
            $label = $room->name . ' Booking Frequency by Day of Week';
            $fullFrequency = ['labels' => $labels, 'frequency' => $frequencyMap, 'percentage' => $percentageMap, 'totalBookings' => $totalBookings];

            $result[] = [
                'room_id' => $roomId,
                'data' => $fullFrequency,
                'options' => ['label' => $label, 'frequencyMax' => round($frequencyMax * 1.1), 'percentageMax' => round($percentageMax * 1.1), 'chartType' => 'bar'],
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
        $roomIds = $request->input('roomIds', [1]);

        $currentMonth = Carbon::now()->month;
        // Get the month and year from the request
        $months = $request->input('months', [$currentMonth]);

        $result = []; // The return value

        $currentSemesterId = Semester::where('is_current', true)->first()->id;
        $semesterIds = $request->input('semesterIds', [$currentSemesterId]);

        $roomIdsLength = count($roomIds);
        $semesterIdsLength = count($semesterIds);
        $monthsLength = count($months);

        if ($roomIdsLength === 0) {
            $roomIds = [1]; // Default value
        }
        if ($monthsLength === 0) {
            $months = [1]; // Default value
        }

        if ($semesterIdsLength === 0) {
            $semesterIds = [$currentSemesterId]; // Default value
        }

        foreach ($roomIds as $roomId) {
            $label = '';
            foreach ($months as $month) {
                // Get the total number of days in the given month
                $totalDays = Carbon::createFromDate($month)->daysInMonth;
                $totalBookings = Booking::where('room_id', $roomId)
                    ->where('status', 1)
                    ->whereIn('semester_id', $semesterIds)
                    ->whereMonth('start', '=', $month)
                    ->count();

                // Query to get the bookings for the room on the last day of the last elapsed month
                $frequency = Booking::select(DB::raw('DAY(start) as day_of_month'), DB::raw('count(*) as frequency'))
                    ->whereIn('semester_id', $semesterIds)
                    ->whereMonth('start', '=', $month)
                    ->where('room_id', $roomId)
                    ->where('status', 1)
                    ->groupBy(DB::raw('DAY(start)'))
                    ->orderBy('day_of_month', 'asc')
                    ->get();


                $frequencyMap = [];
                $percentageMap = [];
                $frequencyMax = 0;
                $percentageMax = 0;
                for ($i = 1; $i <= $totalDays; $i++) {
                    $frequencyMap[$i] = 0;
                    $percentageMap[$i] = 0;
                    $labels[$i - 1] = $i;
                }
                foreach ($frequency as $item) {
                    $frequencyMap[$item->day_of_month - 1] = $item->frequency;
                    $percentageMap[$item->day_of_month - 1] = round(($item->frequency / $totalBookings) * 100);
                    if ($item->frequency > $frequencyMax) {
                        $frequencyMax = $item->frequency;
                        $percentageMax = round(($item->frequency / $totalBookings) * 100);
                    }
                }
                $label = $label . ' - ' . Carbon::create()->month($month)->format('F');

                $fullFrequency = ['labels' => $labels, 'frequency' => array_values($frequencyMap), 'percentage' => array_values($percentageMap), 'totalBookings' => $totalBookings];
                //For multiple months if implemeted
                //$frequencyMonthMap[] = ['month' => $month, 'frequency' => $frequencyMap[$i]];
            }
            $room = Room::where('id', $roomId)->first();
            $label = $room->name . ' ' . $label;
            $result[] = [
                'room_id' => $roomId,
                'data' => $fullFrequency,
                'options' => ['label' => $label . ' Booking Day of Month Frequency', 'frequencyMax' => round($frequencyMax * 1.6), 'percentageMax' => round($percentageMax * 1.6), 'chartType' => 'bar']
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
        $roomIds = $request->input('roomIds', [1]);

        $currentSemesterId = Semester::where('is_current', true)->first()->id;
        $semesterIds = $request->input('semesterIds', [$currentSemesterId]);

        $roomIdsLength = count($roomIds);
        $semesterIdsLength = count($semesterIds);
        if ($roomIdsLength === 0) {
            $roomIds = [1]; // Default value
        }
        if ($semesterIdsLength === 0) {
            $semesterIds = [$currentSemesterId]; // Default value
        }
        $result = []; // The return value
        $semesters = Semester::whereIn('id', $semesterIds)->get();
        $months = [];
        foreach ($semesters as $semester) {
            $months = array_merge($months, $this->getMonthsInRange($semester->start, $semester->end));
        }

        $months = array_unique($months);
        sort($months);
        foreach ($roomIds as $roomId) {
            $frequencyMap = [];
            // Get the total number of bookings for the room in the given semester
            $totalBookings = Booking::where('room_id', $roomId)
                ->whereIn('semester_id', $semesterIds)
                ->where('status', 1)
                ->count();

            $frequencyMax = 0;
            $percentageMax = 0;
            foreach ($months as $month) {
                // Query to get the bookings for the room in the given month
                $frequency = Booking::whereMonth('start', $month)
                    ->where('room_id', $roomId)
                    ->where('status', 1)
                    ->count();

                $frequencyMap[] = $frequency;
                $percentageMap[] = round(($frequency / $totalBookings) * 100);
                if ($frequency > $frequencyMax) {
                    $frequencyMax = $frequency;
                    $percentageMax = round(($frequency / $totalBookings) * 100);
                }
                $labels[] = Carbon::create()->month($month)->format('F');
            }

            $fullFrequency = ['labels' => $labels, 'frequency' => $frequencyMap, 'percentage' => $percentageMap, 'totalBookings' => $totalBookings];
            $room = Room::where('id', $roomId)->first();
            $label = $room->name . ' Month Of Semester Frequency';
            $result[] = [
                'room_id' => $roomId,
                'data' => $fullFrequency,
                'options' => ['label' => $label, 'frequencyMax' => round($frequencyMax * 1.1), 'percentageMax' => round($percentageMax * 1.1), 'chartType' => 'bar'],
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
        $roomIds = $request->input('roomIds', [1]);

        $days = $request->input('days', [1]);

        // Get the sample size from the request
        $currentSemesterId = Semester::where('is_current', true)->first()->id;
        $semesterIds = $request->input('semesterIds', [$currentSemesterId]);

        $roomIdsLength = count($roomIds);
        $daysLength = count($days);
        $semesterIdsLength = count($semesterIds);
        if ($roomIdsLength === 0) {
            $roomIds = [1]; // Default value
        }
        if ($daysLength === 0) {
            $days = [1]; // Default value
        }

        if ($semesterIdsLength === 0) {
            $semesterIds = [$currentSemesterId]; // Default value
        }
        foreach ($roomIds as $roomId) {
            $frequencyMap = [];
            $label = '';
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

                $frequencyMap = [];
                $percentageMap = [];
                $labels = [];
                $frequencyMax = 0;
                $percentageMax = 0;
                for ($hour = 0; $hour < 12; $hour++) {
                    $labels[] = $hour;
                    $frequencyMap[] = 0;
                    $percentageMap[] = 0;
                }
                foreach ($frequency as $value) {
                    $frequencyMap[$value->duration] = $value->frequency;
                    $percentageMap[$value->duration] = round(($value->frequency / $totalBookings) * 100);
                    if ($value->frequency > $frequencyMax) {
                        $frequencyMax = $value->frequency;
                        $percentageMax = round(($value->frequency / $totalBookings) * 100);
                    }
                }
                $label = $label . ' - ' . Carbon::create()->startOfWeek()->addDays($day - 1)->format('l');

                $fullFrequency = ['labels' => $labels, 'frequency' => $frequencyMap, 'percentage' => $percentageMap, 'totalBookings' => $totalBookings];
            }
            $room = Room::where('id', $roomId)->first();
            $label = $room->name . ' ' . $label;
            $result[] = [
                'room_id' => $roomId,
                'data' => $fullFrequency,
                'options' => ['label' => $label . ' Day of Week Duration Frequency', 'frequencyMax' => round($frequencyMax * 1.1), 'percentageMax' => round($percentageMax * 1.1), 'chartType' => 'line'],
            ];
        }

        return $result;
    }
    public function roomMonthDurationFrequency(Request $request)
    {
        $roomIds = $request->input('roomIds', [1]);

        $currentMonth = Carbon::now()->month;
        $months = $request->input('months', [$currentMonth]);

        $currentSemesterId = Semester::where('is_current', true)->first()->id;
        $semesterIds = $request->input('semester', [$currentSemesterId]);

        $roomIdsLength = count($roomIds);
        $semesterIdsLength = count($semesterIds);
        $monthsLength = count($months);
        if ($roomIdsLength === 0) {
            $roomIds = [1]; // Default value
        }
        if ($semesterIdsLength === 0) {
            $semesterIds = [$currentSemesterId]; // Default value
        }
        if ($monthsLength === 0) {
            $months = [$currentMonth]; // Default value
        }
        foreach ($roomIds as $roomId) {
            $label = '';
            foreach ($months as $month) {
                $totalBookings = Booking::where('room_id', $roomId)
                    ->where('status', 1)
                    ->whereMonth('start', '=', $month)
                    ->whereIn('semester_id', $semesterIds)
                    ->count();
                $frequency = Booking::select(
                    DB::raw('TIMESTAMPDIFF(HOUR, start, end) as duration'),
                    DB::raw('COUNT(*) as frequency')
                )
                    ->whereMonth('start', '=', $month)
                    ->where('room_id', $roomId)
                    ->whereIn('semester_id', $semesterIds)
                    ->where('status', 1)
                    ->groupBy('duration')
                    ->orderBy('duration')
                    ->get();
                $frequencyMap = [];
                $percentageMap = [];
                $labels = [];
                $frequencyMax = 0;
                $percentageMax = 0;
                for ($hour = 0; $hour < 12; $hour++) {
                    $labels[] = $hour;
                    $frequencyMap[] = 0;
                    $percentageMap[] = 0;
                }
                foreach ($frequency as $value) {
                    $frequencyMap[$value->duration] = $value->frequency;
                    $percentageMap[$value->duration] = round(($value->frequency / $totalBookings) * 100);
                    if ($value->frequency > $frequencyMax) {
                        $frequencyMax = $value->frequency;
                        $percentageMax = round(($value->frequency / $totalBookings) * 100);
                    }
                }
                $fullFrequency = ['labels' => $labels, 'frequency' => $frequencyMap, 'percentage' => $percentageMap, 'totalBookings' => $totalBookings];
                $label = $label . ' - ' . Carbon::create()->month($month)->format('F');
            }

            $room = Room::where('id', $roomId)->first();
            $label = $room->name . ' ' . $label;
            $result[] = [
                'room_id' => $roomId,
                'data' => $fullFrequency,
                'options' => ['label' => $label . ' Booking Month Duration Frequency', 'frequencyMax' => round($frequencyMax * 1.6), 'percentageMax' => round($percentageMax * 1.6), 'chartType' => 'line'],
            ];
        }

        return $result;
    }
    public function roomOccupancyByDayOfWeekPercentage(Request $request)
    {
        $roomIds = $request->input('roomIds', [1]);
        $days = $request->input('days', [1]);
        $currentSemesterId = Semester::where('is_current', true)->first()->id;
        $semesterIds = $request->input('semesterIds', [$currentSemesterId]);
        $semesters = Semester::whereIn('id', $semesterIds)->get();
        foreach ($roomIds as $roomId) {
            foreach ($days as $day) {
                $totalOccurrences = 0;
                foreach ($semesters as $semester) {
                    $startDate = Carbon::parse($semester->start);
                    $endDate = Carbon::parse($semester->end);

                    // Calculate the number of occurrences of the specified day of the week
                    $occurrences = $startDate->diffInDaysFiltered(function (Carbon $date) use ($day) {
                        return $date->isDayOfWeek($day);
                    }, $endDate);

                    $totalOccurrences += $occurrences;
                }

                $capacity = $totalOccurrences * 12;

                $bookings = Booking::where('room_id', $roomId)
                    ->where('status', 1)
                    ->whereNull('conflict_id')
                    ->whereIn('semester_id', $semesterIds)
                    ->whereRaw('DAYOFWEEK(start) = ?', [$day])
                    ->get();
                // Calculate the total booked hours in the month
                $totalBookedHours = 0;
                foreach ($bookings as $booking) {
                    $startTime = strtotime($booking->start);
                    $endTime = strtotime($booking->end);
                    $totalBookedHours += ($endTime - $startTime) / (60 * 60); // Convert seconds to hours
                }
                if ($capacity > 0) {
                    $percentage = round(($totalBookedHours / $capacity) * 100);
                } else {
                    $percentage = 0; // No available hours, so occupancy is 0%
                }
                $notOccupied = 100 - $percentage;
                $data = [
                    'labels' => ['Occupied', 'Not Occupied'],
                    'percentageDataset' => [$percentage, $notOccupied],
                    'accumulatedDataset' => [$totalBookedHours, $capacity - $totalBookedHours],
                ];
                $result[] = [
                    'room_id' => $roomId,
                    'day' => $day,
                    'data' => $data,
                    'total' => $totalBookedHours,
                    'capacity' => $capacity,
                    'occupied' => $percentage,
                    'notOccupied' => $notOccupied,
                    'options' => ['chartType' => 'doughnut'],
                ];
            }
        }
        return $result;
    }
    public function roomOccupancyByYearMonthPercentage(Request $request)
    {
        $roomIds = $request->input('roomIds', [1]);
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $year = $request->input('year', $currentYear);
        $month = $request->input('month', $currentMonth);
        foreach ($roomIds as $roomId) {
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
            $capacity = $totalDaysInMonth * 12;

            // Calculate the occupancy percentage
            if ($capacity > 0) {
                $percentage = round(($totalBookedHours / $capacity) * 100);
            } else {
                $percentage = 0; // No available hours, so occupancy is 0%
            }
            $notOccupied = 100 - $percentage;
            $data = [
                'labels' => ['Occupied', 'Not Occupied'],
                'percentageDataset' => [$percentage, $notOccupied],
                'accumulatedDataset' => [$totalBookedHours, $capacity - $totalBookedHours],
            ];
            $result[] = [
                'room_id' => $roomId,
                'data' => $data,
                'total' => $totalBookedHours,
                'capacity' => $capacity,
                'occupied' => $percentage,
                'notOccupied' => $notOccupied,
                'options' => ['chartType' => 'doughnut'],
            ];
        }

        return $result;
    }
    public function roomOccupancyBySemester(Request $request)
    {
        $roomIds = $request->input('roomIds', [1]);
        $currentSemesterId = Semester::where('is_current', true)->first()->id;
        $semesterIds = $request->input('semesterIds', [$currentSemesterId]);
        $capacity = $this->calculateSemesterCapacity($semesterIds);
        foreach ($roomIds as $roomId) {
            // Retrieve all bookings for the specified room and month
            $bookings = Booking::where('room_id', $roomId)
                ->whereIn('semester_id', $semesterIds)
                ->where('status', 1)
                ->whereNull('conflict_id')
                ->get();

            // Calculate the total booked hours in the month
            $totalBookedHours = 0;
            foreach ($bookings as $booking) {
                $startTime = strtotime($booking->start);
                $endTime = strtotime($booking->end);
                $totalBookedHours += ($endTime - $startTime) / (60 * 60); // Convert seconds to hours
            }

            // Calculate the occupancy percentage
            if ($capacity > 0) {
                $percentage = round(($totalBookedHours / $capacity) * 100);
            } else {
                $percentage = 0; // No available hours, so occupancy is 0%
            }
            $notOccupied = 100 - $percentage;
            $data = [
                'labels' => ['Occupied', 'Not Occupied'],
                'percentageDataset' => [$percentage, $notOccupied],
                'accumulatedDataset' => [$totalBookedHours, $capacity - $totalBookedHours],
            ];
            $result[] = [
                'room_id' => $roomId,
                'data' => $data,
                'total' => $totalBookedHours,
                'capacity' => $capacity,
                'occupied' => $percentage,
                'notOccupied' => $notOccupied,
                'options' => ['chartType' => 'doughnut'],
            ];
        }

        return $result;
    }
    private function calculateSemesterCapacity($semesterIds)
    {
        $totalCapacity = 0;
        foreach ($semesterIds as $semesterId) {
            $semester = Semester::findOrFail($semesterId);
            $totalCapacity += $semester->calculateCapacityInHours();
        }
        return $totalCapacity;
    }
    public function roomOccupancyByDateRange(Request $request)
    {
        $roomIds = $request->input('roomIds', [1]);
        $currentMonthStart = Carbon::now()->startOfMonth();
        $currentMonthEnd = Carbon::now()->endOfMonth();
        $dateRange = $request->input('dateRange', [$currentMonthStart->format('Y-m-d'), $currentMonthEnd->format('Y-m-d')]);

        $startDate = isset($dateRange["start"]) ? Carbon::createFromFormat('Y-m-d', $dateRange["start"]) : $currentMonthStart;
        $endDate = isset($dateRange["end"]) ? Carbon::createFromFormat('Y-m-d', $dateRange["end"]) : $currentMonthEnd;
        foreach ($roomIds as $roomId) {



            $totalDays = $startDate->diffInDaysFiltered(function (Carbon $date) {
                // Exclude weekends (Saturday and Sunday)
                return !$date->isWeekend();
            }, $endDate);

            $capacity = $totalDays * 12;
            $bookings = Booking::where('room_id', $roomId)
                ->whereBetween('start', [$startDate, $endDate])
                ->where('status', 1)
                ->whereNull('conflict_id')
                ->get();
            $totalBookedHours = 0;
            foreach ($bookings as $booking) {
                $startTime = strtotime($booking->start);
                $endTime = strtotime($booking->end);
                $totalBookedHours += ($endTime - $startTime) / (60 * 60); // Convert seconds to hours
            }
            // Calculate the occupancy percentage
            if ($capacity > 0) {
                $percentage = round(($totalBookedHours / $capacity) * 100);
            } else {
                $percentage = 0; // No available hours, so occupancy is 0%
            }

            $notOccupied = 100 - $percentage;
            $data = [
                'labels' => ['Occupied', 'Not Occupied'],
                'percentageDataset' => [$percentage, $notOccupied],
                'accumulatedDataset' => [$totalBookedHours, $capacity - $totalBookedHours],
            ];
            $result[] = [
                'room_id' => $roomId,
                'data' => $data,
                'total' => $totalBookedHours,
                'capacity' => $capacity,
                'occupied' => $percentage,
                'notOccupied' => $notOccupied,
                'options' => ['chartType' => 'doughnut'],
            ];
        }
        return $result;
    }
}
