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

//For moderator and admin
Route::post('/getBookings', [BookingController::class, 'index']);
Route::post('/getRecurring', [BookingController::class, 'getRecurring']);
Route::post('/getConflicts', [BookingController::class, 'getConflicts'])->name('getConflicts');
Route::post('/getRecurringConflicts', [BookingController::class, 'getRecurringConflicts'])->name('getRecurringConflicts');
Route::post('/checkConflict', [BookingController::class,'checkConflict']);
Route::post('/resolveConflict', [BookingController::class,'resolveConflict']);
Route::post('/resolveRecurringConflict', [BookingController::class,'resolveRecurringConflict']);

//For calendar
Route::post('/getActiveBookings', [BookingController::class, 'getActiveBookings']);

//For user
Route::get('/getUserBookings/{id}', [BookingController::class, 'getUserBookings']);


Route::post('/createBooking', [BookingController::class, 'store']);


Route::post('/approveBooking', [BookingController::class, 'approveBooking']);
Route::post('/cancelBooking', [BookingController::class, 'cancelBooking']);
Route::post('/editBooking', [BookingController::class, 'editBooking']);


//Room
Route::post('getRooms', [RoomController::class, 'index']);
Route::post('getAllRooms', [RoomController::class, 'getAllRooms']);

Route::get('getModeratedRooms/{id}', [RoomController::class, 'getModeratedRooms']);