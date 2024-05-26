<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Subfission\Cas\Facades\Cas;
use App\Models\User;

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
        $ticket = $request->input('ticket');
        if ($ticket) {
            $casServiceUrl = config('cas.base_url') . '/' . config('cas.service_url');
            $response = file_get_contents($casServiceUrl . '?ticket=' . $ticket . '&service=' . urlencode(route('cas.callback')));

            // Parse CAS response using SimpleXML
            $xml = simplexml_load_string($response);
            if ($xml && isset($xml->authenticationSuccess)) {
                $username = (string) $xml->authenticationSuccess->user;

                // Dynamically create or update user
                $user = User::firstOrCreate(
                    ['username' => $username],
                    ['email' => $username . '@example.com'] // Add other fields as necessary
                );

                // Authenticate the user
                Auth::login($user);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Authentication successful',
                    'redirect_url' => '/'
                ]);
            }
        }

        return response()->json([
            'status' => 'error',
            'message' => 'CAS authentication failed',
            'redirect_url' => '/login'
        ]);
    }
}
