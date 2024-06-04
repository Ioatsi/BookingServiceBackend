<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Facades\Gate;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        Gate::define('create-booking', function ($user) {
            return $user->roles->contains(function ($role) {
                return in_array($role->name, ['admin', 'faculty']);
            });
        });

    }
}
