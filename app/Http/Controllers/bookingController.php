<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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
            'booker_id' => 'required|exists:users,id',
            'title' => 'required',
            'info' => 'required',
            'start' => 'required|date',
            'end' => 'required|date',
            'semester_id' => 'required|exists:semesters,id',
            'room_id' => 'required|exists:rooms,id',
            'status' => 'required|in:0,1',
            'color' => 'required',
            'participants' => 'required',
            'type' => 'required',

        ]);
        $booking = Booking::create($validatedData);
        return response()->json(['message' => 'Booking created successfully.', 'booking' => $booking], 201);
    }
}
