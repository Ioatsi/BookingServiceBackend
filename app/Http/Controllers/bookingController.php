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

use Illuminate\Support\Collection;

class bookingController extends Controller
{
    public function index(Request $request)
    {
        $semester = Semester::where('is_current', true)->first();
        $bookings = Booking::where('semester_id', $semester->id)->
        where('status', 0,)->
        where('type', 'normal')->
        whereNull('conflict_id')->
        whereIn('room_id', $request->input('room_id'))
        ->orderBy('created_at', 'desc')
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

        //$booking = Booking::create($validatedData);

        //get semester start and end

        $endDate = $semester->end;
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
        $bookings = Booking::where('semester_id', $semester->id)
        ->whereIn('room_id', $request->input('room_id'))
        ->where('status', 1)
        ->orderBy('created_at', 'desc')
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

        if($recurrings->count()>0){
            $recurrings = $recurrings->groupBy('recurring_id');
        }
        
        $recurring_groups = new Collection();
        $recurrings->each(function ($recurring) use ($recurring_groups) {
            $recurring_groups->push((object)[
                'id' => $recurring[0]->recurring_id,
                'title' => $recurring[0]->title,
                //'bookings' => $recurring,
                'room_id' => $recurring[0]->room_id,
                'info' => $recurring[0]->info,
                'status' => $recurring[0]->status,
                'days' => Day::where('recurring_id', $recurring[0]->recurring_id)->get()
            ]);
        });
        
        return response()->json($recurring_groups);
    }

    public function getConflicts(Request $request)
    {
        $semester = Semester::where('is_current', true)->first();
        $conflicts = Booking::where('semester_id', $semester->id)
        ->whereIn('room_id', $request->input('room_id'))
        //->where('status', 0)
        ->whereNotNull('conflict_id')
        ->orderBy('created_at', 'desc')
        ->get();

        if($conflicts->count()>0){
            $conflicts = $conflicts->groupBy('conflict_id');
        }

        $conflict_groups = new Collection();
        $conflicts->each(function ($conflict) use ($conflict_groups) {
            $conflict_groups->push((object)[
                'id' => $conflict[0]->conflict_id,
                'bookings' => $conflict,
            ]);
        });

        return response()->json($conflict_groups);
    }

    public function updateBookingStatus(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);
        $booking->status = $request->input('status');
        $booking->save();

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
    public function updateBooking(Request $request)
    {
        $bookings = Booking::whereIn('id', $request->input('id'))->get();
        if (!$bookings) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }
        foreach($bookings as $booking){
            $booking->update($request->all());
        }
        return response()->json(['message' => 'Booking updated successfully.']);
    }

    public function updateRecurring(Request $request)
    {
        $recurings = Recurring::whereIn('id', $request->input('id'))->get();
        if (!$recurings) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }
        foreach ($recurings as $recuring) {
            $recuring->update($request->all());
            $bookings = Booking::where('recurring_id', $recuring->id)->get();
            foreach($bookings as $booking){
                $booking->update($request->all());
            }
            return response()->json(['message' => 'Booking updated successfully.']);
        }
    }
}
