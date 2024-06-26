<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

use App\Models\Room;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;

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

        if ($request->input('id')) {
            $id = $request->input('id');

            $query = Room::join('departments', 'rooms.department_id', '=', 'departments.id')
                ->join('buildings', 'rooms.building_id', '=', 'buildings.id')->where('rooms.status', 1)
                ->select('rooms.*', 'departments.name as department', 'buildings.name as building');
        } else {
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
            $moderators = DB::table('moderator_room')->where('room_id', '=', $room->id)->join('users', 'moderator_room.user_id', '=', 'users.id')->select('users.*')->get();
            $room_groups->push((object) [
                'id' => $room->id,
                'name' => $room->name,
                'number' => $room->number,
                'color' => $room->color,
                'capacity' => $room->capacity,
                'info' => $room->info,
                'department' => $room->department,
                'department_id' => $room->department_id,
                'building_id' => $room->building_id,
                'building' => $room->building,
                'moderators' => $moderators,
                'isRoom' => true
            ]);
        });
        return response()->json([
            'rooms' => $room_groups
        ]);
    }

    public function getModeratedRooms($id)
    {
        $currentUserId = Auth::id();
        $userRoles = Auth::user()->roles;
        $roomsIds = DB::table('moderator_room')->where('user_id', $currentUserId)->get();
        $rooms = Room::whereIn('id', $roomsIds->pluck('room_id'))->get();
        foreach ($userRoles as $role) {
            if ($role->name == 'admin') {
                $allRoomIds = Room::pluck('rooms.id')
                    ->toArray();
                $rooms = Room::whereIn('id', $allRoomIds)->get();
            }
        }
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

    public function getPossibleModerators(Request $request)
    {
        $possibleModerators = User::join('role_user', 'users.id', '=', 'role_user.user_id')
            ->where('role_user.role_id', 3)
            ->select('users.*')
            ->get();

        return response()->json($possibleModerators);
    }
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required',
            'info' => 'required',
            'department_id' => 'required',
            'building_id' => 'required',
            'moderator_ids' => 'required',
            'color' => 'required',
        ]);
        $room = Room::create($validatedData);
        $moderatorIds = $request->input('moderator_ids');

        foreach ($moderatorIds as $moderatorId) {
            DB::table('moderator_room')->insert([
                'user_id' => $moderatorId,
                'room_id' => $room->id
            ]);
        }

        return response()->json(['message' => 'Booking created successfully.', 'booking' => $room], 201);
    }

    public function deleteRoom(Request $request)
    {
        $id = $request->input('id');
        $rooms = Room::whereIn('id', $id)->get();
        foreach ($rooms as $room) {
            $room->status = 0;
            $room->save();
        }
        return response()->json(['message' => 'Room deleted successfully.']);
    }
    public function updateRoom(Request $request)
    {
        $id = $request->input('id');
        $room = Room::find($id);
        $room->update($request->all());
        return response()->json(['message' => 'Room updated successfully.']);
    }
}
