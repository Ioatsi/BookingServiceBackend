<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'capacity',
        'building_id',
        'department',
        'number',
        'type',
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
    public function users()
    {
        return $this->belongsToMany(User::class, 'moderator_room', 'room_id', 'user_id');
    }
}
