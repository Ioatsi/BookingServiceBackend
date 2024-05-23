<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Subfission\Cas\Facades\Cas;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class LoginController extends Controller
{

    public function login(Request $request)
    {
        // Validate request
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        // Authenticate user against CAS server
        $response = Http::post('https://sso.ihu.gr/', [
            'username' => $request->username,
            'password' => $request->password,
        ]);


        // Check if the CAS authentication was successful
        if ($response->successful()) {
            // Extract the user information or token from the response
            $userData = $response->json();

            // Optionally, you can store user information in session or database
            // Example:
            // session(['user' => $userData]);

            // Return a JSON response indicating successful authentication
            return response()->json(['message' => 'Authentication successful', 'user' => $userData], 200);
        } else {
            // CAS authentication failed
            // Return a JSON response with error message
            return response()->json(['error' => 'Invalid username or password'], 401);
        }
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
