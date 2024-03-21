<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

use App\Models\Room;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class RoomController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Get the current user ID from the authenticated user
        //$currentUserId = Auth::id();

        $sortBy = $request->input('sortBy', 'created_at');
        $sortOrder = $request->input('sortOrder', 'desc');

        $page = $request->input('page', 1);
        $perPage = $request->input('perPage', 1); // You can adjust this number as needed

        $id = $request->input('id');

        $roomsIds = DB::table('moderator_room')->where('user_id', $id)->get();

        $query = Room::whereIn('rooms.id', $roomsIds->pluck('room_id'))
            ->join('departments', 'rooms.department_id', '=', 'departments.id')
            ->join('buildings', 'rooms.building_id', '=', 'buildings.id')
            ->select('rooms.*', 'departments.name as department', 'buildings.name as building')
            ->orderBy($sortBy, $sortOrder);

        $rooms = $query->paginate($perPage, ['*'], 'page', $page);


        $room_groups = new Collection();
        $rooms->each(function ($room) use ($room_groups) {
            $room_groups->push((object) [
                'id' => $room->id,
                'name' => $room->name,
                'number' => $room->number,
                'capacity' => $room->capacity,
                'info' => $room->info,
                'department' => $room->department,
                'building' => $room->building,
                'isRoom' => true
            ]);
        });
        return response()->json([
            'rooms' => $room_groups,
            'total' => $rooms->total(),
        ]);
    }

    public function getModeratedRooms($id)
    {
        $roomsIds = DB::table('moderator_room')->where('user_id', $id)->get();
        $rooms = Room::whereIn('id', $roomsIds->pluck('room_id'))->get();
        return response()->json($rooms);
    }

    public function getAllRooms()
    {
        $rooms = Room::all();
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
