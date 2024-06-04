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
        $this->registerPolicies();
        //
        Gate::define('create-booking', function ($user) {
            return $user->roles->contains(function ($role) {
                return in_array($role->name, ['admin', 'faculty']);
            });
        });

    }
}
