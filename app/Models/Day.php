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
        'status'
    ];
    protected $casts = [
        'start' => 'datetime',
        'end' => 'datetime',
    ];

    public function recurring(){
        return $this->belongsTo(Recurring::class);
    }

}
