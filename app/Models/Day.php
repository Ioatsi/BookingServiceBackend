<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Day extends Model
{
    use HasFactory;
    protected $fillable = [
        'recurring_id',
        'name',
        'start',
        'end',
        'status',
        'room_id',
        'semester_id',
    ];
    protected $casts = [
        'start' => 'datetime',
        'end' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->attributes['semester_id'] = Semester::where('is_current', true)->value('id');
    }    

    public function recurring()
    {
        return $this->belongsTo(Recurring::class);
    }
}
