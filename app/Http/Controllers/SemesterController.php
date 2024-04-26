<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Semester;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SemesterController extends Controller
{
    public function getAllSemesters(Request $request)
    {
        $perPage = $request->input('perPage', 10); // Set the number of items per page, default is 10
        $semesters = Semester::where('status', 1)->orderBy('start','desc')->paginate($perPage);
        
        // You can also apply additional transformations or modifications to each semester if needed
        foreach ($semesters as $semester) {
            $semester->name = $semester->type . ' ' . Carbon::parse($semester->start)->year;
            $semester->isSemester = true;
        }
    
        return response()->json($semesters);
    }
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required',   
            'start' => 'required',
            'end' => 'required',
            'is_current' => 'required',
        ]);
        $semester = new Semester();
        $semester->type = $request->type;
        $semester->start = $request->start;
        $semester->end = $request->end;
        $semester->is_current = $request->is_current;
        $semester->status = 1;
        $semester->save();
        return response()->json($semester);
    }
    public function updateSemester(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'type' => 'required',   
            'start' => 'required',
            'end' => 'required',
            'is_current' => 'required',
        ]);
        $semester = Semester::find($request->id);
        $semester->type = $request->type;
        $semester->start = $request->start;
        $semester->end = $request->end;
        $semester->is_current = $request->is_current;
        $semester->status = 1;
        $semester->save();
        return response()->json($semester);
    }
    public function deleteSemester(Request $request)
    {
        $request->validate([
            'id' => 'required',
        ]);
        $semesters = Semester::whereIn('id',$request->id)->get();
        foreach ($semesters as $semester) {
            $semester->status = 0;
            $semester->save();
        }
        
        return response()->json($semesters);
    }
}
