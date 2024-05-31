<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Subfission\Cas\Facades\Cas;
use App\Models\User;
use stdClass;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
class LoginController extends Controller
{
    public function login()
    {
        $casLoginUrl = config('https://sso.ihu.gr') . '/' . config('login') . '?service=' . urlencode(route('cas.callback'));
        return redirect($casLoginUrl);
    }
    public function handleCasCallback(Request $request)
    {
        $ticket = $request->query('ticket');

        if ($ticket) {
            // Validate ticket with CAS server
            $casValidateUrl = 'https://sso.ihu.gr/serviceValidate';

            $params = [
                'service' => 'http://booking.iee.ihu.gr/cas/callback',
                'ticket' => $ticket
            ];

            $client = new Client();
            $response = $client->request('GET', $casValidateUrl, ['query' => $params]);

            // Parse CAS server response
            $xmlResponse = $response->getBody()->getContents();
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($xmlResponse);
            $xml->registerXPathNamespace('cas', 'http://www.yale.edu/tp/cas');

            // Check if authentication is successful
            if ($xml->xpath('//cas:authenticationSuccess/cas:user')) {

                $user = new stdClass();

                // Extract the username
                $username = $xml->xpath('//cas:authenticationSuccess/cas:user');
                $user->username = (string) $username[0];

                // Extract and map attributes to object properties
                $attributes = $xml->xpath('//cas:authenticationSuccess/cas:attributes/*');
                foreach ($attributes as $attribute) {
                    $name = $attribute->getName();
                    $value = (string) $attribute;
                    $user->$name = json_decode('"' . $value . '"');
                }

                // Determine role based on 'eduPersonPrimaryAffiliation'
                $casRole = $user->eduPersonPrimaryAffiliation;

                // Check if user exists in the database
                $existingUser = User::where('username', $user->username)->first();

                if (!$existingUser) {
                    // Create new user
                    $givenName = $user->{'givenName-lang-el'} ?? null;
                    $sn = $user->{'sn-lang-el'} ?? null;
                    $newUser = new User();
                    $newUser->username = $user->username;
                    $newUser->email = $user->mail;
                    $newUser->first_name = $givenName;
                    $newUser->last_name = $sn;

                    $newUser->save();

                    if ($casRole == 'faculty' || $casRole == 'staff') {
                        $newUser->roles()->attach(3);
                    } else {
                        $newUser->roles()->attach(4);
                    }

                    $existingUser = $newUser;
                }

                // Check if the user should be an admin
                $adminUsernames = explode(',', env('ADMIN_USERNAMES'));
                if (in_array($existingUser->username, $adminUsernames)) {
                    // Assuming 1 is the ID for the admin role
                    if (!$existingUser->roles->contains(1)) {
                        $existingUser->roles()->attach(1);
                    }
                }

                // Log in the user
                Auth::login($existingUser);

                $user = Auth::user(); // Retrieve the authenticated user

                // Generate JWT token for the user
                $token = JWTAuth::fromUser($user);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Authentication successful',
                    'redirect_url' => '/',
                    'token' => $token,
                    'user' => $existingUser
                ]);
            } else {
                // CAS authentication failed
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Authentication failed',
                    'redirect_url' => '/',
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

    public function logout(Request $request)
    {
        Auth::logout();

        // Optionally, you can clear the user's session data
        $request->session()->invalidate();

        // Redirect to a desired route after logout
        return redirect('/');
    }
    public function authenticated()
    {
        return response()->json(['authenticated' => Auth::check()]);
    }
}
