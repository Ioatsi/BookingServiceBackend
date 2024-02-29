<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Models\Booking;
use App\Models\Day;
use App\Models\Recurring;
use App\Models\Semester;
use Carbon\Carbon;

class bookingController extends Controller
{
    public function index()
    {
        $bookings = Booking::all(); // Example: Fetch all bookings from the database
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
            'semester_id' => 'required|exists:semesters,id',
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
            return response()->json(['message' => 'Recrring booking created successfully.'], 201);
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
        $recurring-> semester_id = $semester->id;
        $recurring->save();
        $days = $validatedData['days'];

        $booking = Booking::create($validatedData);

        //get semester start and end

        $startDate = $semester->start;
        $endDate = $semester->end;
        //$start = date('Y-m-d', strtotime($start));
        //$end = date('Y-m-d', strtotime($end));
        foreach ($days as $day) {
            $currentDate = Carbon::today();
            $day['recurring_id'] = $recurring->id;
            $daystart = Carbon::parse($day['start']);
            $dayend = Carbon::parse($day['end']);
            Day::create($day);
            while ($currentDate <= Carbon::parse($endDate)) {
                if($currentDate->dayOfWeekIso == $day['name']){
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
                    $booking->type = $validatedData['type'];
                    $booking->save();
                }
                $currentDate->addDay();
            }
        }
    }
    public function getAllBookings()
    {
        $bookings = Booking::all();
        return response()->json($bookings);
    }

    public function getUserBookings($id)
    {
        $bookings = Booking::where('booker_id', $id)->get();
        return response()->json($bookings);
    }

    public function getActiveBookings()
    {
        $bookings = Booking::where('status', 1)->get();
        return response()->json($bookings);
    }

    public function getBookingByRoom($id)
    {
        $bookings = Booking::where('room_id', $id)->where('status', 1)->get();
        return response()->json($bookings);
    }

    public function getConflicts($bookings)
    {
        $conflicts = collect();
        foreach ($bookings as $booking) {
            $conflicts = $conflicts->merge(Booking::whereNotNull('conflict_id')->where('conflict_id', $booking->conflict_id)->get());
        }

        $conflicts = $conflicts->unique('id');
        return response()->json($conflicts);
    }
    public function getAllBookingsByRoom(Request $request)
    {

        // Retrieve array of IDs from request body
        $ids = $request->input('ids');
        $bookings = Booking::whereIn('room_id', $ids)->get();
        $conflicts = collect();
        $conflicts = $this->getConflicts($bookings);

        return response()->json([
            'bookings' => $bookings,
            'conflicts' => $conflicts
        ]);
    }

    public function updateBookingStatus(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);
        $booking->status = $request->input('status');
        $booking->save();

        return response()->json($booking);
    }

    public function getBookingById($id)
    {
        $booking = Booking::find($id);
        if (!$booking) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }
        return response()->json($booking);
    }

    public function deleteBooking($id)
    {
        $booking = Booking::find($id);
        if (!$booking) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }
        $booking->delete();
        return response()->json(['message' => 'Booking deleted successfully.']);
    }
    public function updateBooking(Request $request, $id)
    {
        $booking = Booking::find($id);
        if (!$booking) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }
        $booking->update($request->all());
        return response()->json(['message' => 'Booking updated successfully.']);
    }
}
