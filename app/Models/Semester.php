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
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}
