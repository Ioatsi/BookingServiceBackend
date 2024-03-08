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
            $this->createRecurringBooking($validatedData);
            return response()->json(['message' => 'Recurring booking created successfully.'], 201);
        }
        $booking = Booking::create($validatedData);
        return response()->json(['message' => 'Booking created successfully.', 'booking' => $booking], 201);
    }

    public function createRecurringBooking($validatedData)
    {
        $semester = Semester::where('is_current', true)->first();
        $recurring = new Recurring();
        $recurring->title = $validatedData['title'];
        $recurring->status = 0;
        $recurring->semester_id = $semester->id;
        $recurring->save();
        $days = $validatedData['days'];

        //$booking = Booking::create($validatedData);

        //get semester start and end

        $endDate = $semester->end;
        foreach ($days as $day) {
            $currentDate = Carbon::today();
            $day['recurring_id'] = $recurring->id;
            $day['status'] = 0;
            $daystart = Carbon::parse($day['start']);
            $dayend = Carbon::parse($day['end']);
            Day::create($day);
            while ($currentDate <= Carbon::parse($endDate)) {
                if ($currentDate->dayOfWeekIso == $day['name']) {
                    $booking = new Booking();
                    $booking->recurring_id = $recurring->id;
                    $booking->booker_id = $validatedData['booker_id'];
                    $booking->title = $validatedData['title'];
                    $booking->info = $validatedData['info'];
                    $booking->start = $currentDate->copy()->setTime($daystart->hour, $daystart->minute);
                    $booking->end = $currentDate->copy()->setTime($dayend->hour, $dayend->minute);
                    $booking->room_id = $validatedData['room_id'];
                    $booking->color = $validatedData['color'];
                    $booking->participants = $validatedData['participants'];
                    $booking->type = 'recurring';
                    $booking->save();
                }
                $currentDate->addDay();
            }
        }
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
        $recurrings = Booking::where('semester_id', $semester->id)
            ->whereIn('room_id', $request->input('room_id'))
            ->where('type', 'recurring')
            ->where('status', 0)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($recurrings->count() > 0) {
            $recurrings = $recurrings->groupBy('recurring_id');
        }

        $recurring_groups = new Collection();
        $recurrings->each(function ($recurring) use ($recurring_groups) {
            $recurring_groups->push((object)[
                'id' => $recurring[0]->recurring_id,
                'title' => $recurring[0]->title,
                //'bookings' => $recurring,
                'room_id' => $recurring[0]->room_id,
                'room_name' => Room::where('id', $recurring[0]->room_id)->first()->name,
                'info' => $recurring[0]->info,
                'status' => $recurring[0]->status,
                'type' => 'recurringGroup',
                'days' => Day::where('recurring_id', $recurring[0]->recurring_id)->where('status', 0)->get(),
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

        $conflict_groups = new Collection();
        $conflicts->each(function ($conflict) use ($conflict_groups) {
            $conflict_groups->push((object)[
                'id' => $conflict[0]->conflict_id,
                'bookings' => $conflict,
                'room_id' => $conflict[0]->room_id,
                'room_name' => Room::where('id', $conflict[0]->room_id)->first()->name,
            ]);
        });

        return response()->json($conflict_groups);
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
        $recurring = Recurring::whereIn('id', $request->input('id'))->get();
        if (!$recurring) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }
        foreach ($recurring as $recurring) {
            $recurring->status = 1;
            $recurring->save();
            $bookings = Booking::where('recurring_id', $recurring->id)->get();
            foreach ($bookings as $booking) {
                $booking->status = 1;
                $booking->save();
            }
            return response()->json(['message' => 'Booking approved successfully.']);
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
        $recurring->title = $validatedData['title'];
        $recurring->save();
        $existingDays = Day::where('recurring_id', $recurring->id)->get();
        $newDays = $validatedData['days'];
        $booker = Booking::where('recurring_id', $recurring->id)->first();

        foreach ($newDays as $newDay) {
            if (isset($newDay['id']) && isset($existingDays[$newDay['id']])) {
                // If the new day has an ID and it exists in the existing days
                $existingDay = $existingDays[$newDay['id']];
                $existingDay->name = $newDay['name'];
                $existingDay->start = $newDay['start'];
                $existingDay->end = $newDay['end'];
                $existingDay->save();

                unset($existingDays[$newDay['id']]);
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

        foreach ($existingDays as $existingDay) {
            $existingDay->status = 1;
            $existingDay->save();
        }


        $existingBookings = Booking::where('recurring_id', $recurring->id)->get();
        foreach ($existingBookings as $existingBooking) {
            $existingStart = Carbon::parse($existingBooking->start);
            $existingDayOfWeek = $existingStart->isoFormat('E');
            $matched = false;

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
                $existingBooking->status = 2;
                $existingBooking->save();
            }
        }
        foreach ($newDays as $newDay) {
            $newDate = Carbon::parse($newDay['start']);
            $newDayOfWeek = $newDate->isoFormat('E');

            $newStart = Carbon::parse($newDay['start']);
            $newEnd = Carbon::parse($newDay['end']);

            $existingBooking = Booking::where('recurring_id', $recurring->id)
                ->whereRaw("DAYOFWEEK(start) = $newDayOfWeek")
                ->first();
            $semester = Semester::where('is_current', true)->first();
            $semesterEnd = Carbon::parse($semester->end);
            $currentDate = Carbon::today();

            if (!$existingBooking) {

                while ($currentDate <= $semesterEnd) {
                    $currentDayOfWeek = $currentDate->isoFormat('E');
                    if ($currentDayOfWeek == $newDayOfWeek) {
                        $booking = new Booking();
                        $booking->recurring_id = $recurring->id;
                        $booking->title = $validatedData['title'];
                        $booking->info = $validatedData['info'];
                        $booking->booker_id = $booker->booker_id;
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
            if(isset($bookingData['toKeep'])) {
                $toKeep = true;
            }
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
}
