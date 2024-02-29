<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Semester extends Model
{
    use HasFactory;

    protected $fillable = [
        'start',
        'end',
        'type',
        'is_current',
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
