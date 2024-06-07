<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\LoginController;
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
    Route::post('/checkConflict', [BookingController::class, 'checkConflict']);
    Route::post('/resolveConflict', [BookingController::class, 'resolveConflict']);
    Route::post('/resolveRecurringConflict', [BookingController::class, 'resolveRecurringConflict']);

    Route::post('/getActiveBookings', [BookingController::class, 'getActiveBookings']);
    Route::post('/getUserBookings', [BookingController::class, 'getUserBookings']);


    Route::get('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::post('/createBooking', [BookingController::class, 'store']);

    Route::post('/approveBooking', [BookingController::class, 'approveBooking']);
    Route::post('/cancelBooking', [BookingController::class, 'cancelBooking']);
    Route::post('/editBooking', [BookingController::class, 'editBooking']);


    //Room
    Route::post('getRooms', [RoomController::class, 'index']);
    Route::post('getDepartments', [RoomController::class, 'getDepartments']);
    Route::post('getBuildings', [RoomController::class, 'getBuildings']);
    
    Route::get('getAllRooms', [RoomController::class, 'getAllRooms'])->name('getAllRooms');

    Route::post('getAllSemesters', [SemesterController::class, 'getAllSemesters']);
    
    Route::middleware(['check.admin.access'])->group(function () {
        Route::post('createRoom', [RoomController::class, 'store']);
        Route::post('deleteRoom', [RoomController::class, 'deleteRoom']);
        Route::post('editRoom', [RoomController::class, 'updateRoom']);
        Route::get('getPossibleModerators/', [RoomController::class, 'getPossibleModerators']);
        //Semester
        Route::post('createSemester', [SemesterController::class, 'store']);
        Route::post('updateSemester', [SemesterController::class, 'updateSemester']);
        Route::post('deleteSemester', [SemesterController::class, 'deleteSemester']);

    });

    Route::middleware(['check.statistics.access'])->group(function () {

        Route::get('getModeratedRooms/{id}', [RoomController::class, 'getModeratedRooms']);

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
        Route::get('getOccupancyCharts', [StatisticsController::class, 'getOccupancyCharts']);
    
        Route::post('bookingTotals', [StatisticsController::class, 'bookingTotals']);
        Route::post('approvalRate', [StatisticsController::class, 'approvalRate']);
        Route::post('meanDuration', [StatisticsController::class, 'meanDuration']);
        Route::post('bussiestRooms', [StatisticsController::class, 'bussiestRooms']);
        Route::post('bussiestRoomThisWeek', [StatisticsController::class, 'bussiestRoomThisWeek']);
        Route::post('weekCapacityIndicator', [StatisticsController::class, 'weekCapacityIndicator']);
        Route::post('monthCapacityIndicator', [StatisticsController::class, 'monthCapacityIndicator']);
        Route::group(['middleware' => 'cas.auth'], function () {});
    });

Route::get('/login', [LoginController::class, 'login']);
Route::get('/authenticated', [LoginController::class, 'authenticated']);
Route::get('/cas/callback', [LoginController::class, 'handleCasCallback'])->name('cas.callback');
