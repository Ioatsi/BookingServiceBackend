<?php

namespace App\Providers;
use Illuminate\Support\Facades\Gate;
use App\Models\User; // Import the User model if not already imported

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
        Gate::define('create-booking', function ($userId) {            
            return User::where('users.id', $userId)->whereHas('roles', function ($query) {
                $query->where('roles.id', 1); // Assuming role_id 1 represents Faculty
            })->exists();
        });
    }
}
