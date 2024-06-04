<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use App\Models\User; // Import the User model if not already imported

use Illuminate\Support\Facades\DB;
use App\Models\Booking;
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

        // Gate for editing a booking
        Gate::define('edit-booking', function ($user, Booking $booking) {
            // Check if the user is an admin
            $isAdmin = $user->roles->contains('name', 'admin');

            // Check if the user is the booker of the booking
            $isBooker = $booking->booker_id === $user->id;

            // Check if the user moderates the room where the booking takes place
            $isRoomModerator = DB::table('moderator_room')
            ->where('user_id', $user->id)
            ->where('room_id', $booking->room_id)
            ->exists();

            // Allow editing if any of the conditions are true
            return $isAdmin || $isBooker || $isRoomModerator;
        });
    }
}
