<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'email',
        'first_name',
        'last_name',
    ];
    protected $attributes = [
        'email'=>'no email',
        'AM'=>0,
        'first_name'=>'no  first name',
        'last_name'=>'no last name',
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'booker_id');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
    public function rooms()
    {
        return $this->belongsToMany(Room::class);
    }
}
