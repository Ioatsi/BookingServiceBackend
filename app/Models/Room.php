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
        'department_id',
        'number',
        'info',
        'color',
        'type',
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    protected $attributes = [
        'status' => 1,
        'type' => 'normal',
        'capacity' => 0,
        'department_id' => 0,
        'building_id' => 0,
        'number' => 0
    ];
    public function users()
    {
        return $this->belongsToMany(User::class, 'moderator_room', 'room_id', 'user_id');
    }
    public function buildings()
    {
        return $this->belongsTo(Building::class);
    }
    public function departments()
    {
        return $this->belongsTo(Department::class);
    }
}
