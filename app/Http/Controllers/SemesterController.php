<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Semester;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SemesterController extends Controller
{
    public function getAllSemesters()
    {
        $semesters = Semester::all()->sortBy('start');
        foreach ($semesters as $semester) {
            $semester->name=$semester->type.' '.Carbon::parse($semester->start)->year;
        }
        return response()->json($semesters);
    }

}
