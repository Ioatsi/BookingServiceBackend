<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;
    protected $fillable = [
        'group_id',
        'booker_id',
        'semester_id',
        'room_id',
        'moderator_id',
        'status',
        'title',
        'start',
        'end',
        'color',
        'info',
        'participants',
        'type',
    ];

    protected $casts = [
        'start' => 'datetime',
        'end' => 'datetime',
    ];

    public function bookingGroup()
    {
        return $this->belongsTo(BookingGroup::class, 'group_id');
    }

    public function booker()
    {
        return $this->belongsTo(User::class, 'booker_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }
}
