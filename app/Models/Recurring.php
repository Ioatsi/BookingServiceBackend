<?php

namespace App\Models;

use Carbon\Carbon;

use Illuminate\Support\Collection;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recurring extends Model
{
    use HasFactory;
    protected $fillable = [
        'semester_id',
        'title',
        'status',
        'publicity',
        'booker_id',
        'conflict_id',
        'title',
        'info',
        'participants',
        'url'
    ];
    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }
    public function day()
    {
        return $this->hasMany(Day::class);
    }
    
    protected $attributes = [
        'publicity' => 0,
        'url' => null,
        "info" => 'No info provided',
    ];
    //give current semester id
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->attributes['semester_id'] = Semester::where('is_current', true)->value('id');
    }    

    protected static function generateUniqueConflictId()
    {
        $conflictId = null;
        do {
            // Generate a new unique ID (e.g., UUID)
            $conflictId = uniqid(); // Example: Generate a unique ID using uniqid()
            // Check if the generated ID already exists in the table
        } while (Recurring::where('conflict_id', $conflictId)->exists());

        return $conflictId;
    }

    public function scopeConflicts($query, Recurring $recurring)
    {
        $semester = Semester::where('is_current', true)->first();
        // Initialize a collection to store conflicting recurrings
        $conflictingRecurrings = collect();

        // Retrieve the associated days for the recurring booking
        $days = Day::where('recurring_id', $recurring->id)->get();

        $existingRecurrings = Recurring::where('semester_id', $semester->id)
            ->where('status', '!=', 2) // Exclude cancelled recurrings
            ->where('id', '<>', $recurring->id)
            ->get();

        // Iterate over each existing recurring group
        foreach ($existingRecurrings as $existingRecurring) {
            // Fetch the days for the existing recurring group
            $existingRecurringDays = Day::where('recurring_id', $existingRecurring->id)
            ->where('status', '!=', 2)
            ->get();
            
            // Check for conflicts between the days of the recurring being created and the days of the existing recurring group
            foreach ($days as $recurringDay) {
                foreach ($existingRecurringDays as $existingRecurringDay) {
                    // Get the start and end times for the days
                    $recurringDayStart = Carbon::createFromTimeString($recurringDay->start);
                    $recurringDayEnd = Carbon::createFromTimeString($recurringDay->end);
                    $existingRecurringDayStart = Carbon::createFromTimeString($existingRecurringDay->start);
                    $existingRecurringDayEnd = Carbon::createFromTimeString($existingRecurringDay->end);

                    // Check if the days are the same day of the week
                    if ($recurringDay->name == $existingRecurringDay->name && $recurringDay->room_id == $existingRecurringDay->room_id) {
                        // Check if the times overlap
                        if ($recurringDayStart->between($existingRecurringDayStart, $existingRecurringDayEnd) || $recurringDayEnd->between($existingRecurringDayStart, $existingRecurringDayEnd)) {
                            // Add the conflicting recurring to the collection
                            $conflictId = $existingRecurringDay->conflict_id ? $existingRecurringDay->conflict_id : static::generateUniqueConflictId();
                            $existingRecurringDay->update(['conflict_id' => $conflictId]);
                            $recurringDay->update(['conflict_id' => $conflictId]);
                            $conflictingRecurrings->push($existingRecurring);
                            //break 2; // No need to check further days or existing recurrings if a conflict is found
                        }
                    }
                }
            }
        }
        if ($conflictingRecurrings->isNotEmpty()) {
            $firstConflicting = $conflictingRecurrings->first();
            $conflictId = $firstConflicting->conflict_id ? $firstConflicting->conflict_id : static::generateUniqueConflictId();
            foreach ($conflictingRecurrings as $conflict) {
                $conflict->update(['conflict_id' => $conflictId]);
            }

            $recurring->conflict_id = $conflictId;
            $recurring->save();
        }

        return $conflictingRecurrings;
    }
}
