<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;
    protected $fillable = [
        'group_id',
        'recurring_id',
        'conflict_id',
        'booker_id',
        'semester_id',
        'room_id',
        'status',
        'title',
        'start',
        'end',
        'info',
        'participants',
        'type',
        'publicity',
        'url'
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($booking) {
            $conflicting = Booking::conflicts($booking);


            /* if ($conflicting->isNotEmpty()) {
                $firstConflicting = $conflicting->first();
                $conflictId = $firstConflicting->conflict_id ? $firstConflicting->conflict_id : static::generateUniqueConflictId();
                foreach ($conflicting as $conflict) {
                    $conflict->update(['conflict_id' => $conflictId]);
                }

                $booking->conflict_id = $conflictId;
            } */
        });
    }

    protected static function generateUniqueConflictId()
    {
        $conflictId = null;
        do {
            // Generate a new unique ID (e.g., UUID)
            $conflictId = uniqid(); // Example: Generate a unique ID using uniqid()
            // Check if the generated ID already exists in the table
        } while (static::where('conflict_id', $conflictId)->exists());

        return $conflictId;
    }
    public function scopeConflicts($query, Booking $booking)
    {
        // Check for bookings that conflict with the current booking
        $conflictingBookings = Booking::where('room_id', $booking->room_id)
        ->where('status', '!=', 2)
        ->where(function ($query) use ($booking) {
            $query->where(function ($query) use ($booking) {
                $query->where('start', '>=', $booking->start)
                      ->where('start', '<', $booking->end);
            })
            ->orWhere(function ($query) use ($booking) {
                $query->where('end', '>', $booking->start)
                      ->where('end', '<=', $booking->end);
            })
            ->orWhere(function ($query) use ($booking) {
                $query->where('start', '<', $booking->start)
                      ->where('end', '>', $booking->end);
            });
        })
        ->where('id', '<>', $booking->id) // Exclude the current booking
        ->get();

        if ($conflictingBookings->isNotEmpty()) {
            $firstConflicting = $conflictingBookings->first();
            $conflictId = $firstConflicting->conflict_id ? $firstConflicting->conflict_id : static::generateUniqueConflictId();
            foreach ($conflictingBookings as $conflict) {
                $conflict->update(['conflict_id' => $conflictId]);
            }

            $booking->conflict_id = $conflictId;
        }
        return $conflictingBookings;
    }

    protected $casts = [
        'start' => 'datetime',
        'end' => 'datetime',
    ];
    protected $attributes = [
        'status' => 0,
        'participants' => 'No participants',
        'publicity' => 0,
        'url' => null,
    ];

    //give curent semester id
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->attributes['semester_id'] = Semester::where('is_current', true)->value('id');
    }

    public function Group()
    {
        return $this->belongsTo(Group::class, 'group_id');
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

    public function recurring()
    {
        return $this->belongsTo(Recurring::class);
    }

}
