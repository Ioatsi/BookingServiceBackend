<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Semester extends Model
{
    use HasFactory;

    protected $fillable = [
        'start',
        'end',
        'type',
        'is_current',
    ];
    public function calculateCapacityInHours()
    {
        // Calculate the total number of weeks in the semester
        $start = Carbon::parse($this->start);
        $end = Carbon::parse($this->end);
        $totalWeeks = $start->diffInWeeks($end);

        // Assuming a 40-hour work week, calculate the total capacity
        $totalCapacity = $totalWeeks * 60;

        return $totalCapacity;
    }
    protected $attributes = [
        'status' => 1,
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
    public function recurrings()
    {
        return $this->hasMany(Recurring::class);
    }
}
