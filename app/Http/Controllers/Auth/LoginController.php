<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Subfission\Cas\Facades\Cas;
use App\Models\User;

use GuzzleHttp\Client;
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
        $ticket = $request->query('ticket');

        if ($ticket) {
            // Validate ticket with CAS server
            $casValidateUrl = config('cas.base_url') . '/serviceValidate';

            $params = [
                'service' => 'http://booking.iee.ihu.gr',
                'ticket' => $ticket
            ];

            $client = new Client();
            $response = $client->request('GET', $casValidateUrl, ['query' => $params]);

            // Parse CAS server response
            $xmlResponse = $response->getBody()->getContents();
            $xml = simplexml_load_string($xmlResponse);
            
            // Check if authentication is successful
            if ($xml && $xml->authenticationSuccess) {
                // Extract user attributes
                $userAttributes = [];
                foreach ($xml->authenticationSuccess->attributes() as $key => $value) {
                    $userAttributes[$key] = (string) $value;
                }

                // Here, you can authenticate the user in your Laravel application using the user attributes.
                // For example, you can check if the user exists in your database or create a new user.

                // After authentication, you can redirect the user to the desired route
                return response()->json([
                    'status' => 'success',
                    'message' => 'Authentication successful',
                    'redirect_url' => '/'
                ]);
            } else {
                // CAS authentication failed
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Authentication failed',
                    'redirect_url' => '/'
                ]);
            }
        } else {
            // CAS authentication failed
            return response()->json([
                'status' => 'fail',
                'message' => 'Ticket not found',
                'redirect_url' => '/'
            ]);
        }
    }
}
