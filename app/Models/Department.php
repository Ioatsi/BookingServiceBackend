<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
    ];

    public function buildings()
    {
        return $this->belongsToMany(Building::class);
    }
    public function rooms()
    {
        return $this->hasMany(Room::class);
    }
}
