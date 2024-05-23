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

        // Handle CAS server response and user authentication
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
