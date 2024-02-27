<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Models\Booking;

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
        ]);
        /* if (!Gate::forUser($request->input('booker_id'))->allows('create-booking')) {
            abort(403);
        } */
        $booking = Booking::create($validatedData);
        return response()->json(['message' => 'Booking created successfully.', 'booking' => $booking], 201);
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
