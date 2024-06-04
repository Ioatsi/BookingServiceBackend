<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Models\Booking;
use App\Models\Day;
use App\Models\Recurring;
use App\Models\Room;
use App\Models\Semester;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;


class BookingController extends Controller
{
    public function index(Request $request)
    {
        //Get bookings for morderation

        // Get the current user ID from the authenticated user
        $currentUserId = Auth::id();
        $userRoles = Auth::user()->roles;



        $sortBy = $request->input('sortBy', 'created_at');
        $sortOrder = $request->input('sortOrder', 'desc');

        $lecture_type = $request->input('lecture_type', ['lecture', 'teleconference', 'seminar', 'other']);

        $page = $request->input('page', 1);
        $status = $request->input('status', [0, 1]);
        $months = $request->input('start');
        $days = $request->input('days');
        $type = $request->input('type', ['normal', 'recurring']);
        $publicity = $request->input('publicity', [0, 1]);

        // Define the number of items per page
        $perPage = $request->input('perPage', 1); // You can adjust this number as needed
        $allRoomIds = Room::join('moderator_room', 'rooms.id', '=', 'moderator_room.room_id')
            ->where('moderator_room.user_id', $currentUserId)
            ->where('status', 1)
            ->pluck('rooms.id')
            ->toArray();

        foreach ($userRoles as $role) {
            if ($role->name == 'admin') {
                $allRoomIds = Room::join('moderator_room', 'rooms.id', '=', 'moderator_room.room_id')
                    ->pluck('rooms.id')
                    ->toArray();
                    
            }
        }
        $roomIds = $request->input('room_id');
        if ($request->input('room_id') == null) {
            $roomIds = $allRoomIds;
        }
        $semester = Semester::where('is_current', true)->first();

        $query = Booking::join('rooms', 'bookings.room_id', '=', 'rooms.id')
            ->where('bookings.semester_id', $semester->id)
            ->whereIn('bookings.status', $status)
            ->whereIn('bookings.type', $type)
            ->whereIn('bookings.lecture_type', $lecture_type)
            ->whereIn('bookings.publicity', $publicity)
            ->whereNull('bookings.conflict_id')
            ->whereIn('bookings.room_id', $roomIds);

        // If months are provided, filter by them
        if (!empty($months)) {
            $query->whereIn(DB::raw('MONTH(bookings.start)'), $months);
        }

        // If days of the week are provided, filter by them
        if (!empty($days)) {
            // Adjust the day values to match the MySQL convention
            $adjustedDays = array_map(function ($day) {
                return $day + 1; // Increment by 1 to match MySQL convention
            }, $days);

            $query->whereIn(DB::raw('DAYOFWEEK(bookings.start)'), $adjustedDays);
        }


        $bookings = $query->orderBy($sortBy, $sortOrder)
            ->select('bookings.*', 'rooms.name as room_name', 'rooms.color as color', 'rooms.id as room')
            ->paginate($perPage, ['*'], 'page', $page);

        $booking_groups = new Collection();
        $bookings->each(function ($booking) use ($booking_groups) {
            $rooms = Room::where('id', $booking->room_id)->where('status', 1)->get();
            $booking_groups->push((object) [
                'id' => $booking->id,
                'title' => $booking->title,
                'start' => $booking->start,
                'end' => $booking->end,
                'info' => $booking->info,
                'status' => $booking->status,
                'type' => $booking->type,
                'publicity' => $booking->publicity,
                'room_name' => $booking->room_name,
                'lecture_type' => $booking->lecture_type,
                'expected_attendance' => $booking->expected_attendance,
                'url' => $booking->url,
                'room_id' => $booking->room,
                'rooms' => $rooms
            ]);
        });
        if ($request->input('ical') == true) {
            return $this->generateICal($booking_groups);
        }
        return response()->json([
            'bookings' => $booking_groups,
            'total' => $bookings->total(),
        ]);
    }
    public function getRecurring(Request $request)
    {
        $sortBy = $request->input('sortBy', 'created_at');
        $sortOrder = $request->input('sortOrder', 'desc');

        $lecture_type = $request->input('lecture_type', ['lecture', 'teleconference', 'seminar', 'other']);

        $page = $request->input('page', 1);
        $status = $request->input('status', [0, 1]);
        $publicity = $request->input('publicity', [0, 1]);
        $dayInputs = $request->input('days', [1, 2, 3, 4, 5, 6, 7]);

        $semester = Semester::where('is_current', true)->first();
        $perPage = $request->input('perPage', 1); // You can adjust this number as needed
        $allRoomIds = Room::join('moderator_room', 'rooms.id', '=', 'moderator_room.room_id')
            ->where('moderator_room.user_id', $request->user_id)
            ->where('status', 1)
            ->pluck('rooms.id')
            ->toArray();
        $roomIds = $request->input('room_id');
        if ($request->input('room_id') == null) {
            $roomIds = $allRoomIds;
        }
        $days = Day::whereIn('room_id', $roomIds)
            ->whereIn('name', $dayInputs)
            ->where('status', '!=', 2)
            ->get();
        $recurringIds = new Collection();
        foreach ($days as $day) {
            $recurringIds->push($day->recurring_id);
        }
        $recurrings = Recurring::where('semester_id', $semester->id)
            ->whereIn('id', $recurringIds)
            ->whereIn('status', $status)
            ->whereIn('publicity', $publicity)
            ->whereIn('lecture_type', $lecture_type)
            ->where('conflict_id', null)
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage, ['*'], 'page', $page);

        $recurring_groups = new Collection();
        $recurrings->each(function ($recurring) use ($recurring_groups) {
            $days = Day::where('recurring_id', $recurring->id)
                ->where('status', '!=', 2)
                ->get();
            $rooms = Room::whereIn('id', $days->pluck('room_id'))->where('status', 1)->get();
            $recurring_groups->push((object) [
                'id' => $recurring->id,
                'title' => $recurring->title,
                //'bookings' => $recurring,
                'info' => $recurring->info,
                'status' => $recurring->status,
                'publicity' => $recurring->publicity,
                'lecture_type' => $recurring->lecture_type,
                'expected_attendance' => $recurring->expected_attendance,
                'url' => $recurring->url,
                'type' => 'recurringGroup',
                'days' => $days,
                'rooms' => $rooms,
            ]);
        });

        if ($request->input('ical') == true) {
            return $this->generateICal($recurring_groups, true);
        }

        return response()->json([
            'recurrings' => $recurring_groups,
            'total' => $recurrings->total(),
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'group_id' => 'nullable|exists:groups,id',
            'is_recurring' => 'nullable',
            'recurring_id' => 'nullable|exists:recurrings,id',
            'conflict_id' => 'nullable',
            'booker_id' => 'required|exists:users,id',
            'title' => 'required',
            'info' => 'nullable',
            'start' => 'required|date',
            'end' => 'required|date',
            'participants' => 'required',
            'type' => 'required',
            'publicity' => 'required',
            'days' => 'nullable',
            'room_id' => 'nullable',
            'url' => 'nullable',
            'lecture_type' => 'required',
            'expected_attendance' => 'nullable',
        ]);
        /* if (!Gate::forUser($request->input('booker_id'))->allows('create-booking')) {
            abort(403);
        } */
        if ($request->input('is_recurring')) {

            $this->createRecurringBookingGroup($validatedData);
            return response()->json(['message' => 'Recurring booking created successfully.'], 201);
        }
        $booking = Booking::create($validatedData);
        return response()->json(['message' => 'Booking created successfully.', 'booking' => $booking], 201);
    }

    public function createRecurringBookingGroup($validatedData)
    {

        $semester = Semester::where('is_current', true)->first();
        $recurring = new Recurring();
        $recurring->title = $validatedData['title'];
        $recurring->info = $validatedData['info'];
        $recurring->participants = $validatedData['participants'];
        $recurring->booker_id = $validatedData['booker_id'];
        $recurring->status = 0;
        $recurring->publicity = $validatedData['publicity'];
        $recurring->url = $validatedData['url'];
        $recurring->lecture_type = $validatedData['lecture_type'];
        $recurring->expected_attendance = $validatedData['expected_attendance'];
        $recurring->semester_id = $semester->id;
        $recurring->save();
        $days = $validatedData['days'];
        foreach ($days as $day) {
            $day['recurring_id'] = $recurring->id;
            $day['status'] = 0;
            Day::create($day);
        }

        $conflicting = Recurring::conflicts($recurring);
    }

    public function getUserBookings(Request $request)
    {
        $semester = Semester::where('is_current', true)->first();
        $query = Booking::join('rooms', 'bookings.room_id', '=', 'rooms.id')
            ->where('semester_id', $semester->id)
            ->where('booker_id', $request->input('booker_id', ''))
            ->orderBy('created_at', 'desc');

        $perPage = $request->input('perPage', 1); // You can adjust this number as needed
        $page = $request->input('page', 1);

        $bookings = $query->select('bookings.*', 'rooms.name as room_name', 'rooms.color as color', 'rooms.id as room')
            ->paginate($perPage, ['*'], 'page', $page);

        $booking_groups = new Collection();
        $bookings->each(function ($booking) use ($booking_groups) {
            $rooms = Room::where('id', $booking->room_id)->where('status', 1)->get();
            $booking_groups->push((object) [
                'id' => $booking->id,
                'title' => $booking->title,
                'start' => $booking->start,
                'end' => $booking->end,
                'info' => $booking->info,
                'status' => $booking->status,
                'type' => $booking->type,
                'publicity' => $booking->publicity,
                'room_name' => $booking->room_name,
                'url' => $booking->url,
                'lecture_type' => $booking->lecture_type,
                'expected_attendance' => $booking->expected_attendance,
                'room_id' => $booking->room,
                'rooms' => $rooms
            ]);
        });
        if ($request->input('ical') == true) {
            return $this->generateICal($booking_groups);
        }
        return response()->json([
            'bookings' => $booking_groups,
            'total' => $bookings->total(),
        ]);
    }

    public function getActiveBookings(Request $request)
    {
        $date = $request->input('date', now()->toDateString());
        $dateCarbon = Carbon::parse($date);

        $lecture_type = $request->input('lecture_type', ['lecture', 'teleconference', 'seminar', 'other']);

        // Get the start and end dates of the month
        $startOfMonth = $dateCarbon->copy()->startOfMonth();
        $endOfMonth = $dateCarbon->copy()->endOfMonth();

        // Get the start and end dates of the previous month
        $startOfPreviousMonth = $dateCarbon->copy()->subMonth()->startOfMonth();
        $endOfPreviousMonth = $dateCarbon->copy()->subMonth()->endOfMonth();

        // Get the start and end dates of the next month
        $startOfNextMonth = $dateCarbon->copy()->addMonth()->startOfMonth();
        $endOfNextMonth = $dateCarbon->copy()->addMonth()->endOfMonth();

        $semester = Semester::where('is_current', true)->first();

        $query = Booking::join('rooms', 'bookings.room_id', '=', 'rooms.id')
            ->where('bookings.semester_id', $semester->id)
            ->where('bookings.status', 1)
            ->where('bookings.publicity', 1)
            ->where('rooms.status', 1)
            ->whereIn('bookings.lecture_type', $lecture_type)
            ->orderBy('bookings.start', 'asc')
            ->select('bookings.*', 'rooms.name as room_name', 'rooms.color as color', 'rooms.building_id as building_id')
            ->where(function ($query) use ($startOfMonth, $endOfMonth, $startOfPreviousMonth, $endOfPreviousMonth, $startOfNextMonth, $endOfNextMonth) {
                $query->whereBetween('start', [$startOfMonth, $endOfMonth])
                    ->orWhereBetween('start', [$startOfPreviousMonth, $endOfPreviousMonth])
                    ->orWhereBetween('start', [$startOfNextMonth, $endOfNextMonth]);
            });

        if ($request->input('building') != null) {
            $query->whereIn('building_id', $request->input('building'));
        }

        if ($request->input('room_id') != null) {
            $query->whereIn('bookings.room_id', $request->input('room_id'));
        }

        // Add subquery to filter out conflicting bookings and leave the most recent one
        $query->whereNotExists(function ($subquery) {
            $subquery->select(DB::raw(1))
                ->from('bookings as b2')
                ->whereRaw('bookings.conflict_id = b2.conflict_id')
                ->whereRaw('bookings.created_at > b2.created_at');
        });


        $query = $query->get();

        if ($request->input('ical') == true) {
            return $this->generateICal($query);
        }
        return response()->json($query);
    }

    public function getConflicts(Request $request)
    {
        // Get the current user ID from the authenticated user
        //$currentUserId = Auth::id();

        $sortBy = $request->input('sortBy', 'created_at');
        $sortOrder = $request->input('sortOrder', 'desc');

        $months = $request->input('start');
        $days = $request->input('days');
        $type = $request->input('type', ['normal', 'recurring']);

        $allRoomIds = Room::join('moderator_room', 'rooms.id', '=', 'moderator_room.room_id')
            ->where('moderator_room.user_id', $request->user_id)
            ->where('rooms.status', 1)
            ->pluck('rooms.id')
            ->toArray();
        $roomIds = $request->input('room_id');
        if ($request->input('room_id') == null) {
            $roomIds = $allRoomIds;
        }

        $semester = Semester::where('is_current', true)->first();
        $query = Booking::join('rooms', 'bookings.room_id', '=', 'rooms.id')
            ->where('semester_id', $semester->id)
            ->whereIn('room_id', $roomIds)
            ->where('rooms.status', 1)
            ->whereNotIn('bookings.status', [2])
            ->whereIn('bookings.type', $type)
            ->whereNotNull('conflict_id')
            ->orderBy($sortBy, $sortOrder)
            ->select('bookings.*', 'rooms.name as room_name', 'rooms.color as color');

        // If months are provided, filter by them
        if (!empty($months)) {
            $query->whereIn(DB::raw('MONTH(bookings.start)'), $months);
        }

        // If days of the week are provided, filter by them
        if (!empty($days)) {
            // Adjust the day values to match the MySQL convention
            $adjustedDays = array_map(function ($day) {
                return $day + 1; // Increment by 1 to match MySQL convention
            }, $days);

            $query->whereIn(DB::raw('DAYOFWEEK(bookings.start)'), $adjustedDays);
        }

        $conflicts = $query->get()->groupBy('conflict_id');

        $conflictingBookings = new Collection();
        $conflicts->each(function ($conflict) use ($conflictingBookings) {
            $conflictingBookings->push((object) [
                'id' => $conflict[0]->conflict_id,
                'bookings' => $conflict,
                'room_id' => $conflict[0]->room_id,
                'room_name' => Room::where('id', $conflict[0]->room_id)->first()->name,
            ]);
        });

        $page = $request->input('page', 1);

        // Define the number of items per page
        $perPage = $request->input('perPage', 1); // You can adjust this number as needed

        $conflictsPaginated = new LengthAwarePaginator(
            $conflictingBookings->forPage($page, $perPage),
            $conflictingBookings->count(),
            $perPage,
            $page,
            ['path' => route('getConflicts')]
        );

        // Convert the array back to a collection
        $conflictsCollection = collect($conflictsPaginated->items());

        // Extract items without keys
        $mappedConflicts = $conflictsCollection->values();
        // Combine mapped conflicts with pagination data
        $mergedData = [
            'data' => $mappedConflicts,
            'total' => $conflictsPaginated->total(),
            'per_page' => $conflictsPaginated->perPage(),
            'current_page' => $conflictsPaginated->currentPage(),
            'last_page' => $conflictsPaginated->lastPage(),
            'path' => $conflictsPaginated->path(),
        ];

        return response()->json($mergedData);
    }
    public function getRecurringConflicts(Request $request)
    {
        // Get the current user ID from the authenticated user
        //$currentUserId = Auth::id();

        $sortBy = $request->input('sortBy', 'created_at');
        $sortOrder = $request->input('sortOrder', 'desc');

        $dayInputs = $request->input('days', [1, 2, 3, 4, 5]);

        $allRoomIds = Room::join('moderator_room', 'rooms.id', '=', 'moderator_room.room_id')
            ->where('moderator_room.user_id', $request->user_id)
            ->where('rooms.status', 1)
            ->pluck('rooms.id')
            ->toArray();
        $roomIds = $request->input('room_id');
        if ($request->input('room_id') == null) {
            $roomIds = $allRoomIds;
        }

        $semester = Semester::where('is_current', true)->first();
        $days = Day::whereIn('room_id', $roomIds)
            ->where('status', '!=', 2)
            ->whereIn('name', $dayInputs)
            ->where('semester_id', $semester->id)->get();
        $recurringIds = new Collection();
        foreach ($days as $day) {
            $recurringIds->push($day->recurring_id);
        }

        $recurrings = Recurring::where('semester_id', $semester->id)
            ->whereIn('id', $recurringIds)
            ->whereNotIn('status', [2])
            ->whereNotNull('conflict_id')
            ->orderBy($sortBy, $sortOrder)
            ->get();
        if ($recurrings->count() > 0) {
            $recurrings = $recurrings->groupBy('conflict_id');
        }
        $conflictingRecurrings = new Collection();
        $recurrings->each(function ($recurring) use ($conflictingRecurrings, $semester) {
            $days = new Collection();
            $recurring->each(function ($recurring) use ($days) {
                $days = Day::join('rooms', 'days.room_id', '=', 'rooms.id')
                    ->where('days.status', '!=', 2)
                    ->where('days.recurring_id', $recurring->id)
                    ->where('rooms.status', 1)
                    ->select('days.*', 'rooms.id as room_id', 'rooms.name as room_name')
                    ->get();
                $recurring->days = $days;
            });
            $conflictingDays = Day::whereIn('recurring_id', $recurring->pluck('id'))
                ->where('status', '!=', 2)
                ->whereNotNull('conflict_id')
                ->where('semester_id', $semester->id)
                ->get();

            $conflictingRooms = Room::whereIn('id', $conflictingDays->where('status', 1)->pluck('room_id'))->get();
            $conflictingRecurrings->push((object) [
                'id' => $recurring[0]->conflict_id,
                'bookings' => $recurring,
                'type' => 'recurringGroup',
                'conflictingDays' => $conflictingDays,
                'conflictingRooms' => $conflictingRooms,
            ]);
        });

        $page = $request->input('page', 1);
        // Define the number of items per page
        $perPage = $request->input('perPage', 1); // You can adjust this number as needed

        $conflictsPaginated = new LengthAwarePaginator(
            $conflictingRecurrings->forPage($page, $perPage),
            $conflictingRecurrings->count(),
            $perPage,
            $page,
            ['path' => route('getConflicts')]
        );

        // Convert the array back to a collection
        $conflictsCollection = collect($conflictsPaginated->items());

        // Extract items without keys
        $mappedConflicts = $conflictsCollection->values();
        // Combine mapped conflicts with pagination data
        $mergedData = [
            'data' => $mappedConflicts,
            'total' => $conflictsPaginated->total(),
            'per_page' => $conflictsPaginated->perPage(),
            'current_page' => $conflictsPaginated->currentPage(),
            'last_page' => $conflictsPaginated->lastPage(),
            'path' => $conflictsPaginated->path(),
        ];

        return response()->json($mergedData);
    }

    public function approveBooking(Request $request)
    {
        if ($request->input('type') == 'recurringGroup') {
            $this->approveRecurringBooking($request);
            return response()->json(['message' => 'Recurring booking approved successfully.']);
        }
        $bookings = Booking::whereIn('id', $request->input('id'))->get();
        if (!$bookings) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }
        foreach ($bookings as $booking) {
            $booking->status = 1;
            $booking->save();
        }
        return response()->json(['message' => 'Booking approved successfully.']);
    }

    public function approveRecurringBooking(Request $request)
    {
        $semester = Semester::where('is_current', true)->first();
        $recurrings = Recurring::whereIn('id', $request->input('id'))->get();

        if (!$recurrings) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }

        foreach ($recurrings as $recurring) {
            $recurring->status = 1;
            $recurring->save();

            $days = Day::where('recurring_id', $recurring->id)->get();

            $endDate = $semester->end;
            foreach ($days as $day) {
                $currentDate = Carbon::today();
                $daystart = Carbon::parse($day['start']);
                $dayend = Carbon::parse($day['end']);
                while ($currentDate <= Carbon::parse($endDate)) {
                    if ($currentDate->dayOfWeekIso == $day['name']) {
                        $booking = new Booking();
                        $booking->recurring_id = $recurring->id;
                        $booking->booker_id = $recurring->booker_id;
                        $booking->title = $recurring->title;
                        $booking->info = $recurring->info;
                        $booking->publicity = $recurring->publicity;
                        $booking->url = $recurring->url;
                        $booking->lecture_type = $recurring->lecture_type;
                        $booking->expected_attendance = $recurring->expected_attendance;
                        $booking->room_id = $day->room_id;
                        $booking->participants = $recurring->participants;
                        $booking->type = 'recurring';
                        $booking->start = $currentDate->copy()->setTime($daystart->hour, $daystart->minute);
                        $booking->status = 1;
                        $booking->end = $currentDate->copy()->setTime($dayend->hour, $dayend->minute);
                        $booking->save();
                    }
                    $currentDate->addDay();
                }
            }
        }
    }
    public function cancelBooking(Request $request)
    {
        if ($request->input('type') == 'recurringGroup') {
            $this->cancelRecurringBooking($request);
            return response()->json(['message' => 'Recurring booking canceled successfully.']);
        }
        $bookings = Booking::whereIn('id', $request->input('id'))->get();
        if (!$bookings) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }
        foreach ($bookings as $booking) {
            $booking->status = 2;
            $booking->save();
            $this->updateConflicts($booking);
        }
        return response()->json(['message' => 'Booking canceled successfully.']);
    }
    public function cancelRecurringBooking(Request $request)
    {
        $recurrings = Recurring::whereIn('id', $request->input('id'))->get();
        if (!$recurrings) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }
        foreach ($recurrings as $recurring) {
            $recurring->status = 2;
            $recurring->save();
            $bookings = Booking::where('recurring_id', $recurring->id)->get();
            foreach ($bookings as $booking) {
                $booking->status = 2;
                $booking->save();
                $this->updateConflicts($booking);
            }
        }
        return response()->json(['message' => 'Booking canceled successfully.']);
    }
    public function editBooking(Request $request)
    {
        $validatedData = $request->validate([
            'id' => 'required',
            'title' => 'required',
            'info' => 'nullable',
            'start' => 'required|date',
            'end' => 'required|date',
            'participants' => 'nullable',
            'type' => 'required',
            'publicity' => 'required',
            'lecture_type' => 'required',
            'expected_attendance' => 'required',
            'days' => 'nullable',
            'is_recurring' => 'nullable',
            'status' => 'required',
            'room_id' => 'nullable',
            'url' => 'nullable',
        ]);
        if ($request->input('is_recurring')) {
            $this->editRecurringBooking($validatedData);
            return response()->json(['message' => 'Recurring booking updated successfully.']);
        }
        $booking = Booking::find($validatedData['id']);
        if (!$booking) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }

        $booking->room_id = $validatedData['room_id'];
        $booking->publicity = $validatedData['publicity'];
        $booking->lecture_type = $validatedData['lecture_type'];
        $booking->expected_attendance = $validatedData['expected_attendance'];
        $booking->url = $validatedData['url'];
        $booking->title = $validatedData['title'];
        $booking->info = $validatedData['info'];
        $booking->start = $validatedData['start'];
        $booking->end = $validatedData['end'];
        $booking->save();

        $this->updateConflicts($booking);
        return response()->json(['message' => 'Booking updated successfully.']);
    }
    public function editRecurringBooking($validatedData)
    {
        $recurring = Recurring::find($validatedData['id']);
        if (!$recurring) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }

        //Update the recurring group
        $recurring->title = $validatedData['title'];
        $recurring->publicity = $validatedData['publicity'];
        $recurring->url = $validatedData['url'];
        $recurring->info = $validatedData['info'];
        $recurring->save();

        $existingDays = Day::where('recurring_id', $recurring->id)
            ->where('status', '!=', 2)
            ->get();
        $newDays = $validatedData['days'];
        $booker = $recurring->booker_id;

        foreach ($newDays as $newDay) {
            if (isset($newDay['id'])) {
                $newDayId = $newDay['id'];
                $existingDay = $existingDays->where('id', $newDayId)->first();


                if ($existingDay) {
                    // If the new day has an ID and it exists in the existing days
                    $existingDay->name = $newDay['name'];
                    $existingDay->start = $newDay['start'];
                    $existingDay->end = $newDay['end'];
                    $existingDay->room_id = $newDay['room_id'];
                    $existingDay->save();


                    // Remove the existing day from the list
                    foreach ($existingDays as $key => $day) {
                        if ($day->id === $existingDay->id) {
                            $existingDays->forget($key);
                            break; // Exit the loop after removing the item
                        }
                    }
                }
            } else {
                // If the new day doesn't have an ID or it doesn't exist in the existing days
                Day::create([
                    'recurring_id' => $recurring->id,
                    'name' => $newDay['name'],
                    'start' => $newDay['start'],
                    'end' => $newDay['end'],
                    'room_id' => $newDay['room_id'],
                    'status' => 0
                ]);
            }
        }
        // Set status to inactive for days not present in new data
        foreach ($existingDays as $existingDay) {
            $existingDay->status = 2;
            $existingDay->save();
        }
        if ($recurring->status == 1) {
            // Get existing bookings associated with the recurring booking
            $existingBookings = Booking::where('recurring_id', $recurring->id)->get();
            foreach ($existingBookings as $existingBooking) {
                // Get the day of the week for the existing booking
                $existingStart = Carbon::parse($existingBooking->start);
                $existingDayOfWeek = $existingStart->isoFormat('E');
                $matched = false;

                // Update existing bookings based on new day
                foreach ($newDays as $newDay) {
                    $newStart = Carbon::parse($newDay['start']);
                    $newEnd = Carbon::parse($newDay['end']);
                    $newDayOfWeek = $newDay['name'];
                    $newStartDateTime = Carbon::parse($newStart);
                    $newEndDateTime = Carbon::parse($newEnd);

                    // Extract the hours from the new start and end times
                    $newStartHours = $newStartDateTime->format('H');
                    $newStartMinutes = $newStartDateTime->format('i');
                    $newEndHours = $newEndDateTime->format('H');
                    $newEndMinutes = $newEndDateTime->format('i');

                    if ($existingDayOfWeek == $newDayOfWeek) {
                        $existingBooking->start = $existingBooking->start->copy()->hour($newStartHours)->minute($newStartMinutes);
                        $existingBooking->end = $existingBooking->end->copy()->hour($newEndHours)->minute($newEndMinutes);
                        $existingBooking->info = $recurring->info;
                        $existingBooking->publicity = $recurring->publicity;
                        $existingBooking->room_id = $newDay['room_id'];
                        $existingBooking->title = $recurring->title;
                        $existingBooking->save();
                        $matched = true;
                        break;
                    }
                }

                if (!$matched) {
                    // If no match found for day name, set the status of the existing booking to inactive
                    $existingBooking->status = 2;
                    $existingBooking->save();
                }
            }


            // Create new bookings based on new days
            foreach ($newDays as $newDay) {
                $newDayOfWeek = $newDay['name'];

                $newStart = Carbon::parse($newDay['start']);
                $newEnd = Carbon::parse($newDay['end']);

                $semester = Semester::where('is_current', true)->first();
                $semesterEnd = Carbon::parse($semester->end);
                $currentDate = Carbon::today();
                $existingBooking = Booking::where('recurring_id', $recurring->id)
                    ->where('status', '!=', 2)
                    ->whereRaw("DAYOFWEEK(start) = ($newDayOfWeek % 7) + 1")
                    ->first();

                if ($existingBooking == null) {
                    // If no existing booking, create a new booking for each week within the current semester
                    while ($currentDate <= $semesterEnd) {

                        $currentDayOfWeek = $currentDate->isoFormat('E');
                        if ($currentDayOfWeek == $newDayOfWeek) {
                            $booking = new Booking();
                            $booking->recurring_id = $recurring->id;
                            $booking->title = $validatedData['title'];
                            $booking->info = $validatedData['info'];
                            $booking->booker_id = $booker;
                            $booking->room_id = $newDay['room_id'];
                            $booking->type = 'recurring';
                            $booking->status = 1;
                            $booking->publicity = $recurring->publicity;
                            $booking->start = $currentDate->copy()->setTime($newStart->hour, $newStart->minute);
                            $booking->end = $currentDate->copy()->setTime($newEnd->hour, $newEnd->minute);
                            $booking->save();
                        }
                        $currentDate->addDay();
                    }
                }
            }
        }
        $this->updateConflicts($recurring);
        return response()->json(['message' => 'Booking updated successfully.']);
    }

    public function checkConflict(Request $request)
    {

        $semester = Semester::where('is_current', true)->first();
        if ($request->isRecurring == true) {
            $conflicts = new Collection();
            $days = $request->days;
            $existingRecurrings = Recurring::where('semester_id', $semester->id)
                ->where('status', '!=', 2) // Exclude cancelled recurrings
                ->where('id', '<>', $request->id)
                ->get();

            // Iterate over each existing recurring group
            foreach ($existingRecurrings as $existingRecurring) {
                // Fetch the days for the existing recurring group
                $existingRecurringDays = Day::where('recurring_id', $existingRecurring->id)
                    ->where('status', '!=', 2)
                    ->get();

                // Check for conflicts between the days of the recurring being created and the days of the existing recurring group
                foreach ($days as $recurringDay) {
                    foreach ($existingRecurringDays as $existingRecurringDay) {
                        // Get the start and end times for the days
                        $recurringDayStart = Carbon::createFromTimeString($recurringDay['start']);
                        $recurringDayEnd = Carbon::createFromTimeString($recurringDay['end']);
                        $existingRecurringDayStart = Carbon::createFromTimeString($existingRecurringDay->start);
                        $existingRecurringDayEnd = Carbon::createFromTimeString($existingRecurringDay->end);

                        // Check if the days are the same day of the week
                        if ($recurringDay['name'] == $existingRecurringDay->name && $recurringDay['room_id'] == $existingRecurringDay->room_id) {
                            // Check if the times overlap
                            if ($recurringDayStart->between($existingRecurringDayStart, $existingRecurringDayEnd) || $recurringDayEnd->between($existingRecurringDayStart, $existingRecurringDayEnd)) {
                                // Add the conflicting recurring to the collection
                                $conflicts->push($existingRecurring);
                                break 2; // No need to check further days or existing recurrings if a conflict is found
                            }
                        }
                    }
                }
            }
            $isConflicting = $conflicts->isNotEmpty();
            return response()->json(['isConflicting' => $isConflicting, 'conflicts' => $conflicts]);
        } else {
            $conflicts = Booking::where('semester_id', $semester->id)
                ->where('room_id', $request->room_id)
                ->where('status', '!=', 2)
                ->where(function ($query) use ($request) {
                    $query->where(function ($query) use ($request) {
                        $query->where('start', '>=', $request->start)
                            ->where('start', '<', $request->end);
                    })
                        ->orWhere(function ($query) use ($request) {
                            $query->where('end', '>', $request->start)
                                ->where('end', '<=', $request->end);
                        })
                        ->orWhere(function ($query) use ($request) {
                            $query->where('start', '<', $request->start)
                                ->where('end', '>', $request->end);
                        });
                })
                ->where('id', '<>', $request->id) // Exclude the current booking
                ->get();
            $isConflicting = $conflicts->isNotEmpty();

            return response()->json(['isConflicting' => $isConflicting, 'conflicts' => $conflicts]);
        }
    }

    public function resolveConflict(Request $request)
    {

        $bookings = $request->input('bookings');

        foreach ($bookings as $bookingData) {
            // Extract data from each booking object
            $bookingId = $bookingData['id'];
            $resolved = $bookingData['resolved'];
            $toKeep = $bookingData['toKeep'];
            $booking = Booking::find($bookingId);

            // Update the booking based on conditions
            if ($resolved) {
                $booking->room_id = $bookingData['room_id'];
                $booking->start = $bookingData['start'];
                $booking->end = $bookingData['end'];
                $booking->conflict_id = null;
                $booking->save();
            } else {
                if ($toKeep) {
                    $booking->room_id = $bookingData['room_id'];
                    $booking->start = $bookingData['start'];
                    $booking->end = $bookingData['end'];
                    $booking->conflict_id = null;
                    $booking->save();
                } else {
                    $booking->status = 2;
                    $booking->save();
                }
            }
        }

        return response()->json(['message' => 'Conflict resolved successfully']);
    }

    public function resolveRecurringConflict(Request $request)
    {
        $recurings = $request->input('bookings');

        foreach ($recurings as $recuringData) {
            // Extract data from each booking object
            $recuringId = $recuringData['id'];
            $resolved = $recuringData['resolved'];
            $toKeep = $recuringData['toKeep'];
            $recuring = Recurring::find($recuringId);
            // Update the booking based on conditions
            if ($resolved || $toKeep) {
                $this->editRecurringBooking($recuringData);
            } else {
                $recurringRequest = new Request([
                    'id' => [$recuring->id]
                ]);
                $this->cancelRecurringBooking($recurringRequest);
            }
        }
        foreach ($recurings as $recuringData) {
            $days = Day::where('recurring_id', $recuring->id)->get();
            foreach ($days as $day) {
                $day->conflict_id = null;
                $day->save();
            }
            $recuringId = $recuringData['id'];
            $recuring = Recurring::find($recuringId);
            $recuring->conflict_id = null;
            $recuring->save();
        }
        return response()->json(['message' => 'Conflict resolved successfully']);
    }

    public function updateConflicts($editedEntry)
    {
        $className = get_class($editedEntry);
        $conflicts = new Collection();

        switch ($className) {
            case 'App\Models\Booking':
                $conflicts = Booking::conflicts($editedEntry);
                $editedEntry->save();
                break;
            case 'App\Models\Recurring':
                $conflicts = Recurring::conflicts($editedEntry);
                break;
            default:
                throw new \Exception("Unknown model class: $className");
        }
        return $conflicts;
    }
    public function generateICal($bookings, $recurring = false)
    {
        // Set headers
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="bookings.ics"');

        // Initialize iCal content
        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";
        $ical .= "PRODID:bookingservice\r\n";

        if ($recurring === true) {

            $semester = Semester::where('is_current', true)->first();

            $semesterStartDate = date('Ymd\THis\Z', strtotime($semester->start));
            $semesterEndDate = date('Ymd\THis\Z', strtotime($semester->end));

            // Iterate over each recurring event
            foreach ($bookings as $event) {
                $rrule = 'FREQ=WEEKLY;'; // Default RRULE, assuming events repeat weekly
                $dtstamp = date('Ymd\THis\Z'); // Current date and time in UTC format
                $icalEntry = "BEGIN:VEVENT\n";
                $icalEntry .= "DTSTAMP:" . $dtstamp . "\r\n";
                $icalEntry .= "UID:" . uniqid() . "\r\n";
                $rrule = 'FREQ=WEEKLY;';
                $byDayValues = [];
                foreach ($event->days as $day) {
                    $dayName = strtoupper(substr(date('D', strtotime("Sunday +{$day['name']} days")), 0, 2)); // Convert day name to uppercase abbreviated form (e.g., MO, TU)
                    $byDayValues[] = $dayName; // Add only the day abbreviation
                }
                $rrule .= 'BYDAY=' . implode(',', $byDayValues) . ';';
                $rrule .= "UNTIL={$semesterEndDate};";

                // Create iCalendar entry for the recurring event
                $icalEntry .= "DTSTART:" . $semesterStartDate . "\r\n";
                $icalEntry .= "RRULE:$rrule\n"; // Recurrence rule
                $icalEntry .= "SUMMARY:{$event->title}\n"; // Event title
                $icalEntry .= "DESCRIPTION:{$event->info}\n"; // Event description
                $icalEntry .= "END:VEVENT\n";

                // Add the iCalendar entry to the array
                $icalEntries[] = $icalEntry;
            }

            // Combine all iCalendar entries into a single string
            $icalData = "BEGIN:VCALENDAR\n";
            $icalData .= "VERSION:2.0\n";
            $icalData .= "PRODID:bookingservice\r\n";
            $icalData .= implode('', $icalEntries);
            $icalData .= "END:VCALENDAR";

            // Output the iCalendar data
            header('Content-type: text/calendar; charset=utf-8');
            header('Content-Disposition: attachment; filename="calendar.ics"');

            return $icalData;
        }

        // Loop through bookings and generate iCal events
        foreach ($bookings as $booking) {
            $ical .= "BEGIN:VEVENT\r\n";
            $ical .= "UID:" . uniqid() . "\r\n"; // Unique identifier for the event
            $ical .= "DTSTART:" . date('Ymd\THis\Z', strtotime($booking->start)) . "\r\n"; // Start date and time
            $ical .= "DTEND:" . date('Ymd\THis\Z', strtotime($booking->end)) . "\r\n"; // End date and time
            $ical .= "SUMMARY:" . $booking->title . "\r\n"; // Event summary
            $ical .= "DESCRIPTION:" . $booking->info . "\r\n"; // Event summary
            $ical .= "LOCATION:" . $booking->room_name . "\r\n"; // Event summary
            $ical .= "END:VEVENT\r\n";
        }

        // Close iCal content
        $ical .= "END:VCALENDAR";

        return $ical;
    }
}
