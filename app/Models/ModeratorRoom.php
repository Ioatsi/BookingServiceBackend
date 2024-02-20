<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModeratorRoom extends Model
{
    protected $table = 'moderator_room';

    protected $fillable = [
        'user_id',
        'room_id',
    ];
}
