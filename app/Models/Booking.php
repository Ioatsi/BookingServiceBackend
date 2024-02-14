<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;
    public function calendarEvent()
    {
        return $this->belongsTo(CalendarEvent::class, 'calendar_event_id');
    }
}
