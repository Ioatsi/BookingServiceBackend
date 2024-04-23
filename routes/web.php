<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\BookingController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SemesterController;
use App\Http\Controllers\StatisticsController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
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
Route::post('getDepartments', [RoomController::class, 'getDepartments']);
Route::post('getBuildings', [RoomController::class, 'getBuildings']);
Route::get('getAllRooms', [RoomController::class, 'getAllRooms'])->name('getAllRooms');

Route::post('createRoom', [RoomController::class, 'store']);

Route::get('getModeratedRooms/{id}', [RoomController::class, 'getModeratedRooms']);

//Semester
Route::get('getAllSemesters', [SemesterController::class, 'getAllSemesters']);

//Statistics
Route::post('roomHourOfDayOfWeekFrequency', [StatisticsController::class, 'roomHourOfDayOfWeekFrequency']);
Route::post('roomDayOfWeekFrequency', [StatisticsController::class, 'roomDayOfWeekFrequency']);
Route::post('roomDayOfMonthFrequency', [StatisticsController::class, 'roomDayOfMonthFrequency']);
Route::post('roomMonthOfSemesterFrequency', [StatisticsController::class, 'roomMonthOfSemesterFrequency']);
Route::post('roomDateRangeFrequency', [StatisticsController::class, 'roomDateRangeFrequency']);

Route::post('roomDayOfWeekDurationFrequency', [StatisticsController::class, 'roomDayOfWeekDurationFrequency']);
Route::post('roomMonthDurationFrequency', [StatisticsController::class, 'roomMonthDurationFrequency']);
Route::post('roomDateRangeDurationFrequency', [StatisticsController::class, 'roomDateRangeDurationFrequency']);

Route::post('roomOccupancyByDayOfWeekPercentage', [StatisticsController::class, 'roomOccupancyByDayOfWeekPercentage']);
Route::post('roomOccupancyByMonthPercentage', [StatisticsController::class, 'roomOccupancyByMonthPercentage']);
Route::post('roomOccupancyBySemester', [StatisticsController::class, 'roomOccupancyBySemester']);
Route::post('roomOccupancyByDateRange', [StatisticsController::class, 'roomOccupancyByDateRange']);


Route::get('generalStatistics', [StatisticsController::class, 'generalStatistics']);

Route::post('bookingTotals', [StatisticsController::class, 'bookingTotals']);
Route::post('approvalRate', [StatisticsController::class, 'approvalRate']);
Route::post('meanDuration', [StatisticsController::class, 'meanDuration']);
Route::post('bussiestRooms', [StatisticsController::class, 'bussiestRooms']);
Route::post('bussiestRoomThisWeek', [StatisticsController::class, 'bussiestRoomThisWeek']);
Route::post('weekCapacityIndicator', [StatisticsController::class, 'weekCapacityIndicator']);
Route::post('monthCapacityIndicator', [StatisticsController::class, 'monthCapacityIndicator']);
