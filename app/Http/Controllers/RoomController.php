<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

use App\Models\Room;
use App\Models\User;
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

        $department = $request->input('department');
        $building = $request->input('building');

        if($request->input('id')) {
            $id = $request->input('id');
    
            $roomsIds = DB::table('moderator_room')->where('user_id', $id)->get();
    
            $query = Room::whereIn('rooms.id', $roomsIds->pluck('room_id'))
                ->join('departments', 'rooms.department_id', '=', 'departments.id')
                ->join('buildings', 'rooms.building_id', '=', 'buildings.id')
                ->select('rooms.*', 'departments.name as department', 'buildings.name as building');
            
        }else{
            $query = Room::join('departments', 'rooms.department_id', '=', 'departments.id')
                ->join('buildings', 'rooms.building_id', '=', 'buildings.id')
                ->select('rooms.*', 'departments.name as department', 'buildings.name as building');
        }

        if ($department) {
            $query->whereIn('rooms.department_id', $department);
        }

        if ($building) {
            $query->whereIn('rooms.building_id', $building);
        }

        $rooms = $query->get();


        $room_groups = new Collection();
        $rooms->each(function ($room) use ($room_groups) {
            $room_groups->push((object) [
                'id' => $room->id,
                'name' => $room->name,
                'number' => $room->number,
                'color' => $room->color,
                'capacity' => $room->capacity,
                'info' => $room->info,
                'department' => $room->department,
                'building' => $room->building,
                'isRoom' => true
            ]);
        });
        return response()->json([
            'rooms' => $room_groups 
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

    public function getDepartments(Request $request)
    {
        $departments = DB::table('departments')->get();
        return response()->json($departments);
    }

    public function getBuildings(Request $request)
    {
        $department = $request->input('department');
        $query = DB::table('buildings');

        if ($department) {
            $query = $query->where('department_id', $department);
        }

        $buildings = $query->get();
        return response()->json($buildings);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required',
            'info' => 'required',
            'capacity' => 'required',
            'department_id' => 'required',
            'building_id' => 'required',
            'color' => 'required',
            'number' => 'required',
        ]);
        $room = Room::create($validatedData);
        return response()->json(['message' => 'Booking created successfully.', 'booking' => $room], 201);
    }
}
