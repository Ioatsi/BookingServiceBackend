<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;

use App\Models\Room;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $rooms = Room::all();
        return response()->json($rooms);
    }

    public function getModeratedRooms($id){
        $roomsIds = DB::table('moderator_room')->where('user_id', $id)->get();
        $rooms = Room::whereIn('id', $roomsIds->pluck('room_id'))->get();
        return response()->json($rooms);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Room $room)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Room $room)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Room $room)
    {
        //
    }
}
