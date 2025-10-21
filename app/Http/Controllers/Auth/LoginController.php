<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

class LoginController extends Controller
{
    /**
     * Handle a login request and return a JWT if successful.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Find user by username
        $user = User::where('username', $credentials['username'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Invalid username or password'], 401);
        }

        // Generate JWT
        $token = JWTAuth::fromUser($user);

        return response()->json([
            'status' => 'success',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name'),
            ],
        ]);
    }

    /**
     * Return info about the currently authenticated user.
     */
    public function me()
    {
        try {
            $user = auth()->user();
            return response()->json($user);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
    }

    /**
     * Refresh the JWT token.
     */
    public function refresh()
    {
        try {
            $newToken = JWTAuth::refresh();
            return response()->json([
                'status' => 'success',
                'token' => $newToken,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to refresh token'], 500);
        }
    }

    /**
     * Log out the user (invalidate token).
     */
    public function logout()
    {
        try {
            auth()->logout();
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error'], 500);
        }
    }
}
