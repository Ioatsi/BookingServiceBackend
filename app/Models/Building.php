<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Building extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'name',
    ];

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
