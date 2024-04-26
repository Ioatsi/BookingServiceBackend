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
        $semesters = Semester::orderBy('start')->paginate($perPage);
        
        // You can also apply additional transformations or modifications to each semester if needed
        foreach ($semesters as $semester) {
            $semester->name = $semester->type . ' ' . Carbon::parse($semester->start)->year;
            $semester->isSemester = true;
        }
    
        return response()->json($semesters);
    }
}
