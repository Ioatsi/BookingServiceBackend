<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserRoles extends Model
{
    protected $table = 'user_roles';

    protected $fillable = [
        'user_id',
        'role_id',
    ];

    // Define the relationship with the users table
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Define the relationship with the roles table
    public function role()
    {
        return $this->belongsTo(Roles::class);
    }
}
