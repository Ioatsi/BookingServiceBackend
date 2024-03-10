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

use Illuminate\Support\Collection;

class bookingController extends Controller
{
    public function index(Request $request)
    {
        $semester = Semester::where('is_current', true)->first();
        $bookings = Booking::join('rooms', 'bookings.room_id', '=', 'rooms.id')
            ->where('bookings.semester_id', $semester->id)
            ->where('bookings.status', 0)
            ->where('bookings.type', 'normal')
            ->whereNull('bookings.conflict_id')
            ->whereIn('bookings.room_id', $request->input('room_id'))
            ->orderBy('bookings.created_at', 'desc')
            ->select('bookings.*', 'rooms.name as room_name')
            ->get();
        return response()->json($bookings);
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
            'info' => 'required',
            'start' => 'required|date',
            'end' => 'required|date',
            'room_id' => 'required|exists:rooms,id',
            'color' => 'required',
            'participants' => 'required',
            'type' => 'required',
            'days' => 'nullable',
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
        $recurring->color = $validatedData['color'];
        $recurring->participants = $validatedData['participants'];
        $recurring->room_id = $validatedData['room_id'];
        $recurring->booker_id = $validatedData['booker_id'];
        $recurring->status = 0;
        $recurring->semester_id = $semester->id;
        $recurring->save();
        $days = $validatedData['days'];
        foreach ($days as $day) {
            $day['recurring_id'] = $recurring->id;
            $day['status'] = 0;
            Day::create($day);
        }

        $conflicting = Recurring::conflicts($recurring);

        if ($conflicting->isNotEmpty()) {
            $firstConflicting = $conflicting->first();
            $conflictId = $firstConflicting->conflict_id ? $firstConflicting->conflict_id : static::generateUniqueConflictId();
            foreach ($conflicting as $conflict) {
                $conflict->update(['conflict_id' => $conflictId]);
            }

            $recurring->conflict_id = $conflictId;
            $recurring->save();
        }
    }
    protected static function generateUniqueConflictId()
    {
        $conflictId = null;
        do {
            // Generate a new unique ID (e.g., UUID)
            $conflictId = uniqid(); // Example: Generate a unique ID using uniqid()
            // Check if the generated ID already exists in the table
        } while (Recurring::where('conflict_id', $conflictId)->exists());

        return $conflictId;
    }


    public function getUserBookings($id)
    {
        $semester = Semester::where('is_current', true)->first();
        $bookings = Booking::where('semester_id', $semester->id)
            ->where('booker_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json($bookings);
    }

    public function getActiveBookings(Request $request)
    {
        $semester = Semester::where('is_current', true)->first();
        $bookings = Booking::join('rooms', 'bookings.room_id', '=', 'rooms.id')
            ->where('bookings.semester_id', $semester->id)
            ->whereIn('bookings.room_id', $request->input('room_id'))
            ->where('bookings.status', 1)
            ->orderBy('bookings.created_at', 'desc')
            ->select('bookings.*', 'rooms.name as room_name')
            ->get();
        return response()->json($bookings);
    }
    public function getRecurring(Request $request)
    {
        $semester = Semester::where('is_current', true)->first();
        $recurrings = Recurring::where('semester_id', $semester->id)
            ->whereIn('room_id', $request->input('room_id'))
            ->where('status', 0)
            ->where('conflict_id', null)
            ->orderBy('created_at', 'desc')
            ->get();


        $recurring_groups = new Collection();
        $recurrings->each(function ($recurring) use ($recurring_groups) {
            $recurring_groups->push((object)[
                'id' => $recurring->id,
                'title' => $recurring->title,
                //'bookings' => $recurring,
                'room_id' => $recurring->room_id,
                'room_name' => Room::where('id', $recurring->room_id)->first()->name,
                'info' => $recurring->info,
                'status' => $recurring->status,
                'type' => 'recurringGroup',
                'days' => Day::where('recurring_id', $recurring->id)
                    ->where('status', '!=', 2)
                    ->get(),
            ]);
        });

        return response()->json($recurring_groups);
    }

    public function getConflicts(Request $request)
    {
        $semester = Semester::where('is_current', true)->first();
        $conflicts = Booking::where('semester_id', $semester->id)
            ->whereIn('room_id', $request->input('room_id'))
            ->whereNotIn('status', [2])
            ->whereNotNull('conflict_id')
            ->orderBy('created_at', 'desc')
            ->get();


        if ($conflicts->count() > 0) {
            $conflicts = $conflicts->groupBy('conflict_id');
        }

        $conflictingBookings = new Collection();
        $conflicts->each(function ($conflict) use ($conflictingBookings) {
            $conflictingBookings->push((object)[
                'id' => $conflict[0]->conflict_id,
                'bookings' => $conflict,
                'room_id' => $conflict[0]->room_id,
                'room_name' => Room::where('id', $conflict[0]->room_id)->first()->name,
            ]);
        });

        $recurrings = Recurring::where('semester_id', $semester->id)
            ->whereIn('room_id', $request->input('room_id'))
            ->whereNotIn('status', [2])
            ->whereNotNull('conflict_id')
            ->orderBy('created_at', 'desc')
            ->get();
        if ($recurrings->count() > 0) {
            $recurrings = $recurrings->groupBy('conflict_id');
        }
        $conflictingRecurrings = new Collection();
        $recurrings->each(function ($recurring) use ($conflictingRecurrings) {
            $days = new Collection();
            $recurring->each(function ($recurring) use ($days) {
                $days = Day::where('recurring_id', $recurring->id)->get();
                $recurring->days = $days;
            });
            $conflictingRecurrings->push((object)[
                'id' => $recurring[0]->conflict_id,
                'recurring' => $recurring,
                'room_id' => $recurring[0]->room_id,
                'room_name' => Room::where('id', $recurring[0]->room_id)->first()->name,
            ]);
        });

        return response()->json([
            'conflictingBookings' => $conflictingBookings,
            'conflictingRecurrings' => $conflictingRecurrings
        ]);
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
                        $booking->room_id = $recurring->room_id;
                        $booking->color = $recurring->color;
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
            }
        }
        return response()->json(['message' => 'Booking canceled successfully.']);
    }
    public function editBooking(Request $request)
    {
        $validatedData = $request->validate([
            'id' => 'required',
            'room_id' => 'required',
            'title' => 'required',
            'info' => 'required',
            'start' => 'required|date',
            'end' => 'required|date',
            'color' => 'nullable',
            'participants' => 'nullable',
            'type' => 'required',
            'days' => 'nullable',
            'is_recurring' => 'nullable',
            'status' => 'required',
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
        $booking->title = $validatedData['title'];
        $booking->info = $validatedData['info'];
        $booking->start = $validatedData['start'];
        $booking->end = $validatedData['end'];
        $booking->save();

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
        $recurring->title = $validatedData['info'];
        $recurring->room_id = $validatedData['room_id'];
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
                    $existingDay->save();


                    // Remove the existing day from the list
                    foreach ($existingDays as $key => $day) {
                        if ($day->id === $existingDay->id) {
                            $existingDays->forget($key);
                            break; // Exit the loop after removing the item
                        }
                    }
                    //echo $existingDays;
                }
            } else {
                // If the new day doesn't have an ID or it doesn't exist in the existing days
                Day::create([
                    'recurring_id' => $recurring->id,
                    'name' => $newDay['name'],
                    'start' => $newDay['start'],
                    'end' => $newDay['end'],
                    'status' => 0
                ]);
            }
        }

        // Set status to inactive for days not present in new data
        foreach ($existingDays as $existingDay) {
            $existingDay->status = 2;
            $existingDay->save();
        }


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
                $newDayOfWeek = $newStart->isoFormat('E');
                $newStartDateTime = Carbon::parse($newStart);
                $newEndDateTime = Carbon::parse($newEnd);

                // Extract the hours from the new start and end times
                $newStartHours = $newStartDateTime->format('H');
                $newEndHours = $newEndDateTime->format('H');

                if ($existingDayOfWeek == $newDayOfWeek) {
                    $existingBooking->start->hour = $newStartHours;
                    $existingBooking->end->hour = $newEndHours;
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
            $newDate = Carbon::parse($newDay['start']);
            $newDayOfWeek = $newDate->isoFormat('E');

            $newStart = Carbon::parse($newDay['start']);
            $newEnd = Carbon::parse($newDay['end']);

            $semester = Semester::where('is_current', true)->first();
            $semesterEnd = Carbon::parse($semester->end);
            $currentDate = Carbon::today();
            $existingBooking = Booking::where('recurring_id', $recurring->id)
                ->whereRaw("DAYOFWEEK(start) = $newDayOfWeek")
                ->first();

            if (!$existingBooking) {
                // If no existing booking, create a new booking for each week within the current semester
                while ($currentDate <= $semesterEnd) {
                    $currentDayOfWeek = $currentDate->isoFormat('E');
                    if ($currentDayOfWeek == $newDayOfWeek) {
                        $booking = new Booking();
                        $booking->recurring_id = $recurring->id;
                        $booking->title = $validatedData['title'];
                        $booking->info = $validatedData['info'];
                        $booking->booker_id = $booker;
                        $booking->room_id = $validatedData['room_id'];
                        $booking->type = 'recurring';
                        $booking->color = 'blue';
                        $booking->status =  $validatedData['status'];
                        $booking->start = $currentDate->copy()->setTime($newStart->hour, $newStart->minute);
                        $booking->end = $currentDate->copy()->setTime($newEnd->hour, $newEnd->minute);
                        $booking->save();
                        break;
                    }
                    $currentDate->addDay();
                }
            }
        }
        return response()->json(['message' => 'Booking updated successfully.']);
    }

    public function checkConflict(Request $request)
    {
        $conflicts = Booking::where('room_id', $request->room_id)
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

        return response()->json(['isConflicting' => $isConflicting, 'conflicts' => $conflicts]);;
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
        $recurings = $request->input('recurings');

        foreach ($recurings as $recuringData) {
            // Extract data from each booking object
            $recuringId = $recuringData['id'];
            $resolved = $recuringData['resolved'];
            $toKeep = $recuringData['toKeep'];
            $recuring = Booking::find($recuringId);

            // Update the booking based on conditions
            if ($resolved) {
                $recuring->room_id = $recuringData['room_id'];
                $recuring->start = $recuringData['start'];
                $recuring->end = $recuringData['end'];
                $recuring->conflict_id = null;
                $recuring->save();
            } else {
                if ($toKeep) {
                    $recuring->conflict_id = null;
                    $recuring->save();
                } else {
                    $recuring->status = 2;
                    $recuring->save();
                }
            }
        }

        return response()->json(['message' => 'Conflict resolved successfully']);
    }
}
