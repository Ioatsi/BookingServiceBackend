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
}
