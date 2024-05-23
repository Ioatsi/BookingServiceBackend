<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Subfission\Cas\Facades\Cas;

class CasAuth
{
    public function handle($request, Closure $next)
    {
        if (Cas::isAuthenticated()) {
            $username = Cas::user();
            // Optionally, you can load or create a user from your database here
            $user = \App\Models\User::firstOrCreate(['username' => $username]);
            Auth::login($user);
            return $next($request);
        } else {
            Cas::authenticate();
        }
    }
}
