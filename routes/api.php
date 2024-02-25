<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\RoomController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();    
});
/* Booking */
Route::get('/bookings', [BookingController::class, 'index']);

//For moderator and admin
Route::get('/getAllBookings', [BookingController::class, 'getAllBookings']);

//For user
Route::get('/getUserBookings/{id}', [BookingController::class, 'getUserBookings']);

//For callendar
Route::get('/getActiveBookings', [BookingController::class, 'getActiveBookings']);

//Get active bookings by room
Route::get('/getBookingByRoom/{id}', [BookingController::class, 'getBookingByRoom']);

//Get active and pending bookings by room ids
Route::post('getAllBookingsByRoom', [BookingController::class, 'getAllBookingsByRoom']);

Route::post('/createBooking', [BookingController::class, 'store']);


//Room
Route::get('getRooms', [RoomController::class, 'index']);

Route::get('getModeratedRooms/{id}', [RoomController::class, 'getModeratedRooms']);