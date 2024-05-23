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


        // Log the response from the CAS server
        \Log::info('CAS server response:', $response->json());


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
