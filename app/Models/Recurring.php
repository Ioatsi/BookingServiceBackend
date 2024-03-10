<?php

namespace App\Models;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recurring extends Model
{
    use HasFactory;
    protected $fillable = [
        'semester_id',
        'title',
        'status',
        'room_id',
        'booker_id',
        'conflict_id',
        'title',
        'color',
        'info',
        'participants',
    ];
    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }
    public function day()
    {
        return $this->hasMany(Day::class);
    }
    //give curent semester id
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->attributes['semester_id'] = Semester::where('is_current', true)->value('id');
    }    

    public function scopeConflicts($query, Recurring $recurring)
    {
        $semester = Semester::where('is_current', true)->first();
        // Initialize a collection to store conflicting recurrings
        $conflictingRecurrings = collect();

        // Retrieve the associated days for the recurring booking
        $days = Day::where('recurring_id', $recurring->id)->get();

        $existingRecurrings = Recurring::where('room_id', $recurring->room_id)
            ->where('semester_id', $semester->id)
            ->where('status', '!=', 2) // Exclude cancelled recurrings
            ->where('id', '<>', $recurring->id)
            ->get();
        // Iterate over each existing recurring group
        foreach ($existingRecurrings as $existingRecurring) {
            // Fetch the days for the existing recurring group
            $existingRecurringDays = Day::where('recurring_id', $existingRecurring->id)->get();
            
            // Check for conflicts between the days of the recurring being created and the days of the existing recurring group
            foreach ($days as $recurringDay) {
                foreach ($existingRecurringDays as $existingRecurringDay) {
                    // Get the start and end times for the days
                    $recurringDayStart = Carbon::createFromTimeString($recurringDay->start);
                    $recurringDayEnd = Carbon::createFromTimeString($recurringDay->end);
                    $existingRecurringDayStart = Carbon::createFromTimeString($existingRecurringDay->start);
                    $existingRecurringDayEnd = Carbon::createFromTimeString($existingRecurringDay->end);

                    // Check if the days are the same day of the week
                    if ($recurringDay->name == $existingRecurringDay->name) {
                        // Check if the times overlap
                        if ($recurringDayStart->between($existingRecurringDayStart, $existingRecurringDayEnd) || $recurringDayEnd->between($existingRecurringDayStart, $existingRecurringDayEnd)) {
                            // Add the conflicting recurring to the collection
                            $conflictingRecurrings->push($existingRecurring);
                            break 2; // No need to check further days or existing recurrings if a conflict is found
                        }
                    }
                }
            }
        }


        return $conflictingRecurrings;
    }
}
