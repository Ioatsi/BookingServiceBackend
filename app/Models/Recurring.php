<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recurring extends Model
{
    use HasFactory;
    protected $fillable = [
        'semester_id',
        'title',
        'status',
    ];
    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }
    public function day(){
        return $this->hasMany(Day::class);
    }
    
}