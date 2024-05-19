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

use function PHPUnit\Framework\isNull;

class StatisticsController extends Controller
{
    public function roomHourOfDayOfWeekFrequency(Request $request)
    {
        $lecture_type = $request->input('lecture_type', ['lecture', 'teleconference', 'seminar', 'other']);
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

        $lecture_typeLength = count($lecture_type);
        if ($lecture_typeLength === 0) {
            $lecture_type =  ['lecture', 'teleconference', 'seminar', 'other']; // Default value
        }

        foreach ($roomIds as $roomId) {
            $label = '';
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
            foreach ($days as $day) {
                $totalBookings = Booking::select(
                    DB::raw('DAYOFWEEK(start) as day_of_week'),
                    DB::raw('COUNT(*) as frequency')
                )->where('room_id', $roomId)
                    //->whereRaw('DAYOFWEEK(start) = ?', [$day])
                    ->whereIn('semester_id', $semesterIds)
                    ->where('status', 1)
                    ->count();
                $frequency = Booking::select(
                    DB::raw('DAYOFWEEK(start) as day_of_week'),
                    DB::raw('HOUR(start) as hour_of_day'),
                    DB::raw('COUNT(*) as frequency')
                )
                    ->whereIn('semester_id', $semesterIds)
                    ->whereIn('lecture_type', $lecture_type)
                    ->where('room_id', $roomId)
                    ->where('status', 1)
                    ->whereRaw('DAYOFWEEK(start) = ?', [$day]) // Filter by the given day of the week
                    ->groupBy('day_of_week', 'hour_of_day')
                    ->orderBy('day_of_week')
                    ->orderBy('hour_of_day')
                    ->get();

                // Iterate over the frequencies and update the corresponding hour in the frequency map
                foreach ($frequency as $item) {
                    if ($item->frequency > $frequencyMax) {
                        $frequencyMax = $item->frequency;
                        $percentageMax = round(($item->frequency / $totalBookings) * 100);
                    }
                    $hourOfDay = $item->hour_of_day;
                    $frequencyMap[$hourOfDay - 8] += $item->frequency;
                    $percentageMap[$hourOfDay - 8] += round(($item->frequency / $totalBookings) * 100);
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
        $lecture_type = $request->input('lecture_type', ['lecture', 'teleconference', 'seminar', 'other']);

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
        $lecture_typeLength = count($lecture_type);
        if ($lecture_typeLength === 0) {
            $lecture_type =  ['lecture', 'teleconference', 'seminar', 'other']; // Default value
        }
        foreach ($roomIds as $roomId) {
            // Get the total number of bookings for the room in the current semester
            $totalBookings = Booking::where('room_id', $roomId)->whereIn('semester_id', $semesterIds)->count();

            // Query to get the bookings for the room in the current semester
            $frequency = Booking::select(DB::raw('DAYOFWEEK(start) as day_of_week'), DB::raw('count(*) as frequency'))
                ->whereIn('semester_id', $semesterIds)
                ->where('room_id', $roomId)
                ->whereIn('lecture_type', $lecture_type)
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
        $lecture_type = $request->input('lecture_type', ['lecture', 'teleconference', 'seminar', 'other']);

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
        $lecture_typeLength = count($lecture_type);
        if ($lecture_typeLength === 0) {
            $lecture_type =  ['lecture', 'teleconference', 'seminar', 'other']; // Default value
        }
        if ($roomIdsLength === 0) {
            $roomIds = [1]; // Default value
        }
        if ($monthsLength === 0) {
            $months = [$currentMonth]; // Default value
        }

        if ($semesterIdsLength === 0) {
            $semesterIds = [$currentSemesterId]; // Default value
        }


        foreach ($roomIds as $roomId) {
            $label = '';
            $totalBookings = Booking::where('room_id', $roomId)
                ->where('status', 1)
                ->whereIn('semester_id', $semesterIds)
                //->whereMonth('start', '=', $month)
                ->count();

            $frequency = Booking::select(DB::raw('DAY(start) as day_of_month'), DB::raw('count(*) as frequency'))
                ->whereIn('semester_id', $semesterIds)
                ->whereIn(DB::raw('MONTH(start)'), $months)
                ->whereIn('lecture_type', $lecture_type)
                ->where('room_id', $roomId)
                ->where('status', 1)
                ->groupBy(DB::raw('DAY(start)'))
                ->orderBy('day_of_month', 'asc')
                ->get();
            //dd($frequency);
            $frequencyMap = [];
            $percentageMap = [];
            $frequencyMax = 0;
            $percentageMax = 0;
            for ($i = 1; $i <= 31; $i++) {
                $frequencyMap[$i] = 0;
                $percentageMap[$i] = 0;
                $labels[$i - 1] = $i;
            }
            foreach ($frequency as $item) {
                $frequencyMap[$item->day_of_month + 1] = $item->frequency;
                $percentageMap[$item->day_of_month + 1] = round(($item->frequency / $totalBookings) * 100);
                if ($item->frequency > $frequencyMax) {
                    $frequencyMax = $item->frequency;
                    $percentageMax = round(($item->frequency / $totalBookings) * 100);
                }
            }
            foreach ($months as $month) {
                $label = $label . ' - ' . Carbon::create()->month($month)->format('F');
            }

            $fullFrequency = ['labels' => $labels, 'frequency' => array_values($frequencyMap), 'percentage' => array_values($percentageMap), 'totalBookings' => $totalBookings];
            //For multiple months if implemeted
            //$frequencyMonthMap[] = ['month' => $month, 'frequency' => $frequencyMap[$i]];

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
        $lecture_type = $request->input('lecture_type', ['lecture', 'teleconference', 'seminar', 'other']);

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

        $lecture_typeLength = count($lecture_type);
        if ($lecture_typeLength === 0) {
            $lecture_type =  ['lecture', 'teleconference', 'seminar', 'other']; // Default value
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
                    ->whereIn('lecture_type', $lecture_type)
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
        $lecture_type = $request->input('lecture_type', ['lecture', 'teleconference', 'seminar', 'other']);
        $roomIds = $request->input('roomIds', [1]);

        $days = $request->input('days', [1]);

        $lecture_typeLength = count($lecture_type);
        if ($lecture_typeLength === 0) {
            $lecture_type =  ['lecture', 'teleconference', 'seminar', 'other']; // Default value
        }

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
                    //->whereRaw('DAYOFWEEK(start) = ?', [$day])
                    ->whereIn('semester_id', $semesterIds)
                    ->count();
                $frequency = Booking::select(
                    DB::raw('TIMESTAMPDIFF(HOUR, start, end) as duration'),
                    DB::raw('COUNT(*) as frequency')
                )
                    ->whereRaw('DAYOFWEEK(start) = ?', [$day])
                    ->whereIn('lecture_type', $lecture_type)
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

        $lecture_type = $request->input('lecture_type', ['lecture', 'teleconference', 'seminar', 'other']);

        $lecture_typeLength = count($lecture_type);
        if ($lecture_typeLength === 0) {
            $lecture_type =  ['lecture', 'teleconference', 'seminar', 'other']; // Default value
        }

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
                    //->whereMonth('start', '=', $month)
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
                    ->whereIn('lecture_type', $lecture_type)
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
        $lecture_type = $request->input('lecture_type', ['lecture', 'teleconference', 'seminar', 'other']);
        $roomIds = $request->input('roomIds', [1]);
        $days = $request->input('days', [1]);
        $currentSemesterId = Semester::where('is_current', true)->first()->id;
        $semesterIds = $request->input('semesterIds', [$currentSemesterId]);

        $lecture_typeLength = count($lecture_type);
        if ($lecture_typeLength === 0) {
            $lecture_type =  ['lecture', 'teleconference', 'seminar', 'other']; // Default value
        }

        $daysLength = count($days);
        $semesterIdsLength = count($semesterIds);
        $roomIdsLength = count($roomIds);
        if ($roomIdsLength <= 0) {
            $roomIds = [1]; // Default value
        }
        if ($daysLength === 0) {
            $days = [1]; // Default value
        }

        if ($semesterIdsLength === 0) {
            $semesterIds = [$currentSemesterId]; // Default value
        }
        $capacity = 0;
        $data['percentageDataset'] = [];
        $data['accumulatedDataset'] = [];

        foreach ($roomIds as $roomId) {
            $capacity = 0;
            $totalBookedHours = 0;


            foreach ($days as $day) {
                $day = (int) $day;
                $totalOccurrences = 0;
                $semesters = Semester::whereIn('id', $semesterIds)->get();
                foreach ($semesters as $semester) {
                    $startDate = Carbon::parse($semester->start);
                    $endDate = Carbon::parse($semester->end);

                    // Calculate the number of occurrences of the specified day of the week
                    $occurrences = $startDate->diffInDaysFiltered(function (Carbon $date) use ($day) {
                        return $date->isDayOfWeek($day);
                    }, $endDate);

                    $totalOccurrences += $occurrences;
                }

                $capacity = $capacity + $totalOccurrences * 12;

                $bookings = Booking::where('room_id', $roomId)
                    ->where('status', 1)
                    ->whereNull('conflict_id')
                    ->whereIn('semester_id', $semesterIds)
                    ->whereRaw('DAYOFWEEK(start) = ?', [$day])
                    ->whereIn('lecture_type', $lecture_type)
                    ->get();
                // Calculate the total booked hours in the month
            }
            $roomCount = Room::whereIn('id', $roomIds)->count();
            $capacity = $capacity * $roomCount;

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

            $room = Room::where('id', $roomId)->first();
            $data['labels'][] = $room->name;
            $data['percentageDataset'][] = $percentage;
            $data['accumulatedDataset'][] = $totalBookedHours;
        }
        $data['accumulatedDataset'][] = $capacity;

        $data['labels'][] = 'Not Occupied';

        $sumOfPercentage = array_sum($data['percentageDataset']);

        // Calculate $notOccupied
        $notOccupied = 100 - $sumOfPercentage;

        $data['percentageDataset'][] = $notOccupied;
        $result[] = [
            'roomIds' => $roomIds,
            'data' => $data,
            'capacity' => $capacity,
            'options' => ['chartType' => 'doughnut'],
        ];
        return $result;
    }
    public function roomOccupancyByMonthPercentage(Request $request)
    {
        $roomIds = $request->input('roomIds', [1]);
        $currentMonth = Carbon::now()->month;
        $months = $request->input('months', [$currentMonth]);
        $currentSemesterId = Semester::where('is_current', true)->first()->id;
        $semesterIds = $request->input('semesterIds', [$currentSemesterId]);

        $lecture_type = $request->input('lecture_type', ['lecture', 'teleconference', 'seminar', 'other']);

        $monthsLength = count($months);

        $lecture_typeLength = count($lecture_type);
        if ($lecture_typeLength === 0) {
            $lecture_type =  ['lecture', 'teleconference', 'seminar', 'other']; // Default value
        }

        if ($monthsLength === 0) {
            $months = [$currentMonth]; // Default value
        }

        $roomIdsLength = count($roomIds);
        if ($roomIdsLength <= 0) {
            $roomIds = [1]; // Default value
        }

        $semesterIdsLength = count($semesterIds);
        if ($semesterIdsLength === 0) {
            $semesterIds = [$currentSemesterId]; // Default value
        }
        $capacity = 0;
        $data['percentageDataset'] = [];
        $data['accumulatedDataset'] = [];
        foreach ($roomIds as $roomId) {
            $capacity = 0;
            $totalBookedHours = 0;
            foreach ($months as $month) {

                // Get the first and last day of the specified month
                $firstDayOfMonth = Carbon::create(null, $month, 1);
                $lastDayOfMonth = date('Y-m-t', strtotime($firstDayOfMonth));

                // Calculate the total number of days in the month
                $totalDaysInMonth = (int) date('t', strtotime($firstDayOfMonth));

                // Retrieve all bookings for the specified room and month
                $bookings = Booking::where('room_id', $roomId)
                    ->where('status', 1)
                    ->whereNull('conflict_id')
                    ->whereIn('lecture_type', $lecture_type)
                    ->whereIn('semester_id', $semesterIds)
                    ->whereBetween('start', [$firstDayOfMonth, $lastDayOfMonth])
                    ->get();
            }

            // Calculate the total booked hours in the month
            foreach ($bookings as $booking) {
                $startTime = strtotime($booking->start);
                $endTime = strtotime($booking->end);
                $totalBookedHours += ($endTime - $startTime) / (60 * 60); // Convert seconds to hours
            }

            // Calculate the total available hours in the month (assuming 8 hours per day)
            $capacity = $totalDaysInMonth * 12;
            $roomCount = Room::whereIn('id', $roomIds)->count();
            $capacity = $capacity * $roomCount;

            // Calculate the occupancy percentage
            if ($capacity > 0) {
                $percentage = round(($totalBookedHours / $capacity) * 100);
            } else {
                $percentage = 0; // No available hours, so occupancy is 0%
            }

            $room = Room::where('id', $roomId)->first();
            $data['labels'][] = $room->name;
            $data['percentageDataset'][] = $percentage;
            $data['accumulatedDataset'][] = $totalBookedHours;
        }
        $data['accumulatedDataset'][] = $capacity;

        $data['labels'][] = 'Not Occupied';

        $sumOfPercentage = array_sum($data['percentageDataset']);

        // Calculate $notOccupied
        $notOccupied = 100 - $sumOfPercentage;

        $data['percentageDataset'][] = $notOccupied;
        $result[] = [
            'roomIds' => $roomIds,
            'data' => $data,
            'capacity' => $capacity,
            'options' => ['chartType' => 'doughnut'],
        ];


        return $result;
    }
    public function roomOccupancyBySemester(Request $request)
    {
        $lecture_type = $request->input('lecture_type', ['lecture', 'teleconference', 'seminar', 'other']);
        $roomIds = $request->input('roomIds', [1]);
        $currentSemesterId = Semester::where('is_current', true)->first()->id;
        $semesterIds = $request->input('semesterIds', [$currentSemesterId]);

        $lecture_typeLength = count($lecture_type);
        if ($lecture_typeLength === 0) {
            $lecture_type =  ['lecture', 'teleconference', 'seminar', 'other']; // Default value
        }

        $roomIdsLength = count($roomIds);
        if ($roomIdsLength <= 0) {
            $roomIds = [1]; // Default value
        }
        $semesterIdsLength = count($semesterIds);
        if ($semesterIdsLength === 0) {
            $semesterIds = [$currentSemesterId]; // Default value
        }
        $capacity = ($this->calculateSemesterCapacity($semesterIds)) * count($roomIds);
        $data['percentageDataset'] = [];
        $data['accumulatedDataset'] = [];
        foreach ($roomIds as $roomId) {
            // Retrieve all bookings for the specified room and month
            $bookings = Booking::where('room_id', $roomId)
                ->whereIn('semester_id', $semesterIds)
                ->whereIn('lecture_type', $lecture_type)
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
            $room = Room::where('id', $roomId)->first();
            $data['labels'][] = $room->name;
            $data['percentageDataset'][] = $percentage;
            $data['accumulatedDataset'][] = $totalBookedHours;
        }
        $data['accumulatedDataset'][] = $capacity;

        $data['labels'][] = 'Not Occupied';

        $sumOfPercentage = array_sum($data['percentageDataset']);

        // Calculate $notOccupied
        $notOccupied = 100 - $sumOfPercentage;

        $data['percentageDataset'][] = $notOccupied;
        $result[] = [
            'roomIds' => $roomIds,
            'data' => $data,
            'capacity' => $capacity,
            'options' => ['chartType' => 'doughnut'],
        ];
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
        $lecture_type = $request->input('lecture_type', ['lecture', 'teleconference', 'seminar', 'other']);
        $roomIds = $request->input('roomIds', [1]);
        $currentMonthStart = Carbon::now()->startOfMonth();
        $currentMonthEnd = Carbon::now()->endOfMonth();

        $lecture_typeLength = count($lecture_type);
        if ($lecture_typeLength === 0) {
            $lecture_type =  ['lecture', 'teleconference', 'seminar', 'other']; // Default value
        }

        $dateRange = $request->input('dateRange', [$currentMonthStart->format('Y-m-d'), $currentMonthEnd->format('Y-m-d')]);

        if ($dateRange == null) {
            $dateRange = [$currentMonthStart->format('Y-m-d'), $currentMonthEnd->format('Y-m-d')];
        }

        $startDate = isset($dateRange["start"]) ? Carbon::createFromFormat('n/j/Y', $dateRange["start"]) : $currentMonthStart;
        $endDate = isset($dateRange["end"]) ? Carbon::createFromFormat('n/j/Y', $dateRange["end"]) : $currentMonthEnd;

        $roomIdsLength = count($roomIds);
        if ($roomIdsLength <= 0) {
            $roomIds = [1]; // Default value
        }
        $totalDays = $startDate->diffInDaysFiltered(function (Carbon $date) {
            // Exclude weekends (Saturday and Sunday)
            return !$date->isWeekend();
        }, $endDate);

        $totalDays += 1;

        $capacity = ($totalDays * 12) * count($roomIds);

        $data['percentageDataset'] = [];
        $data['accumulatedDataset'] = [];
        foreach ($roomIds as $roomId) {
            $bookings = Booking::where('room_id', $roomId)
                ->whereBetween('start', [$startDate, $endDate])
                ->where('status', 1)
                ->whereNull('conflict_id')
                ->whereIn('lecture_type', $lecture_type)
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
            $room = Room::where('id', $roomId)->first();
            $data['labels'][] = $room->name;
            $data['percentageDataset'][] = $percentage;
            $data['accumulatedDataset'][] = $totalBookedHours;
        }
        $data['accumulatedDataset'][] = $capacity;

        $data['labels'][] = 'Not Occupied';

        $sumOfPercentage = array_sum($data['percentageDataset']);

        // Calculate $notOccupied
        $notOccupied = 100 - $sumOfPercentage;

        $data['percentageDataset'][] = $notOccupied;
        $result[] = [
            'roomIds' => $roomIds,
            'data' => $data,
            'capacity' => $capacity,
            'options' => ['chartType' => 'doughnut'],
        ];
        return $result;
    }
    public function roomDateRangeFrequency(Request $request)
    {
        /**
         * Calculate the frequency of bookings for each day of given date range for a list of rooms
         *
         * @param Request $request
         * @return array An array of room_id and frequency for each day of the month.
         *               The frequency is the number of bookings on that day of the month.
         *               The frequency is an array with day_of_month as the key and frequency as the value.
         */
        $lecture_type = $request->input('lecture_type', ['lecture', 'teleconference', 'seminar', 'other']);
        $lecture_typeLength = count($lecture_type);
        if ($lecture_typeLength === 0) {
            $lecture_type =  ['lecture', 'teleconference', 'seminar', 'other']; // Default value
        }
        // Get the room IDs from the request
        $roomIds = $request->input('roomIds', [1]);

        $currentMonthStart = Carbon::now()->startOfMonth();
        $currentMonthEnd = Carbon::now()->endOfMonth();

        $dateRange = $request->input('dateRange', [$currentMonthStart->format('Y-m-d'), $currentMonthEnd->format('Y-m-d')]);

        if ($dateRange == null) {
            $dateRange = [$currentMonthStart->format('Y-m-d'), $currentMonthEnd->format('Y-m-d')];
        }

        $startDate = isset($dateRange["start"]) ? Carbon::createFromFormat('n/j/Y', $dateRange["start"]) : $currentMonthStart;
        $endDate = isset($dateRange["end"]) ? Carbon::createFromFormat('n/j/Y', $dateRange["end"]) : $currentMonthEnd;

        $roomIdsLength = count($roomIds);
        if ($roomIdsLength === 0) {
            $roomIds = [1]; // Default value
        }

        $result = []; // The return value

        foreach ($roomIds as $roomId) {
            $label = '';

            // Get the total number of days in the date range
            $totalDays = $endDate->diffInDays($startDate) + 1;

            // Get the total number of bookings for the date range
            $totalBookings = Booking::where('room_id', $roomId)
                ->where('status', 1)
                //->whereBetween(DB::raw('DATE(start)'), [$startDate->toDateString(), $endDate->toDateString()])
                ->count();

            // Query to get the bookings for the room within the date range
            $frequency = Booking::select(DB::raw('DAY(start) as day_of_month'), DB::raw('count(*) as frequency'))
                ->whereBetween(DB::raw('DATE(start)'), [$startDate->toDateString(), $endDate->toDateString()])
                ->where('room_id', $roomId)
                ->where('conflict_id', null)
                ->whereIn('lecture_type', $lecture_type)
                ->where('status', 1)
                ->groupBy(DB::raw('DAY(start)'))
                ->orderBy('day_of_month', 'asc')
                ->get();

            $frequencyMap = array_fill(0, $totalDays, 0);
            $percentageMap = array_fill(0, $totalDays, 0);

            $frequencyMax = 0;
            $percentageMax = 0;

            foreach ($frequency as $item) {
                $distanceFromStart = $item->day_of_month - $startDate->copy()->day;

                $frequencyMap[$distanceFromStart] = $item->frequency;
                $percentageMap[$distanceFromStart] = round(($item->frequency / $totalBookings) * 100);

                if ($item->frequency > $frequencyMax) {
                    $frequencyMax = $item->frequency;
                    $percentageMax = round(($item->frequency / $totalBookings) * 100);
                }
            }

            // Re-adjust labels and frequency/percentage maps
            for ($i = 0; $i < $totalDays; $i++) {
                $labels[$i] = $startDate->copy()->addDays($i)->day;
            }

            $fullFrequency = ['labels' => $labels, 'frequency' => array_values($frequencyMap), 'percentage' => array_values($percentageMap), 'totalBookings' => $totalBookings];

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
    public function roomDateRangeDurationFrequency(Request $request)
    {
        $roomIds = $request->input('roomIds', [1]);
        $lecture_type = $request->input('lecture_type', ['lecture', 'teleconference', 'seminar', 'other']);
        $lecture_typeLength = count($lecture_type);
        if ($lecture_typeLength === 0) {
            $lecture_type =  ['lecture', 'teleconference', 'seminar', 'other']; // Default value
        }
        $currentMonthStart = Carbon::now()->startOfMonth();
        $currentMonthEnd = Carbon::now()->endOfMonth();

        $dateRange = $request->input('dateRange', [$currentMonthStart->format('Y-m-d'), $currentMonthEnd->format('Y-m-d')]);

        if ($dateRange == null) {
            $dateRange = [$currentMonthStart->format('Y-m-d'), $currentMonthEnd->format('Y-m-d')];
        }

        $startDate = isset($dateRange["start"]) ? Carbon::createFromFormat('n/j/Y', $dateRange["start"]) : $currentMonthStart;
        $endDate = isset($dateRange["end"]) ? Carbon::createFromFormat('n/j/Y', $dateRange["end"]) : $currentMonthEnd;

        $roomIdsLength = count($roomIds);

        if ($roomIdsLength === 0) {
            $roomIds = [1]; // Default value
        }

        $result = []; // The return value

        foreach ($roomIds as $roomId) {
            $label = '';
            $totalBookings = Booking::where('room_id', $roomId)
                ->where('status', 1)
                //->whereBetween('start', [$startDate, $endDate])
                ->count();
            $frequency = Booking::select(
                DB::raw('TIMESTAMPDIFF(HOUR, start, end) as duration'),
                DB::raw('COUNT(*) as frequency')
            )
                ->whereBetween('start', [$startDate, $endDate])
                ->where('room_id', $roomId)
                ->where('status', 1)
                ->whereIn('lecture_type', $lecture_type)
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
            $label = $label . ' - ' . $startDate->format('F') . ' to ' . $endDate->format('F');

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
    public function bookingTotals(Request $request)
    {

        $semester = Semester::where('is_current', true)->first();
        $totalSemester = Booking::where('status', 1)->where('semester_id', $semester->id)->count();
        $totalMonth = Booking::where('status', 1)->whereBetween('start', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->count();
        $totalWeek = Booking::where('status', 1)->whereBetween('start', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count();

        $result = [
            'totalSemester' => $totalSemester,
            'totalMonth' => $totalMonth,
            'totalWeek' => $totalWeek
        ];

        return $result;
    }

    public function approvalRate(Request $request)
    {

        $semester = Semester::where('is_current', true)->first();
        $allBookings = Booking::where('semester_id', $semester->id)->count();

        $approvedBookings = Booking::where('status', 1)->where('semester_id', $semester->id)->count();
        $canceledBookings = Booking::where('status', 2)->where('semester_id', $semester->id)->count();

        $approvalRate = round(($approvedBookings / $allBookings) * 100);
        $cancelationRate = round(($canceledBookings / $allBookings) * 100);

        $result = [
            'approvalRate' => $approvalRate,
            'approvedBookings' => $approvedBookings,
            'canceledBookings' => $canceledBookings,
            'cancelationRate' => $cancelationRate,
            'allBookings' => $allBookings,
            'divident' => 100 - $approvalRate
        ];

        return $result;
    }

    public function meanDuration(Request $request)
    {
        $semester = Semester::where('is_current', true)->first();
        $startDate = $semester->start;
        $endDate = $semester->end;
        $meanDuration = Booking::selectRaw('AVG(TIMESTAMPDIFF(HOUR, start, end)) as duration')
            ->whereBetween('start', [$startDate, $endDate])
            ->where('status', 1)
            ->first(); // Use first() to get a single result

        // Access the 'duration' attribute of the result
        return round($meanDuration->duration, 0);
    }
    public function bussiestRooms(Request $request)
    {
        $semester = Semester::where('is_current', true)->first();
        $startDate = $semester->start;
        $endDate = $semester->end;
        $bussiestRoomIds = Booking::selectRaw('room_id, COUNT(*) as frequency')
            ->whereBetween('start', [$startDate, $endDate])
            ->where('status', 1)
            ->groupBy('room_id')
            ->orderBy('frequency', 'desc')
            ->take(3)
            ->get();

        $busiestRooms = [];
        foreach ($bussiestRoomIds as $room) {
            $room->name = Room::where('id', $room->room_id)->first()->name;
            $busiestRooms[] = $room;
        }

        return $busiestRooms;
    }

    public function bussiestRoomThisWeek(Request $request)
    {

        $bussiestRoom = Booking::selectRaw('room_id, COUNT(*) as frequency')
            ->whereBetween('start', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->where('status', 1)
            ->groupBy('room_id')
            ->orderBy('frequency', 'desc')
            ->first();

        $bussiestRoom->name = Room::where('id', $bussiestRoom->room_id)->first()->name;

        return $bussiestRoom;
    }

    public function weekCapacityIndicator(Request $request)
    {
        $remainingHoursInWeek = Carbon::now()->endOfWeek()->diffInHours(Carbon::now());
        $remainingHoursInWeek -= (int)(($remainingHoursInWeek / 24) * 12);
        $remainingBookingsInWeek = Booking::where('status', 1)->whereBetween('start', [Carbon::now(), Carbon::now()->endOfWeek()]);
        foreach ($remainingBookingsInWeek as $booking) {
            $remainingHoursInWeek -= $booking->end->diffInHours($booking->start);
        }
        $now = Carbon::now();
        $endOfWeek = $now->endOfWeek();
        $hoursUntilEndOfWeek = $endOfWeek->diffInHours($now);

        // Ensure hoursUntilEndOfWeek is not zero to prevent division by zero
        if ($hoursUntilEndOfWeek > 0) {
            $capacityIndicator = round(($remainingHoursInWeek / $hoursUntilEndOfWeek) * 100);
        } else {
            // Handle the case where the difference is zero
            $capacityIndicator = 99; // or any default value you deem appropriate
        }

        $result = [
            'capacityIndicator' => $capacityIndicator,
            'divident' => 100 - $capacityIndicator,
            'remainingHoursInWeek' => $remainingHoursInWeek
        ];
        return $result;
    }

    public function monthCapacityIndicator(Request $request)
    {
        // Calculate the remaining hours in the month with 12-hour workdays
        $remainingHoursInMonth = Carbon::now()->endOfMonth()->diffInHours(Carbon::now());
        $remainingHoursInMonth -= (int)(($remainingHoursInMonth / 24) * 12);

        // Calculate the remaining bookings in the month
        $remainingBookingsInMonth = Booking::where('status', 1)
            ->whereBetween('start', [Carbon::now(), Carbon::now()->endOfMonth()])
            ->get();

        // Subtract the duration of each booking from the remaining hours in the month
        foreach ($remainingBookingsInMonth as $booking) {
            $remainingHoursInMonth -= $booking->end->diffInHours($booking->start);
        }

        // Calculate the capacity indicator for the month
        $capacityIndicator = round(($remainingHoursInMonth / Carbon::now()->endOfMonth()->diffInHours(Carbon::now())) * 100);

        // Prepare the result array
        $result = [
            'capacityIndicator' => $capacityIndicator,
            'divident' => 100 - $capacityIndicator,
            'remainingHoursInMonth' => $remainingHoursInMonth
        ];

        return $result;
    }

    public function generalStatistics(Request $request)
    {
        $totals = $this->bookingTotals($request);
        $meanDuration = $this->meanDuration($request);
        $bussiestRooms = $this->bussiestRooms($request);
        $bussiestRoomThisWeek = $this->bussiestRoomThisWeek($request);
        $weekCapacityIndicator = $this->weekCapacityIndicator($request);
        $monthCapacityIndicator = $this->monthCapacityIndicator($request);
        $approvalRate = $this->approvalRate($request);
        $charts = $this->getOccupancyCharts($request);
        $charts[][] = ['data' => ['percentageDataset' => [$approvalRate['approvalRate'], $approvalRate['divident']]]];
        $charts[][] = ['data' => ['percentageDataset' => [$weekCapacityIndicator['capacityIndicator'], $weekCapacityIndicator['divident']]]];
        $charts[][] = ['data' => ['percentageDataset' => [$monthCapacityIndicator['capacityIndicator'], $monthCapacityIndicator['divident']]]];
        $result = [
            'totals' => $totals,
            'meanDuration' => $meanDuration,
            'bussiestRoomsThisSemester' => $bussiestRooms,
            'bussiestRoomsThisWeek' => $bussiestRoomThisWeek,
            'weekCapacityIndicator' => $weekCapacityIndicator,
            'monthCapacityIndicator' => $monthCapacityIndicator,
            'approvalRate' => $approvalRate,
            'charts' => $charts
        ];
        return $result;
    }

    public function getOccupancyCharts(Request $request)
    {
        $roomIds = Room::all()->pluck('id')->toArray();
        $req = new Request(); // Create a new instance of Illuminate\Http\Request
        $req->merge([
            'roomIds' => $roomIds,
        ]);
        $weekOccupancy = $this->roomOccupancyByDateRange($req);
        $monthOccupancy = $this->roomOccupancyByMonthPercentage($req);
        $semesterOccupancy = $this->roomOccupancyBySemester($req);

        return [$weekOccupancy, $monthOccupancy, $semesterOccupancy];
    }
}
