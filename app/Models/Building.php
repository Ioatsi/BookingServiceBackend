<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Building extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];
    protected $attributes = [
        'info' => 'No Info',
    ];
    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function departments()
    {
        return $this->belongsToMany(Department::class);
    }
}
