<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Subfission\Cas\Facades\Cas;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function login(){
        $casLoginUrl = config('https://sso.ihu.gr') . '/' . config('login') . '?service=' . urlencode(route('cas.callback'));
        return redirect($casLoginUrl);
    }
    public function handleCasCallback(Request $request)
    {
        // Extract the ticket parameter from the request
        $ticket = $request->input('ticket');

        // Validate the ticket with the CAS server
        // You can use a CAS client library or make an HTTP request to the CAS server
        // Once validated, retrieve user attributes from the CAS server response

        // Authenticate the user in your Laravel application
        // This may involve creating a session for the user or storing user information

        // Redirect the user to the appropriate route or return a response indicating successful authentication
    }
    /**
     * Handle CAS logout.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    
    public function logout()
    {
        Cas::logout(); // Perform CAS logout
        Auth::logout(); // Perform local logout
        return redirect('/'); // Redirect to home page or any other page
    }
}
